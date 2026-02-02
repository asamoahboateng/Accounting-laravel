<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnomalyRule extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'rule_type',
        'name',
        'description',
        'entity_type',
        'conditions',
        'severity',
        'is_active',
        'is_system',
    ];

    protected $casts = [
        'conditions' => 'array',
        'is_active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public static function getRuleTypes(): array
    {
        return [
            'threshold' => 'Threshold',
            'pattern' => 'Pattern',
            'statistical' => 'Statistical',
        ];
    }

    public static function getEntityTypes(): array
    {
        return [
            'transaction' => 'Transaction',
            'journal_entry' => 'Journal Entry',
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'payment' => 'Payment',
        ];
    }

    public static function getSeverities(): array
    {
        return [
            'info' => 'Info',
            'warning' => 'Warning',
            'critical' => 'Critical',
        ];
    }

    public function evaluate($entity): ?array
    {
        if (!$this->is_active) {
            return null;
        }

        foreach ($this->conditions as $condition) {
            if (!$this->checkCondition($entity, $condition)) {
                return null;
            }
        }

        return [
            'rule_id' => $this->id,
            'rule_name' => $this->name,
            'severity' => $this->severity,
            'description' => $this->description,
        ];
    }

    protected function checkCondition($entity, array $condition): bool
    {
        $field = $condition['field'] ?? null;
        $operator = $condition['operator'] ?? null;
        $value = $condition['value'] ?? null;

        if (!$field || !$operator) {
            return false;
        }

        $entityValue = data_get($entity, $field);

        return match ($operator) {
            '=' => $entityValue == $value,
            '!=' => $entityValue != $value,
            '>' => $entityValue > $value,
            '>=' => $entityValue >= $value,
            '<' => $entityValue < $value,
            '<=' => $entityValue <= $value,
            'contains' => str_contains((string) $entityValue, (string) $value),
            'not_contains' => !str_contains((string) $entityValue, (string) $value),
            'in' => in_array($entityValue, (array) $value),
            'not_in' => !in_array($entityValue, (array) $value),
            'is_null' => is_null($entityValue),
            'is_not_null' => !is_null($entityValue),
            default => false,
        };
    }
}
