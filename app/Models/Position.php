<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'title',
        'description',
        'level',
        'min_salary',
        'max_salary',
        'required_skills',
        'preferred_skills',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'required_skills' => 'array',
            'preferred_skills' => 'array',
            'min_salary' => 'decimal:2',
            'max_salary' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    // Relationships
    public function employeePositions(): HasMany
    {
        return $this->hasMany(EmployeePosition::class);
    }

    public function currentEmployees()
    {
        return $this->hasManyThrough(
            User::class,
            EmployeePosition::class,
            'position_id',
            'id',
            'id',
            'user_id'
        )->where('employee_positions.is_current', true);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByLevel($query, $level)
    {
        return $query->where('level', $level);
    }
}
