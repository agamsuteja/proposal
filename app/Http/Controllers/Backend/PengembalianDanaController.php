<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\PengembalianDana;
use App\Models\User;
use App\Models\Rekening;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Proposal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\Grup;



class PengembalianDanaController extends Controller
{

    public function index(Request $request)
    {
        $query = PengembalianDana::with(['proposal.user', 'rekening']);

        $grups = Grup::orderBy('nama_grup')->get();

        $query = PengembalianDana::with(['proposal.user.grup','rekening']);

        if ($request->grup_id) {
            $query->whereHas('proposal.user', function ($q) use ($request) {
                $q->where('grup_id', $request->grup_id);
            });
        }
        


        // Filter status
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // Filter tanggal (untuk laporan)
        if ($request->tanggal_awal && $request->tanggal_akhir) {
            $query->whereBetween('created_at', [
                $request->tanggal_awal . ' 00:00:00',
                $request->tanggal_akhir . ' 23:59:59'
            ]);
        }

        $data = $query->latest()->paginate(10);

        // Hitung total laporan
        $totalDiterima = (clone $query)
            ->where('status', 'diterima')
            ->sum('jumlah');




        $rekening = [];

        if (auth()->user()->role === 'keuangan') {
            $rekening = Rekening::orderBy('nama_rekening')->get();
        }


        $rekening = Rekening::orderBy('nama_rekening')->get();

        return view('backend.pengembalian_dana.index', compact(
            'data',
            'totalDiterima',
            'grups'
        ));
        
        
    }



    /**
     * Form pengembalian dana
     */
    public function create($proposalId)
    {
        $proposal = Proposal::with('rekening')->findOrFail($proposalId);

        return view('pengembalian_dana.create', compact('proposal'));
    }

    /**
     * Simpan pengajuan pengembalian dana
     */
    public function store(Request $request)
    {
        $request->validate([
            'proposal_id' => 'required|exists:proposal,id',
            'jumlah' => 'required|numeric|min:1',
            'metode' => 'required',
            'bukti' => 'required_if:metode,transfer|nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
            'catatan' => 'nullable|string',
        ]);

        $user = Auth::user();

        DB::transaction(function () use ($request, $user) {

            $buktiPath = null;

            if ($request->hasFile('bukti')) {
                $file = $request->file('bukti');
                $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                $buktiPath = $file->storeAs('bukti_pengembalian', $filename, 'public');
            }

            $proposal = Proposal::with([
                'pencairan',
                'pengembalianDana'
            ])->findOrFail($request->proposal_id);

            $totalCair = $proposal->pencairan->sum('jumlah_dana');

            $totalKembali = $proposal->pengembalianDana
                ->where('status', '!=', 'ditolak')
                ->sum('jumlah');

            $sisaDana = $totalCair - $totalKembali;

            if ($request->jumlah > $sisaDana) {
                throw new \Exception('Jumlah pengembalian melebihi sisa dana.');
            }

            $pengembalian = PengembalianDana::create([
                'proposal_id' => $request->proposal_id,
                'rekening_id' => null,
                'jumlah' => $request->jumlah,
                'metode' => $request->metode,
                'bukti' => $buktiPath,
                'catatan' => $request->catatan,
                'status' => 'pending',
                'tanggal_pengembalian' => now(),
            ]);

            // 🔥 PANGGIL NOTIF DI SINI
            $this->notifyKeuangan($proposal, $pengembalian, $user);

        });

        return back()->with(
            'success',
            'Pengembalian dana berhasil diajukan dan menunggu verifikasi keuangan.'
        );
    }


    private function notifyKeuangan($proposal, $pengembalian, $user)
    {
        $usersKeuangan = User::where('role', 'keuangan')->get();

        foreach ($usersKeuangan as $keuangan) {

            if (!$keuangan->nohp) continue;

            $pesan = \App\Services\WaTemplate::pengembalian($keuangan->name, $proposal->judul, $pengembalian->jumlah, $user->name, $pengembalian->catatan);

            $this->sendViaFonnte($keuangan->nohp, $pesan);
        }
    }


    


    public function approve(Request $request, $id)
    {
        if (auth()->user()->role !== 'keuangan') {
            abort(403, 'Anda tidak memiliki akses.');
        }

        $request->validate([
            'rekening_id' => 'required|exists:rekening,id'
        ]);

        DB::transaction(function () use ($id, $request) {

            $pengembalian = PengembalianDana::lockForUpdate()->findOrFail($id);

            if ($pengembalian->status !== 'pending') {
                throw new \Exception('Data sudah diproses.');
            }

            \App\Models\Departemen::whereKey($pengembalian->proposal->departemen_id)->lockForUpdate()->firstOrFail();
            $rekening = Rekening::lockForUpdate()->findOrFail($request->rekening_id);

            // Tambah saldo rekening
            $rekening->increment('saldo', $pengembalian->jumlah);

            // Update pengembalian
            $pengembalian->update([
                'status' => 'diterima',
                'rekening_id' => $rekening->id
            ]);
        });

        return back()->with('success', 'Pengembalian dana berhasil disetujui.');
    }





    public function reject($id)
    {
        // 🔐 Cek role dulu
        if (auth()->user()->role !== 'keuangan') {
            abort(403, 'Anda tidak memiliki akses.');
        }

        DB::transaction(function () use ($id) {
            $pengembalian = PengembalianDana::lockForUpdate()->findOrFail($id);
            if ($pengembalian->status !== 'pending') {
                throw \Illuminate\Validation\ValidationException::withMessages(['pengembalian' => 'Data sudah diproses.']);
            }
            $pengembalian->update(['status' => 'ditolak']);
        });

        return back()->with('success', 'Pengembalian dana ditolak.');
    }


    

    public function exportPdf(Request $request)
    {
        $query = PengembalianDana::with(['proposal.user', 'rekening']);

        // FILTER STATUS
        if ($request->status) {
            $query->where('status', $request->status);
        }

        // FILTER TANGGAL
        if ($request->tanggal_awal && $request->tanggal_akhir) {
            $query->whereBetween('created_at', [
                $request->tanggal_awal,
                $request->tanggal_akhir
            ]);
        }

        $data = $query->latest()->get();

        $totalDiterima = $data
            ->where('status', 'diterima')
            ->sum('jumlah');

        $pdf = Pdf::loadView(
            'backend.pengembalian_dana.laporan_pdf',
            compact('data','totalDiterima','request')
        );

        return $pdf->download('laporan_pengembalian_dana.pdf');
    }



    public function show($id)
    {
        $data = PengembalianDana::with([
            'proposal.user',
            'rekening'
        ])->findOrFail($id);

        $rekening = Rekening::orderBy('nama_rekening')->get();

        return view(
            'backend.pengembalian_dana.show',
            compact('data','rekening')
        );
    }

    private function sendViaFonnte($nohp, $pesan)
    {
        return app(\App\Services\FonnteService::class)->send($nohp, $pesan);
    }




}
