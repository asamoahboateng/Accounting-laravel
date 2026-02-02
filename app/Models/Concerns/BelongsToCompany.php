<?php

namespace App\Models\Concerns;

use App\Models\Company;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToCompany
{
    public static function bootBelongsToCompany(): void
    {
        static::creating(function (Model $model) {
            if (! $model->company_id && filament()->getTenant()) {
                $model->company_id = filament()->getTenant()->id;
            }
        });

        static::addGlobalScope('company', function (Builder $builder) {
            if (filament()->getTenant()) {
                $builder->where($builder->getModel()->getTable() . '.company_id', filament()->getTenant()->id);
            }
        });
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
