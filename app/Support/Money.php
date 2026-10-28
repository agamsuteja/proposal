<?php

namespace App\Support;

use Illuminate\Validation\ValidationException;

final class Money
{
    // Canonical amounts or Indonesian thousands formatting; never remove arbitrary characters.
    public static function normalize(mixed $input): string
    {
        if (! is_string($input) && ! is_int($input) && ! is_float($input)) {
            throw ValidationException::withMessages(['jumlah' => 'Nominal harus berupa angka.']);
        }
        $value = trim((string) $input);
        $value = preg_replace('/^Rp\s*/i', '', $value);
        if (preg_match('/^\d{1,3}(\.\d{3})+(,\d{1,2})?$/D', $value)) {
            $value = str_replace(['.', ','], ['', '.'], $value);
        } elseif (preg_match('/^\d+,\d{1,2}$/D', $value)) {
            $value = str_replace(',', '.', $value);
        }
        if (! preg_match('/^\d{1,13}(\.\d{1,2})?$/D', $value)) {
            throw ValidationException::withMessages(['jumlah' => 'Nominal harus angka positif, maksimal dua angka desimal.']);
        }

        return $value;
    }
}
