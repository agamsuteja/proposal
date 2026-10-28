<?php

namespace App\Support;

use App\Models\Departemen;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class Access
{
    public const ROLES = ['admin', 'keuangan', 'karyawan', 'departemen_head', 'group_head', 'gembala'];

    public static function departments(User $user): Builder
    {
        $query = Departemen::query();
        if (in_array($user->role, ['admin', 'keuangan', 'gembala'], true)) {
            return $query;
        }
        if ($user->role === 'group_head') {
            return $query->where('grup_id', $user->grup_id ?? 0);
        }

        return $query->whereKey($user->departemen_id ?? 0);
    }

    public static function proposals(User $user): Builder
    {
        return Proposal::whereIn('departemen_id', self::departments($user)->select('id'));
    }

    public static function viewProposal(User $user, Proposal $proposal): void
    {
        abort_unless(self::departments($user)->whereKey($proposal->departemen_id)->exists(), 403);
    }

    public static function editProposal(User $user, Proposal $proposal): void
    {
        self::viewProposal($user, $proposal);
        abort_unless((int) $proposal->user_id === (int) $user->id, 403);
        abort_unless(in_array($proposal->status, ['diajukan', 'revisi'], true), 409, 'Proposal yang telah disetujui harus melalui revisi dan approval ulang.');
        abort_if($proposal->pencairan()->exists(), 409, 'Proposal dengan pencairan tidak dapat diubah.');
        if ($proposal->status === 'diajukan') {
            abort_if($proposal->approval_flows()->where('status', '!=', 'pending')->exists(), 409, 'Persetujuan sudah berjalan.');
        }
    }
}
