<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithCustomValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

class LaporanExport extends DefaultValueBinder implements FromView, WithCustomValueBinder
{
    public function __construct(private Collection $data, private string $role) {}

    public function bindValue(Cell $cell, $value): bool
    {
        if (is_string($value) && preg_match('/^[=+@-]/u', $value)) {
            $cell->setValueExplicit($value, DataType::TYPE_STRING);

            return true;
        }

        return parent::bindValue($cell, $value);
    }

    public function view(): View
    {
        return view('backend.laporan.export_excel', ['data' => $this->data, 'role' => $this->role]);
    }
}
