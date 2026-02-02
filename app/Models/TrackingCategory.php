<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCompany;
use App\Models\Concerns\HasUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrackingCategory extends Model
{
    use HasFactory, HasUuid, BelongsToCompany;

    protected $fillable = [
        'company_id',
        'type',
        'parent_id',
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(TrackingCategory::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(TrackingCategory::class, 'parent_id');
    }

    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($path, $parent->name);
            $parent = $parent->parent;
        }

        return implode(' > ', $path);
    }

    public static function getTypes(): array
    {
        return [
            'class' => 'Class',
            'department' => 'Department',
            'location' => 'Location',
            'project' => 'Project',
        ];
    }

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeClasses($query)
    {
        return $query->ofType('class');
    }

    public function scopeDepartments($query)
    {
        return $query->ofType('department');
    }

    public function scopeLocations($query)
    {
        return $query->ofType('location');
    }
}
