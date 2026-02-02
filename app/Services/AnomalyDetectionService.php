<?php

namespace App\Services;

use App\Models\AnomalyDetection;
use App\Models\BooksCloseRun;
use App\Models\Company;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnomalyDetectionService
{
    protected Company $company;
    protected FiscalPeriod $period;
    protected BooksCloseRun $run;

    protected array $anomalies = [];

    public function runBooksClose(Company $company, FiscalPeriod $period): BooksCloseRun
    {
        $this->company = $company;
        $this->period = $period;

        // Create a books close run record
        $this->run = BooksCloseRun::create([
            'id' => (string) Str::uuid(),
            'company_id' => $company->id,
            'fiscal_period_id' => $period->id,
            'status' => 'running',
            'started_at' => now(),
            'initiated_by' => auth()->id(),
        ]);

        try {
            // Run all anomaly detection checks
            $this->detectUnusualAmounts();
            $this->detectDuplicateEntries();
            $this->detectMissingEntries();
            $this->detectTimingAnomalies();
            $this->detectPatternAnomalies();
            $this->detectUnbalancedEntries();
            $this->detectRoundNumberAnomalies();

            // Save all detected anomalies
            $this->saveAnomalies();

            // Update run status
            $this->run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'transactions_processed' => $this->getTransactionCount(),
                'anomalies_found' => count($this->anomalies),
                'warnings_count' => $this->countBySeverity('warning'),
                'errors_count' => $this->countBySeverity('critical'),
                'summary' => $this->generateSummary(),
            ]);
        } catch (\Exception $e) {
            $this->run->update([
                'status' => 'failed',
                'completed_at' => now(),
                'error_message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $this->run;
    }

    protected function detectUnusualAmounts(): void
    {
        // Get historical transaction amounts for the company
        $transactions = Transaction::where('company_id', $this->company->id)
            ->where('posting_date', '<', $this->period->start_date)
            ->where('status', 'posted')
            ->pluck('total_amount');

        if ($transactions->count() < 10) {
            return; // Not enough data for statistical analysis
        }

        $avgAmount = $transactions->avg();
        $stdAmount = $this->calculateStdDev($transactions);

        if (!$avgAmount || !$stdAmount) {
            return;
        }

        $stats = (object) ['avg_amount' => $avgAmount, 'std_amount' => $stdAmount];

        $threshold = $stats->avg_amount + (3 * $stats->std_amount); // 3 standard deviations

        // Find transactions with unusually high amounts
        $unusual = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->where('total_amount', '>', $threshold)
            ->get();

        foreach ($unusual as $transaction) {
            $this->addAnomaly([
                'detection_type' => 'unusual_amount',
                'severity' => $transaction->total_amount > ($threshold * 2) ? 'critical' : 'warning',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'anomaly_code' => 'UNUSUAL_AMOUNT_HIGH',
                'title' => 'Unusually High Transaction Amount',
                'description' => sprintf(
                    'Transaction %s has an amount of %s, which is %.1f standard deviations above the historical average of %s.',
                    $transaction->transaction_number,
                    number_format($transaction->total_amount, 2),
                    ($transaction->total_amount - $stats->avg_amount) / $stats->std_amount,
                    number_format($stats->avg_amount, 2)
                ),
                'confidence_score' => min(0.99, 0.5 + (($transaction->total_amount - $threshold) / $threshold) * 0.5),
                'detection_data' => [
                    'transaction_amount' => $transaction->total_amount,
                    'average_amount' => $stats->avg_amount,
                    'std_deviation' => $stats->std_amount,
                    'threshold' => $threshold,
                ],
                'suggested_actions' => [
                    'Review the transaction for accuracy',
                    'Verify supporting documentation',
                    'Confirm authorization for this amount',
                ],
            ]);
        }
    }

    protected function detectDuplicateEntries(): void
    {
        // Find potential duplicate transactions
        $duplicates = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->select('contact_id', 'total_amount', 'transaction_date', DB::raw('COUNT(*) as count'))
            ->groupBy('contact_id', 'total_amount', 'transaction_date')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            $transactions = Transaction::where('company_id', $this->company->id)
                ->where('contact_id', $duplicate->contact_id)
                ->where('total_amount', $duplicate->total_amount)
                ->where('transaction_date', $duplicate->transaction_date)
                ->where('status', 'posted')
                ->get();

            $this->addAnomaly([
                'detection_type' => 'duplicate',
                'severity' => 'warning',
                'entity_type' => 'transaction',
                'entity_id' => $transactions->first()->id,
                'anomaly_code' => 'POTENTIAL_DUPLICATE',
                'title' => 'Potential Duplicate Transaction',
                'description' => sprintf(
                    'Found %d transactions with the same amount (%s), date (%s), and contact on the same day.',
                    $duplicate->count,
                    number_format($duplicate->total_amount, 2),
                    $duplicate->transaction_date
                ),
                'confidence_score' => 0.75,
                'detection_data' => [
                    'duplicate_count' => $duplicate->count,
                    'transaction_ids' => $transactions->pluck('id')->toArray(),
                ],
                'suggested_actions' => [
                    'Review transactions for duplicates',
                    'Void duplicate entry if confirmed',
                    'Check data import process',
                ],
            ]);
        }
    }

    protected function detectMissingEntries(): void
    {
        // Check for gaps in sequential numbering
        $transactions = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->orderBy('transaction_number')
            ->pluck('transaction_number')
            ->toArray();

        // Extract numeric portions and check for gaps
        $numbers = [];
        foreach ($transactions as $txnNum) {
            preg_match('/\d+/', $txnNum, $matches);
            if (! empty($matches)) {
                $numbers[] = (int) $matches[0];
            }
        }

        sort($numbers);

        for ($i = 1; $i < count($numbers); $i++) {
            $gap = $numbers[$i] - $numbers[$i - 1];
            if ($gap > 1) {
                $this->addAnomaly([
                    'detection_type' => 'missing_entry',
                    'severity' => 'info',
                    'entity_type' => 'transaction',
                    'entity_id' => null,
                    'anomaly_code' => 'SEQUENCE_GAP',
                    'title' => 'Gap in Transaction Sequence',
                    'description' => sprintf(
                        'Missing transaction numbers detected between %d and %d (gap of %d).',
                        $numbers[$i - 1],
                        $numbers[$i],
                        $gap - 1
                    ),
                    'confidence_score' => 0.6,
                    'detection_data' => [
                        'gap_start' => $numbers[$i - 1],
                        'gap_end' => $numbers[$i],
                        'missing_count' => $gap - 1,
                    ],
                    'suggested_actions' => [
                        'Verify if transactions were voided',
                        'Check for data migration issues',
                        'Document reason for gap',
                    ],
                ]);
            }
        }
    }

    protected function detectTimingAnomalies(): void
    {
        // Detect transactions dated on weekends or holidays
        // Use database-agnostic approach
        $weekendTransactions = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->get()
            ->filter(function ($transaction) {
                $dayOfWeek = $transaction->transaction_date->dayOfWeek;
                return $dayOfWeek === 0 || $dayOfWeek === 6; // Sunday = 0, Saturday = 6
            });

        foreach ($weekendTransactions as $transaction) {
            $this->addAnomaly([
                'detection_type' => 'timing',
                'severity' => 'info',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'anomaly_code' => 'WEEKEND_TRANSACTION',
                'title' => 'Weekend Transaction',
                'description' => sprintf(
                    'Transaction %s is dated on a weekend (%s). This may require review.',
                    $transaction->transaction_number,
                    $transaction->transaction_date->format('l, M j, Y')
                ),
                'confidence_score' => 0.4,
                'detection_data' => [
                    'day_of_week' => $transaction->transaction_date->format('l'),
                ],
                'suggested_actions' => [
                    'Verify transaction date is correct',
                    'Check if business operates on weekends',
                ],
            ]);
        }

        // Detect backdated transactions (created more than 7 days after transaction date)
        $backdated = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->get()
            ->filter(function ($transaction) {
                return $transaction->created_at->startOfDay()->diffInDays($transaction->transaction_date) > 7
                    && $transaction->created_at > $transaction->transaction_date;
            });

        foreach ($backdated as $transaction) {
            $this->addAnomaly([
                'detection_type' => 'timing',
                'severity' => 'warning',
                'entity_type' => 'transaction',
                'entity_id' => $transaction->id,
                'anomaly_code' => 'BACKDATED_TRANSACTION',
                'title' => 'Significantly Backdated Transaction',
                'description' => sprintf(
                    'Transaction %s was created on %s but dated %s (more than 7 days prior).',
                    $transaction->transaction_number,
                    $transaction->created_at->format('M j, Y'),
                    $transaction->transaction_date->format('M j, Y')
                ),
                'confidence_score' => 0.7,
                'detection_data' => [
                    'created_date' => $transaction->created_at->toDateString(),
                    'transaction_date' => $transaction->transaction_date->toDateString(),
                    'days_difference' => $transaction->created_at->diffInDays($transaction->transaction_date),
                ],
                'suggested_actions' => [
                    'Verify reason for backdating',
                    'Ensure proper authorization',
                    'Review internal controls',
                ],
            ]);
        }
    }

    protected function detectPatternAnomalies(): void
    {
        // Detect unusual vendor/customer activity patterns
        $contactActivity = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->whereNotNull('contact_id')
            ->select('contact_id', DB::raw('COUNT(*) as txn_count'), DB::raw('SUM(total_amount) as total'))
            ->groupBy('contact_id')
            ->having('txn_count', '>=', 10)
            ->get();

        foreach ($contactActivity as $activity) {
            // Compare with historical average
            $historical = Transaction::where('company_id', $this->company->id)
                ->where('contact_id', $activity->contact_id)
                ->where('fiscal_period_id', '!=', $this->period->id)
                ->where('status', 'posted')
                ->selectRaw('AVG(monthly_count) as avg_count, AVG(monthly_total) as avg_total FROM (
                    SELECT COUNT(*) as monthly_count, SUM(total_amount) as monthly_total
                    FROM transactions
                    WHERE company_id = ? AND contact_id = ? AND status = ?
                    GROUP BY fiscal_period_id
                ) as monthly', [$this->company->id, $activity->contact_id, 'posted'])
                ->first();

            if ($historical->avg_count && $activity->txn_count > ($historical->avg_count * 2)) {
                $this->addAnomaly([
                    'detection_type' => 'pattern',
                    'severity' => 'info',
                    'entity_type' => 'contact',
                    'entity_id' => $activity->contact_id,
                    'anomaly_code' => 'UNUSUAL_ACTIVITY_VOLUME',
                    'title' => 'Unusual Contact Activity Volume',
                    'description' => sprintf(
                        'Contact has %d transactions this period, which is %.1fx higher than the historical average of %.1f.',
                        $activity->txn_count,
                        $activity->txn_count / $historical->avg_count,
                        $historical->avg_count
                    ),
                    'confidence_score' => 0.6,
                    'detection_data' => [
                        'current_count' => $activity->txn_count,
                        'historical_average' => $historical->avg_count,
                        'current_total' => $activity->total,
                    ],
                    'suggested_actions' => [
                        'Review relationship with contact',
                        'Verify all transactions are legitimate',
                        'Consider if business changes explain increase',
                    ],
                ]);
            }
        }
    }

    protected function detectUnbalancedEntries(): void
    {
        // Find journal entries that are not balanced
        $unbalanced = JournalEntry::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->where('is_balanced', false)
            ->get();

        foreach ($unbalanced as $entry) {
            $difference = abs($entry->total_debit - $entry->total_credit);

            $this->addAnomaly([
                'detection_type' => 'missing_entry',
                'severity' => 'critical',
                'entity_type' => 'journal_entry',
                'entity_id' => $entry->id,
                'anomaly_code' => 'UNBALANCED_ENTRY',
                'title' => 'Unbalanced Journal Entry',
                'description' => sprintf(
                    'Journal entry %s has debits of %s and credits of %s (difference: %s).',
                    $entry->entry_number,
                    number_format($entry->total_debit, 2),
                    number_format($entry->total_credit, 2),
                    number_format($difference, 2)
                ),
                'confidence_score' => 1.0,
                'detection_data' => [
                    'total_debit' => $entry->total_debit,
                    'total_credit' => $entry->total_credit,
                    'difference' => $difference,
                ],
                'suggested_actions' => [
                    'URGENT: Correct the journal entry immediately',
                    'Add missing line to balance entry',
                    'Review posting process for errors',
                ],
            ]);
        }
    }

    protected function detectRoundNumberAnomalies(): void
    {
        // Detect suspiciously round numbers (potential estimates or fraud)
        $roundNumbers = Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->where('total_amount', '>=', 1000)
            ->whereRaw("total_amount = FLOOR(total_amount / 1000) * 1000") // Exactly divisible by 1000
            ->get();

        $roundCount = $roundNumbers->count();
        $totalCount = $this->getTransactionCount();

        // Only flag if percentage of round numbers is unusually high
        if ($totalCount > 20 && ($roundCount / $totalCount) > 0.15) {
            $this->addAnomaly([
                'detection_type' => 'pattern',
                'severity' => 'info',
                'entity_type' => 'transaction',
                'entity_id' => null,
                'anomaly_code' => 'HIGH_ROUND_NUMBER_RATIO',
                'title' => 'High Ratio of Round Number Transactions',
                'description' => sprintf(
                    '%d of %d transactions (%.1f%%) have amounts that are exactly divisible by 1000. This may indicate estimates rather than actual amounts.',
                    $roundCount,
                    $totalCount,
                    ($roundCount / $totalCount) * 100
                ),
                'confidence_score' => 0.5,
                'detection_data' => [
                    'round_count' => $roundCount,
                    'total_count' => $totalCount,
                    'percentage' => ($roundCount / $totalCount) * 100,
                    'sample_transactions' => $roundNumbers->take(5)->pluck('id')->toArray(),
                ],
                'suggested_actions' => [
                    'Review transactions with round amounts',
                    'Verify amounts against source documents',
                    'Consider if estimates need to be trued up',
                ],
            ]);
        }
    }

    protected function addAnomaly(array $data): void
    {
        $this->anomalies[] = array_merge($data, [
            'company_id' => $this->company->id,
            'fiscal_period_id' => $this->period->id,
            'status' => 'open',
        ]);
    }

    protected function saveAnomalies(): void
    {
        foreach ($this->anomalies as $anomaly) {
            AnomalyDetection::create(array_merge($anomaly, [
                'id' => (string) Str::uuid(),
            ]));
        }
    }

    protected function getTransactionCount(): int
    {
        return Transaction::where('company_id', $this->company->id)
            ->where('fiscal_period_id', $this->period->id)
            ->where('status', 'posted')
            ->count();
    }

    protected function countBySeverity(string $severity): int
    {
        return collect($this->anomalies)
            ->where('severity', $severity)
            ->count();
    }

    protected function generateSummary(): array
    {
        return [
            'total_anomalies' => count($this->anomalies),
            'by_severity' => [
                'critical' => $this->countBySeverity('critical'),
                'warning' => $this->countBySeverity('warning'),
                'info' => $this->countBySeverity('info'),
            ],
            'by_type' => collect($this->anomalies)
                ->groupBy('detection_type')
                ->map(fn ($items) => count($items))
                ->toArray(),
            'period' => [
                'start' => $this->period->start_date->toDateString(),
                'end' => $this->period->end_date->toDateString(),
            ],
            'transactions_reviewed' => $this->getTransactionCount(),
        ];
    }

    /**
     * Calculate standard deviation (database-agnostic)
     */
    protected function calculateStdDev(Collection $values): float
    {
        $count = $values->count();

        if ($count < 2) {
            return 0;
        }

        $mean = $values->avg();
        $sumSquaredDiff = $values->reduce(function ($carry, $value) use ($mean) {
            return $carry + pow($value - $mean, 2);
        }, 0);

        return sqrt($sumSquaredDiff / ($count - 1));
    }
}
