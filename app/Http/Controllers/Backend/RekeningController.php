<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\PencairanDana;
use App\Models\PengembalianDana;
use App\Models\Rekening;
use App\Services\DepartmentBudgetService as Money;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekeningController extends Controller
{
    public function index()
    {
        return view('backend.rekening.index', ['rekenings' => Rekening::orderBy('nama_rekening')->paginate(10)]);
    }

    public function create()
    {
        return view('backend.rekening.create');
    }

    public function edit(Rekening $rekening)
    {
        return view('backend.rekening.edit', compact('rekening'));
    }

    private function data(Request $request): array
    {
        return $request->validate(['nama_rekening' => 'required|string|max:100', 'saldo' => 'required|numeric|decimal:0,2|min:0|max:9999999999999.99',
            'nomor_rekening' => 'nullable|string|max:100', 'bank' => 'nullable|string|max:100', 'keterangan' => 'nullable|string|max:5000', 'alasan' => 'nullable|string|min:5|max:1000']);
    }

    public function store(Request $request)
    {
        $data = $this->data($request);
        DB::transaction(fn () => Rekening::create(collect($data)->except('alasan')->all()));

        return redirect()->route('admin.rekening.index')->with('success', 'Rekening ditambahkan.');
    }

    public function update(Request $request, Rekening $rekening)
    {
        $data = $this->data($request);
        DB::transaction(function () use ($rekening, $data) {
            $account = Rekening::lockForUpdate()->findOrFail($rekening->id);
            $before = (string) $account->saldo;
            if (Money::cents($before) !== Money::cents($data['saldo']) && empty($data['alasan'])) {
                throw ValidationException::withMessages(['alasan' => 'Perubahan saldo harus disertai alasan rekonsiliasi.']);
            }
            $account->update(collect($data)->except('alasan')->all());
            if (Money::cents($before) !== Money::cents($data['saldo'])) {
                ActivityLog::create(['user_id' => auth()->id(), 'action' => 'account.adjustment', 'subject_type' => 'rekening', 'subject_id' => (string) $account->id, 'before' => ['saldo' => $before], 'after' => ['saldo' => $data['saldo'], 'alasan' => $data['alasan']]]);
            }
        }, 3);

        return redirect()->route('admin.rekening.index')->with('success', 'Rekening diperbarui dan penyesuaian dicatat.');
    }

    public function destroy(Rekening $rekening)
    {
        DB::transaction(function () use ($rekening) {
            $account = Rekening::lockForUpdate()->findOrFail($rekening->id);
            if (PencairanDana::where('rekening_id', $account->id)->exists() || PengembalianDana::where('rekening_id', $account->id)->exists()) {
                throw ValidationException::withMessages(['rekening' => 'Rekening dengan riwayat transaksi tidak dapat dihapus.']);
            }
            $account->delete();
        }, 3);

        return back()->with('success', 'Rekening dihapus.');
    }
}
