<?php

namespace App\Services;

use App\Models\Departemen;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class ProposalApprovers
{
    public function forDepartment(Departemen $department): array
    {
        $result = [
            'departemen_head' => User::where('role', 'departemen_head')->where('departemen_id', $department->id)->where('is_active', 1)->orderBy('id')->first(),
            'group_head' => User::where('role', 'group_head')->where('grup_id', $department->grup_id)->where('is_active', 1)->orderBy('id')->first(),
            'gembala' => User::where('role', 'gembala')->where('is_active', 1)->orderBy('id')->first(),
        ];
        foreach ($result as $role => $user) {
            if (! $user) {
                throw ValidationException::withMessages(['approver' => 'Approver aktif untuk '.$role.' belum tersedia. Hubungi admin.']);
            }
        }

        return $result;
    }
}
