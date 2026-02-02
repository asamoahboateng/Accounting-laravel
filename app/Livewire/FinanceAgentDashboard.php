<?php

namespace App\Livewire;

use App\Models\AnomalyDetection;
use App\Models\Bill;
use App\Models\BooksCloseRun;
use App\Models\FiscalPeriod;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\AnomalyDetectionService;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class FinanceAgentDashboard extends Component
{
    public bool $showAnomalyPanel = false;
    public bool $isRunningAnalysis = false;
    public ?string $selectedAnomalyId = null;
    public string $chatInput = '';
    public array $chatMessages = [];

    protected AnomalyDetectionService $anomalyService;

    public function boot(AnomalyDetectionService $anomalyService): void
    {
        $this->anomalyService = $anomalyService;
    }

    public function mount(): void
    {
        $this->chatMessages = [
            [
                'role' => 'assistant',
                'content' => 'Hello! I\'m your Finance Agent. I can help you analyze your financial data, detect anomalies, and provide insights. What would you like to know?',
            ],
        ];
    }

    #[Computed]
    public function company()
    {
        return filament()->getTenant();
    }

    #[Computed]
    public function currentPeriod(): ?FiscalPeriod
    {
        return FiscalPeriod::where('company_id', $this->company?->id)
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->first();
    }

    #[Computed]
    public function openAnomalies(): Collection
    {
        return AnomalyDetection::where('company_id', $this->company?->id)
            ->whereIn('status', ['open', 'reviewed'])
            ->orderByRaw("CASE severity WHEN 'critical' THEN 1 WHEN 'warning' THEN 2 ELSE 3 END")
            ->limit(10)
            ->get();
    }

    #[Computed]
    public function latestBooksCloseRun(): ?BooksCloseRun
    {
        return BooksCloseRun::where('company_id', $this->company?->id)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    #[Computed]
    public function financialInsights(): array
    {
        if (! $this->company) {
            return [];
        }

        $insights = [];

        // Revenue trend
        $currentMonthRevenue = Invoice::where('company_id', $this->company->id)
            ->whereMonth('invoice_date', now()->month)
            ->whereYear('invoice_date', now()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        $lastMonthRevenue = Invoice::where('company_id', $this->company->id)
            ->whereMonth('invoice_date', now()->subMonth()->month)
            ->whereYear('invoice_date', now()->subMonth()->year)
            ->where('status', '!=', 'void')
            ->sum('total_amount');

        if ($lastMonthRevenue > 0) {
            $revenueChange = (($currentMonthRevenue - $lastMonthRevenue) / $lastMonthRevenue) * 100;
            $insights[] = [
                'type' => $revenueChange >= 0 ? 'positive' : 'negative',
                'icon' => $revenueChange >= 0 ? 'trending-up' : 'trending-down',
                'message' => sprintf(
                    'Revenue is %s %.1f%% compared to last month',
                    $revenueChange >= 0 ? 'up' : 'down',
                    abs($revenueChange)
                ),
            ];
        }

        // Overdue invoices
        $overdueCount = Invoice::where('company_id', $this->company->id)
            ->where('due_date', '<', now())
            ->where('balance_due', '>', 0)
            ->whereIn('status', ['sent', 'viewed', 'partial'])
            ->count();

        if ($overdueCount > 0) {
            $overdueAmount = Invoice::where('company_id', $this->company->id)
                ->where('due_date', '<', now())
                ->where('balance_due', '>', 0)
                ->whereIn('status', ['sent', 'viewed', 'partial'])
                ->sum('balance_due');

            $insights[] = [
                'type' => 'warning',
                'icon' => 'exclamation-circle',
                'message' => sprintf(
                    '%d invoices totaling $%s are overdue',
                    $overdueCount,
                    number_format($overdueAmount, 2)
                ),
            ];
        }

        // Upcoming bills
        $upcomingBills = Bill::where('company_id', $this->company->id)
            ->where('due_date', '<=', now()->addDays(7))
            ->where('due_date', '>=', now())
            ->where('balance_due', '>', 0)
            ->whereIn('status', ['pending', 'partial'])
            ->count();

        if ($upcomingBills > 0) {
            $upcomingAmount = Bill::where('company_id', $this->company->id)
                ->where('due_date', '<=', now()->addDays(7))
                ->where('due_date', '>=', now())
                ->where('balance_due', '>', 0)
                ->sum('balance_due');

            $insights[] = [
                'type' => 'info',
                'icon' => 'calendar',
                'message' => sprintf(
                    '%d bills totaling $%s due in the next 7 days',
                    $upcomingBills,
                    number_format($upcomingAmount, 2)
                ),
            ];
        }

        // Anomaly alerts
        $criticalAnomalies = $this->openAnomalies->where('severity', 'critical')->count();

        if ($criticalAnomalies > 0) {
            $insights[] = [
                'type' => 'danger',
                'icon' => 'shield-exclamation',
                'message' => sprintf(
                    '%d critical anomalies detected requiring immediate attention',
                    $criticalAnomalies
                ),
            ];
        }

        return $insights;
    }

    public function runBooksCloseAnalysis(): void
    {
        if (! $this->currentPeriod || $this->isRunningAnalysis) {
            return;
        }

        $this->isRunningAnalysis = true;

        try {
            $run = $this->anomalyService->runBooksClose($this->company, $this->currentPeriod);

            $this->chatMessages[] = [
                'role' => 'assistant',
                'content' => sprintf(
                    'Books close analysis completed. I reviewed %d transactions and found %d anomalies (%d critical, %d warnings). %s',
                    $run->transactions_processed,
                    $run->anomalies_found,
                    $run->errors_count,
                    $run->warnings_count,
                    $run->errors_count > 0 ? 'Please review the critical items immediately.' : 'No critical issues found.'
                ),
            ];

            $this->showAnomalyPanel = true;
        } catch (\Exception $e) {
            $this->chatMessages[] = [
                'role' => 'assistant',
                'content' => 'I encountered an error while running the analysis: ' . $e->getMessage(),
            ];
        } finally {
            $this->isRunningAnalysis = false;
        }
    }

    public function resolveAnomaly(string $anomalyId, string $action): void
    {
        $anomaly = AnomalyDetection::find($anomalyId);

        if (! $anomaly) {
            return;
        }

        if ($action === 'dismiss') {
            $anomaly->dismiss();
            $this->chatMessages[] = [
                'role' => 'assistant',
                'content' => "I've dismissed the anomaly: \"{$anomaly->title}\". It will no longer appear in alerts.",
            ];
        } elseif ($action === 'resolve') {
            $anomaly->resolve('Resolved via Finance Agent');
            $this->chatMessages[] = [
                'role' => 'assistant',
                'content' => "Great! I've marked the anomaly \"{$anomaly->title}\" as resolved.",
            ];
        }

        $this->selectedAnomalyId = null;
    }

    public function sendChatMessage(): void
    {
        if (empty(trim($this->chatInput))) {
            return;
        }

        $userMessage = trim($this->chatInput);
        $this->chatMessages[] = ['role' => 'user', 'content' => $userMessage];
        $this->chatInput = '';

        // Simple AI-like response based on keywords
        $response = $this->generateAgentResponse($userMessage);
        $this->chatMessages[] = ['role' => 'assistant', 'content' => $response];
    }

    protected function generateAgentResponse(string $message): string
    {
        $messageLower = strtolower($message);

        if (str_contains($messageLower, 'anomal') || str_contains($messageLower, 'issue') || str_contains($messageLower, 'problem')) {
            $count = $this->openAnomalies->count();

            return $count > 0
                ? "You have {$count} open anomalies. Would you like me to run a full books close analysis? Just click the 'Run Analysis' button above."
                : "No anomalies detected! Your books look clean.";
        }

        if (str_contains($messageLower, 'revenue') || str_contains($messageLower, 'income') || str_contains($messageLower, 'sales')) {
            $revenue = Invoice::where('company_id', $this->company->id)
                ->whereMonth('invoice_date', now()->month)
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            return sprintf(
                'Your revenue this month is $%s. I can provide more detailed analysis if you run the books close process.',
                number_format($revenue, 2)
            );
        }

        if (str_contains($messageLower, 'expense') || str_contains($messageLower, 'bill') || str_contains($messageLower, 'cost')) {
            $expenses = Bill::where('company_id', $this->company->id)
                ->whereMonth('bill_date', now()->month)
                ->where('status', '!=', 'void')
                ->sum('total_amount');

            return sprintf(
                'Your expenses this month total $%s. Would you like me to break this down by category?',
                number_format($expenses, 2)
            );
        }

        if (str_contains($messageLower, 'overdue') || str_contains($messageLower, 'late') || str_contains($messageLower, 'collect')) {
            $overdue = Invoice::where('company_id', $this->company->id)
                ->where('due_date', '<', now())
                ->where('balance_due', '>', 0)
                ->get();

            if ($overdue->isEmpty()) {
                return 'Great news! You have no overdue invoices.';
            }

            return sprintf(
                'You have %d overdue invoices totaling $%s. The oldest is from %s. Would you like me to help you prioritize collection efforts?',
                $overdue->count(),
                number_format($overdue->sum('balance_due'), 2),
                $overdue->min('due_date')?->format('M j, Y')
            );
        }

        if (str_contains($messageLower, 'close') || str_contains($messageLower, 'period') || str_contains($messageLower, 'month end')) {
            return "I can help you with the month-end close process. Click 'Run Analysis' to check for any issues before closing the books. This will scan for anomalies, duplicate entries, and potential errors.";
        }

        if (str_contains($messageLower, 'help') || str_contains($messageLower, 'what can you')) {
            return "I can help you with:\n- Running books close analysis\n- Detecting anomalies and unusual transactions\n- Providing revenue and expense insights\n- Identifying overdue invoices\n- Analyzing financial trends\n\nJust ask me about any of these topics!";
        }

        return "I understand you're asking about \"{$message}\". To provide the most accurate insights, I recommend running a full books close analysis. This will give me comprehensive data to work with. Would you like me to run the analysis now?";
    }

    public function render()
    {
        return view('livewire.finance-agent-dashboard');
    }
}
