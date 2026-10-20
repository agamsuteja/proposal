<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ApprovalProposal;
use App\Models\Departemen;
use App\Models\NotaPenggunaanProposal;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ProposalController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Query dasar
        $query = Proposal::with(['departemen', 'user', 'items', 'pencairan', 'approval_flows'])
            ->latest();

        // === Filter berdasarkan role ===
        if (in_array($user->role, ['karyawan', 'departemen_head'])) {
            // 🔹 Hanya lihat proposal dari departemen sendiri
            $query->where('departemen_id', $user->departemen_id);
        } elseif ($user->role === 'group_head') {
            // 🔹 Lihat proposal dari semua departemen dalam grup user
            $departemenIds = \App\Models\Departemen::where('grup_id', $user->grup_id)->pluck('id');
            $query->whereIn('departemen_id', $departemenIds);
        }
        // 🔹 Admin dan gembala bisa lihat semua proposal

        // $proposals = $query->get();
        $proposals = $query->paginate(5)->withQueryString();

        // 🔹 Hitung status cair dinamis (tanpa ubah kolom status di DB)
        foreach ($proposals as $p) {
            $totalCair = $p->pencairan->sum('jumlah_dana');
            $totalAnggaran = (float) $p->total_anggaran;

            if ($totalAnggaran <= 0) {
                $p->status_cair = 'Belum Dicairkan';
                continue;
            }

            if ($totalCair >= $totalAnggaran) {
                $p->status_cair = 'Dicairkan Penuh';
            } elseif ($totalCair > 0) {
                $p->status_cair = 'Dicairkan Sebagian';
            } else {
                $p->status_cair = 'Belum Dicairkan';
            }
        }

        return view('backend.proposal.index', compact('proposals', 'user'));
    }

    public function show($id)
    {
        $proposal = \App\Models\Proposal::with([
            'user',
            'departemen',
            'items',
            'pencairan.rekening',
            'approval_flows.approver',
            'nota_penggunaan',
            'pengembalianDana',  // tambahkan ini
        ])->findOrFail($id);

        // Total dana dicairkan
        $totalCair = $proposal->pencairan->sum('jumlah_dana');
        $totalDigunakan = $proposal->nota_penggunaan->sum('jumlah');
        $totalRefund = $proposal->pengembalianDana->sum('jumlah');

        $sisaDana = $totalCair - $totalDigunakan - $totalRefund;

        return view('backend.proposal.show', compact(
            'proposal',
            'totalCair',
            'totalDigunakan',
            'totalRefund',
            'sisaDana'
        ));
    }

    public function create()
    {
        $user = Auth::user();

        // Jika user punya role 'karyawan', otomatis pakai departemen dia
        if ($user->role === 'karyawan' && $user->departemen_id) {
            $departemen = Departemen::find($user->departemen_id);
            return view('backend.proposal.create', compact('departemen'));
        }

        // Jika admin atau role lain, tampilkan dropdown
        $departemens = Departemen::orderBy('nama_departemen')->get();
        return view('backend.proposal.create', compact('departemens'));
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        // Tentukan departemen
        $departemenId = $user->role === 'karyawan'
            ? $user->departemen_id
            : $request->departemen_id;

        // 🔴 DEBUG: Log data yang diterima
        \Log::info('=== STORE PROPOSAL - DATA DITERIMA ===');
        \Log::info('Jenis: ' . $request->jenis);
        \Log::info('Judul: ' . $request->judul);
        \Log::info('Items Count: ' . count($request->items ?? []));
        \Log::info('Items[0]:', ['value' => $request->items[0] ?? 'TIDAK ADA']);
        \Log::info('Total Manual: ' . $request->total_anggaran_manual);

        // 🔴 BERSIHKAN DATA DI AWAL SEBELUM VALIDASI
        // Bersihkan total anggaran manual
        if ($request->filled('total_anggaran_manual')) {
            $cleanAnggaran = preg_replace('/[^0-9]/', '', $request->total_anggaran_manual);
            $request->merge(['total_anggaran_manual' => $cleanAnggaran]);
            \Log::info('Total Anggaran Manual Dibersihkan: ' . $cleanAnggaran);
        }

        // ✅ PERBAIKAN KRUSIAL UNTUK MAINTENANCE: Filter items SEBELUM validasi
        if (in_array($request->jenis, ['barang', 'maintenance']) && $request->filled('items')) {
            $cleanItems = [];

            foreach ($request->items as $item) {
                // Ambil nilai dengan fallback aman
                $namaBarang = trim($item['nama_barang'] ?? '');
                $anggaranRaw = $item['anggaran'] ?? '';

                // Bersihkan anggaran dari format non-digit
                $anggaranClean = preg_replace('/[^0-9]/', '', $anggaranRaw);

                // ✅ HANYA SIMPAN ITEM YANG VALID (nama_barang & anggaran terisi)
                if (!empty($namaBarang) && !empty($anggaranClean) && $anggaranClean != '0') {
                    $cleanItems[] = [
                        'nama_barang' => $namaBarang,
                        'merk_tipe' => trim($item['merk_tipe'] ?? ''),
                        'jumlah' => trim($item['jumlah'] ?? ''),
                        'tujuan_pembelian' => trim($item['tujuan_pembelian'] ?? ''),
                        'anggaran' => $anggaranClean,
                    ];
                    \Log::info("✓ Item valid: {$namaBarang} - Rp {$anggaranClean}");
                } else {
                    \Log::warning("✗ Item di-SKIP: nama_barang='{$namaBarang}', anggaran='{$anggaranClean}'");
                }
            }

            // ✅ GANTI REQUEST DENGAN DATA YANG SUDAH DIBERSIHKAN
            $request->merge(['items' => $cleanItems]);
            \Log::info('Total item valid setelah filtering: ' . count($cleanItems));

            // ✅ VALIDASI: Minimal 1 item valid untuk barang/maintenance
            if (count($cleanItems) === 0) {
                \Log::error('❌ TIDAK ADA ITEM VALID!');
                return back()->withErrors([
                    'items' => 'Minimal harus ada 1 item yang diisi dengan lengkap (Nama Alat dan Anggaran)!'
                ])->withInput();
            }
        }

        // 🔹 Validasi dinamis - SEKARANG DATA SUDAH BERSIH!
        $rules = [
            'judul' => 'required|string|max:150',
            'tujuan' => 'nullable|string',
            'tanggal_pengajuan' => 'required|date',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'jenis' => 'required|in:barang,maintenance,acara',
        ];

        if ($user->role !== 'karyawan') {
            $rules['departemen_id'] = 'required|exists:departemen,id';
        }

        // Validasi berdasarkan jenis
        if (in_array($request->jenis, ['barang', 'maintenance'])) {
            $rules = array_merge($rules, [
                'items' => 'required|array|min:1',
                'items.*.nama_barang' => 'required|string|max:255',
                'items.*.anggaran' => 'required|numeric|min:1',
            ]);
        } elseif ($request->jenis === 'acara') {
            $rules['total_anggaran_manual'] = 'required|numeric|min:1';
        }

        // 🔴 Custom error messages
        $messages = [
            'items.required' => 'Minimal harus ada 1 item yang diisi!',
            'items.min' => 'Minimal harus ada 1 item yang diisi!',
            'items.*.nama_barang.required' => 'Nama barang/alat wajib diisi!',
            'items.*.anggaran.required' => 'Anggaran wajib diisi!',
            'items.*.anggaran.numeric' => 'Anggaran harus berupa angka!',
            'items.*.anggaran.min' => 'Anggaran minimal Rp 1!',
            'total_anggaran_manual.required' => 'Total anggaran wajib diisi!',
            'total_anggaran_manual.numeric' => 'Total anggaran harus berupa angka!',
            'total_anggaran_manual.min' => 'Total anggaran minimal Rp 1!',
        ];

        $request->validate($rules, $messages);

        DB::transaction(function () use ($request, $departemenId, $user) {
            // Simpan lampiran jika ada
            $lampiranPath = $request->hasFile('lampiran')
                ? $request->file('lampiran')->store('proposal_lampiran', 'public')
                : null;

            // Hitung total
            if (in_array($request->jenis, ['barang', 'maintenance'])) {
                $total = collect($request->items)->sum('anggaran');
                \Log::info('Total dari Items: ' . $total);
            } else {
                $total = (int) $request->total_anggaran_manual;
                \Log::info('Total Manual: ' . $total);
            }

            // Simpan Proposal
            $proposal = Proposal::create([
                'user_id' => $user->id,
                'departemen_id' => $departemenId,
                'jenis' => $request->jenis,
                'judul' => $request->judul,
                'tujuan' => $request->tujuan,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
                'tanggal_acara' => $request->tanggal_acara ?? null,
                'lokasi_acara' => $request->lokasi_acara ?? null,
                'keterangan' => $request->keterangan ?? null,
                'total_anggaran' => $total,
                'lampiran' => $lampiranPath,
                'status' => 'diajukan',
            ]);

            \Log::info('Proposal berhasil disimpan dengan ID: ' . $proposal->id);

            // Jika barang/maintenance → simpan item
            if (in_array($request->jenis, ['barang', 'maintenance'])) {
                foreach ($request->items as $index => $item) {
                    \Log::info('Menyimpan Item ' . $index . ': ' . $item['nama_barang'] . ' - ' . $item['anggaran']);

                    ProposalItem::create([
                        'proposal_id' => $proposal->id,
                        'nama_barang' => $item['nama_barang'],
                        'merk_tipe' => $item['merk_tipe'] ?? null,
                        'jumlah' => $item['jumlah'] ?? null,
                        'tujuan_pembelian' => $item['tujuan_pembelian'] ?? null,
                        'anggaran' => $item['anggaran'],
                    ]);
                }
                \Log::info('Total ' . count($request->items) . ' item berhasil disimpan');
            }

            // 🔹 Alur approval
            $departemen = Departemen::with('grup')->find($departemenId);
            $departemenHead = User::where('role', 'departemen_head')
                ->where('departemen_id', $departemenId)
                ->first();
            $groupHead = $departemen && $departemen->grup_id
                ? User::where('role', 'group_head')
                    ->where('grup_id', $departemen->grup_id)
                    ->first()
                : null;
            $seniorPastor = User::where('role', 'gembala')->first();

            $urutan = 1;

            // 🔹 Buat approval flow untuk Departemen Head
            if ($departemenHead) {
                ApprovalProposal::create([
                    'proposal_id' => $proposal->id,
                    'approver_id' => $departemenHead->id,
                    'tahap' => 'departemen_head',
                    'status' => 'pending',
                    'urutan' => $urutan++,
                ]);
                \Log::info('Approval Departemen Head dibuat untuk: ' . $departemenHead->name);
            }

            // 🔹 Buat approval flow untuk Group Head
            if ($groupHead) {
                ApprovalProposal::create([
                    'proposal_id' => $proposal->id,
                    'approver_id' => $groupHead->id,
                    'tahap' => 'group_head',
                    'status' => 'pending',
                    'urutan' => $urutan++,
                ]);
                \Log::info('Approval Group Head dibuat untuk: ' . $groupHead->name);
            }

            // 🔹 Buat approval flow untuk Gembala/Senior Pastor
            if ($seniorPastor) {
                ApprovalProposal::create([
                    'proposal_id' => $proposal->id,
                    'approver_id' => $seniorPastor->id,
                    'tahap' => 'gembala',
                    'status' => 'pending',
                    'urutan' => $urutan++,
                ]);
                \Log::info('Approval Gembala dibuat untuk: ' . $seniorPastor->name);
            }

            // 🔔 Kirim notifikasi WA ke Departemen Head saat proposal baru diajukan
            if ($departemenHead && $departemenHead->nohp) {
                $pesan = \App\Services\WaTemplate::proposalBaru($departemenHead->name, $departemen->nama_departemen, $proposal->judul, $proposal->total_anggaran);

                $this->sendViaFonnte($departemenHead->nohp, $pesan);
                \Log::info('Notifikasi WA dikirim ke: ' . $departemenHead->nohp);
            }
        });

        \Log::info('=== PROPOSAL BERHASIL DIBUAT ===');

        return redirect()
            ->route('admin.proposal.index')
            ->with('success', 'Proposal berhasil dibuat dan dikirim untuk persetujuan.');
    }

    public function uploadNota(Request $request, $id)
    {
        $proposal = Proposal::findOrFail($id);

        // Pastikan hanya pemilik proposal
        if (Auth::id() !== $proposal->user_id) {
            return back()->with('error', 'Anda tidak memiliki izin untuk mengunggah nota.');
        }

        // Pastikan status dicairkan
        if (!in_array($proposal->status, ['dicairkan', 'dicairkan_sebagian'])) {
            return back()->with('error', 'Hanya proposal yang sudah dicairkan yang dapat mengunggah nota.');
        }

        // Validasi dynamic array
        $request->validate([
            'nota' => 'required|array|min:1',
            'nota.*.file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:2048',
            'nota.*.jumlah' => 'required|numeric|min:1',
            'nota.*.keterangan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            foreach ($request->nota as $n) {
                $path = $n['file']->store('nota_penggunaan', 'public');

                NotaPenggunaanProposal::create([
                    'proposal_id' => $proposal->id,
                    'file_nota' => $path,
                    'jumlah' => $n['jumlah'],
                    'keterangan' => $n['keterangan'] ?? null,
                ]);
            }

            DB::commit();

            return back()->with('success', 'Semua nota berhasil diunggah.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Upload gagal. Silakan coba lagi.');
        }
    }

    public function edit($id)
    {
        $proposal = Proposal::with('items')->findOrFail($id);

        if (in_array($proposal->status, ['dicairkan', 'dicairkan_sebagian'])) {
            return redirect()
                ->route('admin.proposal.index')
                ->with('error', 'Proposal yang sudah dicairkan tidak dapat diedit.');
        }

        $departemen = Departemen::orderBy('nama_departemen')->get();
        return view('backend.proposal.edit', compact('proposal', 'departemen'));
    }

    public function update(Request $request, $id)
    {
        $proposal = Proposal::with('approval_flows', 'departemen')->findOrFail($id);

        if ($proposal->status === 'dicairkan') {
            return redirect()
                ->route('admin.proposal.index')
                ->with('error', 'Proposal yang sudah dicairkan tidak dapat diubah.');
        }

        // 🔹 Hanya karyawan yang boleh revisi
        if ($proposal->status === 'revisi' && Auth::user()->role !== 'karyawan') {
            return back()->with('error', 'Hanya karyawan yang dapat memperbarui proposal revisi.');
        }

        /**
         * 🔹 Bersihkan format rupiah sebelum validasi
         * Hapus semua karakter selain angka agar lolos numeric
         */
        if ($request->filled('total_anggaran_manual')) {
            $request->merge([
                'total_anggaran_manual' => preg_replace('/[^0-9]/', '', $request->total_anggaran_manual),
            ]);
        }

        if ($request->filled('items')) {
            $items = $request->items;
            foreach ($items as &$item) {
                if (isset($item['anggaran'])) {
                    $item['anggaran'] = preg_replace('/[^0-9]/', '', $item['anggaran']);
                }
            }
            $request->merge(['items' => $items]);
        }

        $rules = [
            'judul' => 'required|string|max:150',
            'tujuan' => 'nullable|string',
            'tanggal_pengajuan' => 'required|date',
            'lampiran' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048',
        ];

        if (Auth::user()->role !== 'karyawan') {
            $rules['departemen_id'] = 'required|exists:departemen,id';
        }

        if (in_array($proposal->jenis, ['barang', 'maintenance'])) {
            $rules = array_merge($rules, [
                'items' => 'required|array|min:1',
                'items.*.nama_barang' => 'required|string',
                'items.*.anggaran' => 'required|numeric|min:1',
            ]);
        } else {
            $rules = array_merge($rules, [
                'total_anggaran_manual' => 'required|numeric|min:1',
            ]);
        }

        $request->validate($rules);

        DB::transaction(function () use ($request, $proposal) {
            $lampiranPath = $proposal->lampiran;
            if ($request->hasFile('lampiran')) {
                if ($lampiranPath)
                    Storage::disk('public')->delete($lampiranPath);
                $lampiranPath = $request->file('lampiran')->store('proposal_lampiran', 'public');
            }

            // 🔹 Hitung total anggaran sesuai jenis proposal
            $total = in_array($proposal->jenis, ['barang', 'maintenance'])
                ? collect($request->items)->sum('anggaran')
                : (int) $request->total_anggaran_manual;

            $proposal->update([
                'departemen_id' => Auth::user()->role === 'karyawan'
                    ? $proposal->departemen_id
                    : $request->departemen_id,
                'judul' => $request->judul,
                'tujuan' => $request->tujuan,
                'total_anggaran' => $total,
                'tanggal_pengajuan' => $request->tanggal_pengajuan,
                'lampiran' => $lampiranPath,
            ]);

            // 🔹 Hapus semua item lama dan simpan ulang
            if (in_array($proposal->jenis, ['barang', 'maintenance'])) {
                $proposal->items()->delete();

                foreach ($request->items as $item) {
                    ProposalItem::create([
                        'proposal_id' => $proposal->id,
                        'nama_barang' => $item['nama_barang'],
                        'merk_tipe' => $item['merk_tipe'] ?? null,
                        'jumlah' => $item['jumlah'] ?? null,
                        'tujuan_pembelian' => $item['tujuan_pembelian'] ?? null,
                        'anggaran' => $item['anggaran'],
                    ]);
                }
            }

            /**
             * =============================
             * LOGIKA KHUSUS UNTUK PROPOSAL REVISI
             * =============================
             */
            if ($proposal->status === 'revisi') {
                // Ambil flow yang terakhir menolak
                $rejectedFlow = $proposal
                    ->approval_flows
                    ->where('status', 'ditolak')
                    ->sortByDesc('urutan')
                    ->first();

                if ($rejectedFlow) {
                    foreach ($proposal->approval_flows as $flow) {
                        // Reset hanya tahap yang urutannya >= tahap penolak
                        if ($flow->urutan >= $rejectedFlow->urutan) {
                            $flow->update([
                                'status' => 'pending',
                                'catatan' => null,
                                'tanggal_approve' => null,
                            ]);
                        }
                    }
                }

                // Ubah status proposal menjadi diajukan ulang
                $proposal->update(['status' => 'diajukan']);

                // 🔔 Cari tahap yang sekarang pending pertama
                $nextFlow = $proposal
                    ->approval_flows()
                    ->where('status', 'pending')
                    ->orderBy('urutan')
                    ->first();

                if ($nextFlow && $nextFlow->approver && $nextFlow->approver->nohp) {
                    $pesan = \App\Services\WaTemplate::proposalRevisi($nextFlow->approver->name, $proposal->judul);

                    $this->sendViaFonnte($nextFlow->approver->nohp, $pesan);
                }
            }
        });

        return redirect()
            ->route('admin.proposal.index')
            ->with('success', 'Proposal berhasil diperbarui.');
    }

    /**
     * Kirim WhatsApp via Fonnte (lihat App\Services\FonnteService)
     */
    private function sendViaFonnte($nohp, $pesan)
    {
        return app(\App\Services\FonnteService::class)->send($nohp, $pesan);
    }

    public function destroy($id)
    {
        $proposal = Proposal::findOrFail($id);

        if (in_array($proposal->status, ['dicairkan', 'dicairkan_sebagian'])) {
            return redirect()
                ->route('admin.proposal.index')
                ->with('error', 'Proposal yang sudah dicairkan tidak dapat dihapus.');
        }

        if ($proposal->lampiran) {
            Storage::disk('public')->delete($proposal->lampiran);
        }

        $proposal->delete();

        return redirect()
            ->route('admin.proposal.index')
            ->with('success', 'Proposal berhasil dihapus.');
    }
}
