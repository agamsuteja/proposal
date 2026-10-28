<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class FinancialOperation
{
    private function canonical(mixed $value): mixed
    {
        if ($value instanceof UploadedFile) {
            return ['file_sha256' => hash_file('sha256', $value->getRealPath())];
        }
        if (is_array($value)) {
            ksort($value);

            return array_map(fn ($v) => $this->canonical($v), $value);
        }

        return $value;
    }

    // Must be used within the same transaction as the financial write.
    public function replay(string $id, int $user, string $purpose, array $data): bool
    {
        if (DB::transactionLevel() === 0) {
            throw new \LogicException('Idempotency requires an active transaction');
        }
        $fingerprint = hash('sha256', json_encode($this->canonical($data), JSON_THROW_ON_ERROR));
        $new = DB::table('financial_operations')->insertOrIgnore(['id' => $id, 'user_id' => $user, 'purpose' => $purpose, 'fingerprint' => $fingerprint, 'created_at' => now(), 'updated_at' => now()]);
        $row = DB::table('financial_operations')->where('id', $id)->lockForUpdate()->first();
        abort_unless($row && (int) $row->user_id === $user && $row->purpose === $purpose && hash_equals($row->fingerprint, $fingerprint), 409, 'Kunci transaksi telah digunakan untuk data berbeda. Muat ulang formulir.');
        if (! $new) {
            abort_unless($row->completed_at, 409, 'Transaksi masih diproses.');

            return true;
        }

        return false;
    }

    public function complete(string $id): void
    {
        DB::table('financial_operations')->where('id', $id)->update(['completed_at' => now(), 'updated_at' => now()]);
    }
}
