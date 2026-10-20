<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PencairanDana;
use App\Models\Proposal;
use App\Models\Rekening;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class PencairanDanaController extends Controller
{
    public function index()
    {
        $proposals = Proposal::with('departemen', 'user', 'pencairan')
            ->whereIn('status', ['disetujui_senior_pastor', 'dicairkan_sebagian'])
            ->get()
            ->filter(function ($p) {
                $totalCair = $p->pencairan->sum('jumlah_dana');
                return $totalCair < $p->total_anggaran;
            });

        $pencairans = PencairanDana::with(['proposal.nota_penggunaan', 'rekening'])
            ->latest()
            ->get();
        
        $rekenings = Rekening::orderBy('nama_rekening')->get();

        return view('backend.pencairan.index', compact('proposals', 'pencairans', 'rekenings'));
    }

    public function show($id)
    {
        $proposal = Proposal::with([
            'user',
            'departemen',
            'items',
            'approval_flows.approver',
            'nota_penggunaan',
        ])->findOrFail($id);

        return view('backend.pencairan.show', compact('proposal'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'proposal_id' => 'required|exists:proposal,id',
            'rekening_id' => 'required|exists:rekening,id',
            'jumlah_dana' => 'required|numeric|min:1',
            'metode' => 'required|in:transfer,cash',
            'catatan' => 'required|string|max:500', // ✅ WAJIB ADA CATATAN
            'bukti_transfer' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ]);
    
        DB::beginTransaction();
        try {
            $proposal = Proposal::with('user', 'pencairan')->findOrFail($request->proposal_id);
            $rekening = Rekening::findOrFail($request->rekening_id);
    
            // 💰 Total yang sudah dicairkan
            $totalSudahCair = $proposal->pencairan->sum('jumlah_dana');
            $totalSetelahCair = $totalSudahCair + $request->jumlah_dana;
    
            // 💡 Cegah pencairan lebih besar dari sisa dana
            if ($totalSetelahCair > $proposal->total_anggaran) {
                return back()->with('error', 'Jumlah pencairan melebihi total anggaran proposal!');
            }
    
            // 💡 Cegah saldo rekening minus
            if ($rekening->saldo < $request->jumlah_dana) {
                return back()->with('error', 'Saldo rekening tidak mencukupi!');
            }
    
            // 📎 Upload bukti transfer (jika metode transfer)
            $buktiPath = null;
            if ($request->metode === 'transfer' && $request->hasFile('bukti_transfer')) {
                $buktiPath = $request->file('bukti_transfer')->store('bukti_transfer', 'public');
            }
    
            // 💳 Kurangi saldo rekening
            $rekening->decrement('saldo', $request->jumlah_dana);
    
            // 🧾 Simpan data pencairan
            $pencairan = PencairanDana::create([
                'proposal_id'    => $proposal->id,
                'rekening_id'    => $rekening->id,
                'jumlah_dana'    => $request->jumlah_dana,
                'metode'         => $request->metode,
                'catatan'        => $request->catatan, // ✅ SIMPAN CATATAN
                'bukti_transfer' => $buktiPath,
            ]);
    
            // 🔄 Update status proposal
            if ($totalSetelahCair < $proposal->total_anggaran) {
                $proposal->update(['status' => 'dicairkan_sebagian']);
            } else {
                $proposal->update(['status' => 'dicairkan']);
            }
    
            // 💬 Kirim notifikasi WA ke pemohon (dengan catatan lengkap)
            $this->kirimNotifCairKePemohon(
                $proposal,
                $request->catatan, // ✅ KIRIM CATATAN KE PEMOHON
                $request->jumlah_dana,
                $totalSetelahCair
            );
    
            DB::commit();
            return back()->with('success', 'Dana berhasil dicairkan dan notifikasi terkirim ke pemohon!');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error pencairan dana: ' . $e->getMessage());
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
    
    /**
     * ✅ DIPERBAIKI: Kirim notifikasi ke pemohon dengan catatan pencairan
     * Dikirim via Fonnte
     */
    private function kirimNotifCairKePemohon($proposal, $catatan = null, $jumlahCair = 0, $totalCair = 0)
    {
        try {
            $pemohon = $proposal->user;
            if (!$pemohon || !$pemohon->nohp) {
                \Log::warning("Tidak bisa kirim notifikasi: pemohon tidak memiliki nohp");
                return;
            }

            $judul = $proposal->judul;
            $totalAnggaran = number_format($proposal->total_anggaran, 0, ',', '.');
            $jumlahCairFmt = number_format($jumlahCair, 0, ',', '.');
            $totalCairFmt = number_format($totalCair, 0, ',', '.');

            $status = ($proposal->status === 'dicairkan_sebagian')
                ? "Dana dicairkan sebagian"
                : "Dana telah dicairkan penuh";

            // ✅ PESAN LENGKAP DENGAN CATATAN DARI KEUANGAN
            $pesan = \App\Services\WaTemplate::pencairan($pemohon->name, $judul, $status, $jumlahCairFmt, $totalCairFmt, $totalAnggaran, $catatan);

            \Log::info("Mengirim notifikasi pencairan ke pemohon: {$pemohon->name} ({$pemohon->nohp})");
            \Log::info("Catatan pencairan: " . ($catatan ?? 'Tidak ada'));
            
            // Kirim via Fonnte
            $this->sendViaFonnte($pemohon->nohp, $pesan);
            
        } catch (\Exception $e) {
            \Log::error('Gagal kirim notifikasi pencairan ke pemohon: ' . $e->getMessage());
        }
    }

    /**
     * Kirim WhatsApp via Fonnte (lihat App\Services\FonnteService)
     */
    private function sendViaFonnte($nohp, $pesan)
    {
        return app(\App\Services\FonnteService::class)->send($nohp, $pesan);
    }
}