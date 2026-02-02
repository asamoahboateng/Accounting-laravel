<?php

namespace App\Livewire\Banking;

use App\Models\BankAccount;
use App\Models\JournalEntryLine;
use App\Models\Reconciliation;
use App\Models\ReconciliationItem;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class BankReconciliation extends Component
{
    public ?string $bankAccountId = null;
    public ?string $reconciliationId = null;
    public ?string $statementDate = null;
    public float $statementBalance = 0;
    public float $openingBalance = 0;
    public array $selectedTransactions = [];
    public string $searchTerm = '';
    public string $filterType = 'all'; // all, deposits, withdrawals
    public bool $showCompleted = false;

    protected $rules = [
        'bankAccountId' => 'required|uuid|exists:bank_accounts,id',
        'statementDate' => 'required|date',
        'statementBalance' => 'required|numeric',
    ];

    public function mount(?string $bankAccountId = null, ?string $reconciliationId = null): void
    {
        $this->bankAccountId = $bankAccountId;
        $this->reconciliationId = $reconciliationId;

        if ($reconciliationId) {
            $this->loadExistingReconciliation();
        }
    }

    protected function loadExistingReconciliation(): void
    {
        $reconciliation = Reconciliation::find($this->reconciliationId);

        if ($reconciliation) {
            $this->bankAccountId = $reconciliation->bank_account_id;
            $this->statementDate = $reconciliation->statement_date->format('Y-m-d');
            $this->statementBalance = (float) $reconciliation->statement_balance;
            $this->openingBalance = (float) $reconciliation->opening_balance;
            $this->selectedTransactions = $reconciliation->clearedItems()
                ->pluck('journal_entry_line_id')
                ->toArray();
        }
    }

    #[Computed]
    public function bankAccount(): ?BankAccount
    {
        return $this->bankAccountId ? BankAccount::find($this->bankAccountId) : null;
    }

    #[Computed]
    public function bankAccounts(): array
    {
        return BankAccount::where('company_id', filament()->getTenant()?->id)
            ->where('is_active', true)
            ->get()
            ->map(fn ($account) => [
                'id' => $account->id,
                'name' => $account->name,
                'balance' => $account->current_balance,
            ])
            ->toArray();
    }

    #[Computed]
    public function transactions(): array
    {
        if (! $this->bankAccountId || ! $this->bankAccount) {
            return [];
        }

        $query = JournalEntryLine::query()
            ->where('account_id', $this->bankAccount->account_id)
            ->whereHas('journalEntry', fn ($q) => $q->where('status', 'posted'))
            ->where('is_reconciled', false)
            ->with(['journalEntry.transaction', 'contact']);

        if ($this->searchTerm) {
            $query->where(function ($q) {
                $q->where('description', 'like', '%' . $this->searchTerm . '%')
                    ->orWhereHas('journalEntry', fn ($jq) => $jq->where('description', 'like', '%' . $this->searchTerm . '%'));
            });
        }

        if ($this->filterType === 'deposits') {
            $query->where('type', 'debit');
        } elseif ($this->filterType === 'withdrawals') {
            $query->where('type', 'credit');
        }

        return $query->orderBy('created_at', 'desc')
            ->limit(100)
            ->get()
            ->map(fn ($line) => [
                'id' => $line->id,
                'date' => $line->journalEntry->entry_date->format('m/d/Y'),
                'description' => $line->description ?? $line->journalEntry->description,
                'reference' => $line->journalEntry->entry_number,
                'type' => $line->type,
                'amount' => $line->type === 'debit' ? $line->amount : -$line->amount,
                'contact' => $line->contact?->display_name,
                'is_selected' => in_array($line->id, $this->selectedTransactions),
            ])
            ->toArray();
    }

    #[Computed]
    public function clearedBalance(): float
    {
        $total = 0;

        foreach ($this->transactions as $transaction) {
            if (in_array($transaction['id'], $this->selectedTransactions)) {
                $total += $transaction['amount'];
            }
        }

        return $this->openingBalance + $total;
    }

    #[Computed]
    public function difference(): float
    {
        return $this->statementBalance - $this->clearedBalance;
    }

    #[Computed]
    public function isBalanced(): bool
    {
        return abs($this->difference) < 0.01;
    }

    public function toggleTransaction(string $transactionId): void
    {
        if (in_array($transactionId, $this->selectedTransactions)) {
            $this->selectedTransactions = array_diff($this->selectedTransactions, [$transactionId]);
        } else {
            $this->selectedTransactions[] = $transactionId;
        }
    }

    public function selectAll(): void
    {
        $this->selectedTransactions = collect($this->transactions)
            ->pluck('id')
            ->toArray();
    }

    public function deselectAll(): void
    {
        $this->selectedTransactions = [];
    }

    public function startReconciliation(): void
    {
        $this->validate();

        // Get or calculate opening balance
        $lastReconciliation = Reconciliation::where('bank_account_id', $this->bankAccountId)
            ->where('status', 'completed')
            ->orderBy('statement_date', 'desc')
            ->first();

        $this->openingBalance = $lastReconciliation
            ? (float) $lastReconciliation->statement_balance
            : (float) $this->bankAccount->account->opening_balance;

        $reconciliation = Reconciliation::create([
            'id' => (string) Str::uuid(),
            'company_id' => filament()->getTenant()->id,
            'bank_account_id' => $this->bankAccountId,
            'account_id' => $this->bankAccount->account_id,
            'statement_date' => $this->statementDate,
            'statement_start_date' => $lastReconciliation?->statement_end_date?->addDay() ?? now()->startOfYear(),
            'statement_end_date' => $this->statementDate,
            'statement_balance' => $this->statementBalance,
            'opening_balance' => $this->openingBalance,
            'cleared_balance' => $this->openingBalance,
            'difference' => $this->statementBalance - $this->openingBalance,
            'status' => 'in_progress',
            'created_by' => auth()->id(),
        ]);

        $this->reconciliationId = $reconciliation->id;
    }

    public function saveProgress(): void
    {
        if (! $this->reconciliationId) {
            return;
        }

        $reconciliation = Reconciliation::find($this->reconciliationId);

        // Remove old items and add new ones
        $reconciliation->items()->delete();

        foreach ($this->selectedTransactions as $lineId) {
            $line = JournalEntryLine::find($lineId);

            ReconciliationItem::create([
                'id' => (string) Str::uuid(),
                'company_id' => filament()->getTenant()->id,
                'reconciliation_id' => $this->reconciliationId,
                'journal_entry_line_id' => $lineId,
                'amount' => $line->type === 'debit' ? $line->amount : -$line->amount,
                'is_cleared' => true,
                'cleared_at' => now(),
            ]);
        }

        $reconciliation->recalculateDifference();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Progress saved successfully.',
        ]);
    }

    public function finishReconciliation(): void
    {
        if (! $this->reconciliationId || ! $this->isBalanced) {
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Reconciliation is not balanced. Difference must be $0.00.',
            ]);

            return;
        }

        $this->saveProgress();

        $reconciliation = Reconciliation::find($this->reconciliationId);
        $reconciliation->complete();

        $this->dispatch('notify', [
            'type' => 'success',
            'message' => 'Reconciliation completed successfully!',
        ]);

        $this->redirect(route('filament.admin.pages.dashboard', ['tenant' => filament()->getTenant()]));
    }

    public function render()
    {
        return view('livewire.banking.bank-reconciliation');
    }
}
