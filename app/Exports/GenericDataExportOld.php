<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class GenericDataExportOld implements FromArray, WithHeadings
{
  protected $data;

  public function __construct(array $data)
  {
    $this->data = $data;
  }

  public function array(): array
  {
    return $this->data;
  }

  public function headings(): array
  {
    // Use keys of first data item as headings
    return $this->data ? array_keys($this->data[0]) : [];
  }
}
