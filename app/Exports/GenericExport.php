<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class GenericExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected $collection;
    protected $headings;
    protected $mapping;

    public function __construct($collection, array $headings, $mapping = null)
    {
        $this->collection = $collection;
        $this->headings = $headings;
        $this->mapping = $mapping;
    }

    public function collection()
    {
        return $this->collection;
    }

    public function headings(): array
    {
        return $this->headings;
    }

    public function map($row): array
    {
        if (is_callable($this->mapping)) {
            return call_user_func($this->mapping, $row);
        }

        if (is_array($this->mapping)) {
            $mappedRow = [];
            foreach ($this->mapping as $column) {
                $mappedRow[] = data_get($row, $column);
            }
            return $mappedRow;
        }

        // Si no hay mapping, devolvemos todo el array/modelo
        return is_array($row) ? $row : $row->toArray();
    }
}
