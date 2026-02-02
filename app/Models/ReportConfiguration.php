<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportConfiguration extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'name',
        'report_type',
        'description',
        'parameters',
        'columns',
        'filters',
        'grouping',
        'sorting',
        'is_default',
        'is_system',
        'created_by',
    ];

    protected $casts = [
        'parameters' => 'array',
        'columns' => 'array',
        'filters' => 'array',
        'grouping' => 'array',
        'sorting' => 'array',
        'is_default' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scheduledReports(): HasMany
    {
        return $this->hasMany(ScheduledReport::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ReportSnapshot::class);
    }

    public static function getReportTypes(): array
    {
        return [
            'balance_sheet' => 'Balance Sheet',
            'profit_loss' => 'Profit & Loss',
            'cash_flow' => 'Statement of Cash Flows',
            'trial_balance' => 'Trial Balance',
            'general_ledger' => 'General Ledger',
            'accounts_receivable_aging' => 'Accounts Receivable Aging',
            'accounts_payable_aging' => 'Accounts Payable Aging',
            'sales_by_customer' => 'Sales by Customer',
            'sales_by_product' => 'Sales by Product',
            'expenses_by_vendor' => 'Expenses by Vendor',
            'expenses_by_category' => 'Expenses by Category',
            'inventory_valuation' => 'Inventory Valuation',
            'project_profitability' => 'Project Profitability',
            'tax_summary' => 'Tax Summary',
        ];
    }

    public function duplicate(string $newName): self
    {
        $new = $this->replicate();
        $new->name = $newName;
        $new->is_default = false;
        $new->is_system = false;
        $new->save();

        return $new;
    }
}
