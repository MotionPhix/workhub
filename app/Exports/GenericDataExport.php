<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class GenericDataExport implements FromArray, WithHeadings, WithStyles
{
  protected $data;
  protected $columns;

  public function __construct(array $data, ?array $columns = null)
  {
    $this->data = $data;
    $this->columns = $columns ?? (empty($data) ? [] : array_keys($data[0]));
  }

  public function array(): array
  {
    // Filter data based on selected columns
    return array_map(function ($item) {
      // Filter the columns based on the visible columns passed
      return array_intersect_key($item, array_flip($this->columns));
    }, $this->data);
  }

  public function headings(): array
  {
    return array_map(fn($col) => ucfirst(str_replace('_', ' ', $col), $this->columns);
  }

  public function styles(Worksheet $sheet)
  {
    $headerColor = $this->styling['header_color'] ?? '#4a4a4a';
    $alternateRowColor = $this->styling['alternate_row_color'] ?? '#f4f4f4';

    // Apply styles to the header row
    return [
        1 => [
            'font' => [
                'bold' => true,
                'color' => ['argb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => $headerColor],
            ],
        ],
        // Alternate row styling
        'A2:A' . (count($this->data) + 1) => [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['argb' => $alternateRowColor],
            ],
        ],
    ];
  }

}
