<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ProductivityExport implements FromArray, WithHeadings
{
    protected $insights;

    public function __construct(array $insights)
    {
        $this->insights = $insights;
    }

    public function array(): array
    {
        return [
            [
                'Productivity Score' => $this->insights['productivity_score'] ?? 0,
                'Most Productive Hours' => json_encode($this->insights['energy_patterns']['most_productive_hours'] ?? []),
                'Burnout Risk' => $this->insights['burnout_risk']['risk_level'] ?? 'Unknown',
                'Predictive Productivity' => json_encode($this->insights['predictive_productivity'] ?? []),
            ],
        ];
    }

    public function headings(): array
    {
        return [
            'Productivity Score',
            'Most Productive Hours',
            'Burnout Risk',
            'Predictive Productivity',
        ];
    }
}
