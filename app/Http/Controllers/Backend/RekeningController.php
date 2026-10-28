<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\GroupFunding;
use App\Models\PencairanDana;
use App\Models\PengembalianDana;
use App\Models\Rekening;
use App\Services\DepartmentBudgetService as Money;
use App\Services\GroupFundingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RekeningController extends Controller
{
    public function index()
    {
        $rekenings = Rekening::orderBy('nama_rekening')->paginate(10);
        foreach ($rekenings as $account) {
            $account->committed_cents = app(GroupFundingService::class)->committed($account->id);
            $account->free_cents = Money::cents($account->saldo ?? 0) - $account->committed_cents;
        }

        return view('backend.rekening.index', compact('rekenings'));
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
            app(GroupFundingService::class)->lock();
            $account = Rekening::lockForUpdate()->findOrFail($rekening->id);
            $before = (string) ($account->saldo ?? '0');
            if (Money::cents($before) !== Money::cents($data['saldo']) && empty($data['alasan'])) {
                throw ValidationException::withMessages(['alasan' => 'Perubahan saldo harus disertai alasan rekonsiliasi.']);
            }
            $account->update(collect($data)->except('alasan')->all());
            app(GroupFundingService::class)->assertBacked($account->id);
            if (Money::cents($before) !== Money::cents($data['saldo'])) {
                ActivityLog::create(['user_id' => auth()->id(), 'action' => 'account.adjustment', 'subject_type' => 'rekening', 'subject_id' => (string) $account->id, 'before' => ['saldo' => $before], 'after' => ['saldo' => $data['saldo'], 'alasan' => $data['alasan']]]);
            }
        }, 3);

        return redirect()->route('admin.rekening.index')->with('success', 'Rekening diperbarui dan penyesuaian dicatat.');
    }

    public function destroy(Rekening $rekening)
    {
        DB::transaction(function () use ($rekening) {
            app(GroupFundingService::class)->lock();
            $account = Rekening::lockForUpdate()->findOrFail($rekening->id);
            if (GroupFunding::where('rekening_id', $account->id)->exists()) {
                throw ValidationException::withMessages(['rekening' => 'Rekening masih terkait pendanaan grup dan tidak dapat dihapus.']);
            }
            if (PencairanDana::where('rekening_id', $account->id)->exists() || PengembalianDana::where('rekening_id', $account->id)->exists()) {
                throw ValidationException::withMessages(['rekening' => 'Rekening dengan riwayat transaksi tidak dapat dihapus.']);
            }
            $account->delete();
        }, 3);

        return back()->with('success', 'Rekening dihapus.');
    }
}
