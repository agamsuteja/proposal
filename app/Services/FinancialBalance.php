<?php

namespace App\Services;

use App\Models\Proposal;

class FinancialBalance
{
    public function values(Proposal $proposal, bool $reservePending = true): array
    {
        $paid = DepartmentBudgetService::cents($proposal->pencairan()->sum('jumlah_dana'));
        $receipts = DepartmentBudgetService::cents($proposal->nota_penggunaan()->sum('jumlah'));
        $refunds = DepartmentBudgetService::cents($proposal->pengembalianDana()->whereIn('status', $reservePending ? ['pending', 'diterima'] : ['diterima'])->sum('jumlah'));

        return ['paid' => $paid, 'receipts' => $receipts, 'refunds' => $refunds, 'remaining' => $paid - $receipts - $refunds];
    }
}
