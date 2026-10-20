@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   EDIT PROPOSAL — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.ep-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.ep-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.ep-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.ep-back { background:rgba(255,255,255,.15); color:#fff; border:0.5px solid rgba(255,255,255,.3); border-radius:8px; padding:7px 16px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; text-decoration:none; transition:opacity .15s }
.ep-back:hover { opacity:.85; text-decoration:none; color:#fff }

/* ── Cards ── */
.ep-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; margin-bottom:1rem; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.ep-card-hd { padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; background:#fafafa; display:flex; align-items:center; gap:7px; font-size:13px; font-weight:700; color:#111 }
.ep-card-hd .cdot { width:7px; height:7px; border-radius:50%; background:#7c3aed; flex-shrink:0 }
.ep-card-bd { padding:1.25rem }

/* ── Field layout ── */
.ep-grid-2 { display:grid; grid-template-columns:1fr 1fr; gap:.875rem }
.ep-grid-3 { display:grid; grid-template-columns:1fr 1fr 1fr; gap:.875rem }
@media(max-width:640px) { .ep-grid-2, .ep-grid-3 { grid-template-columns:1fr } }
.ep-group { margin-bottom:.875rem }
.ep-group:last-child { margin-bottom:0 }

/* ── Labels & inputs ── */
.ep-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; display:block; margin-bottom:4px }
.ep-label .opt { font-weight:400; text-transform:none; letter-spacing:0 }
.ep-input, .ep-select, .ep-textarea { width:100%; box-sizing:border-box; font-size:13px; padding:8px 11px; border-radius:8px; border:0.5px solid #d1d5db; background:#f9fafb; color:#111; font-family:inherit; transition:border-color .12s,box-shadow .12s; appearance:none; -webkit-appearance:none }
.ep-input:focus, .ep-select:focus, .ep-textarea:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1); background:#fff }
.ep-input[readonly] { background:#f3f4f6; color:#9ca3af; cursor:not-allowed }
.ep-textarea { resize:vertical; min-height:80px }
.ep-select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.ep-lampiran-link { font-size:11.5px; display:inline-flex; align-items:center; gap:4px; color:#5b21b6; margin-top:5px; text-decoration:none; font-weight:700 }
.ep-lampiran-link:hover { text-decoration:underline; color:#4c1d95 }

/* ── Jenis pills ── */
.ep-jenis-pills { display:flex; gap:.5rem; flex-wrap:wrap }
.ep-pill { padding:8px 18px; border-radius:9px; border:0.5px solid #d1d5db; font-size:12.5px; font-weight:700; cursor:pointer; transition:all .12s; color:#6b7280; background:#fff; display:inline-flex; align-items:center; gap:6px; font-family:inherit }
.ep-pill:hover { border-color:#7c3aed; color:#7c3aed }
.ep-pill.active-barang { background:#dbeafe; color:#1e40af; border-color:#93c5fd }
.ep-pill.active-acara  { background:#fef3c7; color:#92400e; border-color:#fcd34d }
.ep-pill.active-maint  { background:#ccfbf1; color:#0f766e; border-color:#5eead4 }

/* ── Table edit ── */
.ep-table-wrap { overflow-x:auto }
.ep-table { width:100%; border-collapse:collapse; font-size:12.5px; min-width:560px }
.ep-table thead th { background:#f9fafb; color:#6b7280; font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; padding:8px 10px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.ep-table tbody tr { border-bottom:0.5px solid #f3f4f6 }
.ep-table tbody tr:last-child { border-bottom:none }
.ep-table tbody td { padding:6px 8px; vertical-align:middle }
.ep-table tbody td .ep-cell { width:100%; box-sizing:border-box; font-size:12.5px; padding:7px 9px; border-radius:7px; border:0.5px solid #e5e7eb; background:#f9fafb; color:#111; font-family:inherit; transition:border-color .12s }
.ep-table tbody td .ep-cell:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 2px rgba(124,58,237,.1); background:#fff }
.ep-table tbody td .ep-cell.money { text-align:right }
.ep-del-btn { width:28px; height:28px; border-radius:7px; border:none; background:#fee2e2; color:#dc2626; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; flex-shrink:0; transition:background .12s }
.ep-del-btn:hover { background:#fecaca }

/* ── Table footer bar ── */
.ep-table-foot { padding:.875rem 1.25rem; border-top:0.5px solid #f3f4f6; display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:.5rem }
.ep-add-btn { background:#fff; border:0.5px solid #d1d5db; color:#374151; border-radius:8px; padding:7px 14px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:5px; font-family:inherit; transition:background .12s }
.ep-add-btn:hover { background:#f9fafb; border-color:#7c3aed; color:#7c3aed }
.ep-total-box { background:linear-gradient(135deg,#7c3aed,#4c1d95); border-radius:10px; padding:.625rem 1.1rem; display:flex; align-items:center; gap:.75rem }
.ep-total-label { font-size:10px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,.75) }
.ep-total-amount { font-size:14px; font-weight:800; color:#fff; font-family:'Nunito',sans-serif }

/* ── Mobile item cards ── */
.ep-mob-item { background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:10px; padding:.875rem 1rem; margin-bottom:.625rem }
.ep-mob-row { margin-bottom:.625rem }
.ep-mob-row:last-child { margin-bottom:0 }
.ep-mob-2 { display:grid; grid-template-columns:1fr 1fr; gap:.5rem }

/* ── Submit bar ── */
.ep-submit-bar { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; padding:1rem 1.25rem; display:flex; justify-content:flex-end; align-items:center; gap:.75rem; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.ep-save-btn { background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff; border:none; border-radius:9px; padding:10px 28px; font-size:13.5px; font-weight:700; cursor:pointer; display:flex; align-items:center; gap:7px; font-family:inherit; transition:opacity .15s }
.ep-save-btn:hover { opacity:.9 }
.ep-cancel-link { color:#6b7280; font-size:13px; font-weight:700; text-decoration:none; padding:8px 16px; border-radius:9px; border:0.5px solid #d1d5db; transition:background .12s }
.ep-cancel-link:hover { background:#f9fafb; color:#374151; text-decoration:none }

/* ── Error ── */
.ep-err-alert { background:#fef2f2; border:0.5px solid #fecaca; border-left:3px solid #ef4444; border-radius:10px; padding:.875rem 1.1rem; margin-bottom:1rem }
.ep-err-alert strong { font-size:12.5px; color:#991b1b; display:block; margin-bottom:.4rem }
.ep-err-alert ul { margin:0; padding-left:1.25rem }
.ep-err-alert li { font-size:12px; color:#b91c1c; margin-bottom:.2rem; line-height:1.5 }
.ep-field-err { font-size:11px; color:#dc2626; margin-top:3px }
.is-invalid { border-color:#dc2626 !important }

/* ── Mobile fixed save ── */
@media(max-width:767.98px) {
    .ep-submit-bar { position:fixed; bottom:0; left:0; right:0; border-radius:0; border-left:none; border-right:none; border-bottom:none; z-index:1000; box-shadow:0 -2px 12px rgba(0,0,0,.08) }
    .ep-wrap { padding-bottom:6rem }
    .ep-table-wrap { display:none }
    .ep-mob-list { display:block !important }
}
@media(min-width:768px) {
    .ep-mob-list { display:none }
}
</style>

<div class="ep-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="ep-topbar">
        <h1><i class="fas fa-edit"></i> Edit Proposal</h1>
        <a href="{{ route('admin.proposal.index') }}" class="ep-back">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- ═══ ERROR ALERT ═══ --}}
    @if($errors->any())
    <div class="ep-err-alert">
        <strong>Terdapat kesalahan pada form:</strong>
        <ul>
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.proposal.update', $proposal->id) }}" method="POST"
          enctype="multipart/form-data" id="ep-form">
        @csrf
        @method('PUT')

        {{-- ═══ INFORMASI UMUM ═══ --}}
        <div class="ep-card">
            <div class="ep-card-hd"><span class="cdot"></span> Informasi Umum</div>
            <div class="ep-card-bd">
                <div class="ep-grid-2 ep-group">
                    <div>
                        <label class="ep-label"><i class="fas fa-building fa-sm"></i> Departemen</label>
                        <select name="departemen_id" class="ep-select @error('departemen_id') is-invalid @enderror" required>
                            <option value="">— Pilih Departemen —</option>
                            @foreach($departemen as $d)
                                <option value="{{ $d->id }}" {{ $proposal->departemen_id == $d->id ? 'selected' : '' }}>
                                    {{ $d->nama_departemen }}
                                </option>
                            @endforeach
                        </select>
                        @error('departemen_id') <div class="ep-field-err">{{ $message }}</div> @enderror
                    </div>
                    <div>
                        <label class="ep-label"><i class="fas fa-calendar fa-sm"></i> Tanggal Pengajuan</label>
                        <input type="date" name="tanggal_pengajuan"
                               class="ep-input @error('tanggal_pengajuan') is-invalid @enderror"
                               value="{{ $proposal->tanggal_pengajuan }}" required>
                        @error('tanggal_pengajuan') <div class="ep-field-err">{{ $message }}</div> @enderror
                    </div>
                </div>

                <div class="ep-group">
                    <label class="ep-label"><i class="fas fa-file-alt fa-sm"></i> Judul Proposal</label>
                    <input type="text" name="judul"
                           class="ep-input @error('judul') is-invalid @enderror"
                           value="{{ old('judul', $proposal->judul) }}" required>
                    @error('judul') <div class="ep-field-err">{{ $message }}</div> @enderror
                </div>

                <div class="ep-group">
                    <label class="ep-label"><i class="fas fa-bullseye fa-sm"></i> Tujuan / Deskripsi</label>
                    <textarea name="tujuan" class="ep-textarea" rows="3">{{ old('tujuan', $proposal->tujuan) }}</textarea>
                </div>

                <div class="ep-group">
                    <label class="ep-label"><i class="fas fa-paperclip fa-sm"></i> Lampiran Baru <span class="opt">(opsional)</span></label>
                    <input type="file" name="lampiran" class="ep-input" accept=".pdf,.jpg,.jpeg,.png">
                    @if($proposal->lampiran)
                        <a href="{{ asset('storage/'.$proposal->lampiran) }}" target="_blank" class="ep-lampiran-link">
                            <i class="fas fa-eye"></i> Lihat lampiran lama
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- ═══ JENIS PROPOSAL ═══ --}}
        <div class="ep-card">
            <div class="ep-card-hd"><span class="cdot"></span> Jenis Proposal</div>
            <div class="ep-card-bd">
                <input type="hidden" name="jenis" id="jenisInput" value="{{ $proposal->jenis }}">
                <div class="ep-jenis-pills">
                    <button type="button" class="ep-pill {{ $proposal->jenis==='barang' ? 'active-barang' : '' }}"
                            onclick="setJenis('barang',this)">
                        <i class="fas fa-box"></i> Pengadaan Barang
                    </button>
                    <button type="button" class="ep-pill {{ $proposal->jenis==='acara' ? 'active-acara' : '' }}"
                            onclick="setJenis('acara',this)">
                        <i class="fas fa-calendar-alt"></i> Kegiatan / Acara
                    </button>
                    <button type="button" class="ep-pill {{ $proposal->jenis==='maintenance' ? 'active-maint' : '' }}"
                            onclick="setJenis('maintenance',this)">
                        <i class="fas fa-tools"></i> Maintenance
                    </button>
                </div>
            </div>
        </div>

        {{-- ═══ FORM ACARA ═══ --}}
        <div id="formAcara" class="{{ $proposal->jenis === 'acara' ? '' : 'd-none' }}">
            <div class="ep-card">
                <div class="ep-card-hd"><span class="cdot"></span> Detail Acara</div>
                <div class="ep-card-bd">
                    <div class="ep-grid-3 ep-group">
                        <div>
                            <label class="ep-label">Nama Acara</label>
                            <input type="text" name="judul_acara" class="ep-input" value="{{ old('judul_acara', $proposal->judul_acara) }}">
                        </div>
                        <div>
                            <label class="ep-label">Tanggal Acara</label>
                            <input type="date" name="tanggal_acara" class="ep-input" value="{{ old('tanggal_acara', $proposal->tanggal_acara) }}">
                        </div>
                        <div>
                            <label class="ep-label">Lokasi Acara</label>
                            <input type="text" name="lokasi_acara" class="ep-input" value="{{ old('lokasi_acara', $proposal->lokasi_acara) }}">
                        </div>
                    </div>
                    <div class="ep-group">
                        <label class="ep-label">Rincian Kebutuhan Dana</label>
                        <textarea name="keterangan" class="ep-textarea" rows="3">{{ old('keterangan', $proposal->keterangan) }}</textarea>
                    </div>
                    <div>
                        <label class="ep-label">Total Anggaran (Rp)</label>
                        <div style="display:flex;border:0.5px solid #d1d5db;border-radius:8px;overflow:hidden">
                            <span style="background:#f3f4f6;padding:8px 11px;font-size:12px;color:#6b7280;border-right:0.5px solid #d1d5db;white-space:nowrap">Rp</span>
                            <input type="text" id="totalManual" name="total_anggaran_manual"
                                   style="border:none;flex:1;padding:8px 11px;font-size:13px;background:#f9fafb;color:#111;font-family:inherit;text-align:right;outline:none"
                                   value="{{ number_format($proposal->total_anggaran, 0, ',', '.') }}"
                                   placeholder="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ═══ FORM BARANG ═══ --}}
        <div id="formBarang" class="{{ $proposal->jenis === 'barang' ? '' : 'd-none' }}">
            <div class="ep-card">
                <div class="ep-card-hd">
                    <span class="cdot"></span> Daftar Barang
                    <span id="barangCount" style="margin-left:auto;font-size:10.5px;background:#ede9fe;color:#5b21b6;border-radius:20px;padding:2px 10px;font-weight:700">
                        {{ $proposal->jenis==='barang' ? $proposal->items->count() : 0 }} item
                    </span>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="ep-table-wrap">
                    <table class="ep-table">
                        <thead>
                            <tr>
                                <th style="min-width:150px">Nama Barang</th>
                                <th style="min-width:120px">Merk / Tipe</th>
                                <th style="width:65px;text-align:center">Qty</th>
                                <th style="min-width:130px">Tujuan</th>
                                <th style="width:140px;text-align:right">Anggaran (Rp)</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="items_body">
                            @if($proposal->jenis === 'barang')
                            @foreach($proposal->items as $i => $item)
                            <tr>
                                <td><input type="text" name="items[{{ $i }}][nama_barang]" class="ep-cell" value="{{ $item->nama_barang }}" required></td>
                                <td><input type="text" name="items[{{ $i }}][merk_tipe]" class="ep-cell" value="{{ $item->merk_tipe }}"></td>
                                <td><input type="text" name="items[{{ $i }}][jumlah]" class="ep-cell" value="{{ $item->jumlah }}" style="text-align:center"></td>
                                <td><input type="text" name="items[{{ $i }}][tujuan_pembelian]" class="ep-cell" value="{{ $item->tujuan_pembelian }}"></td>
                                <td><input type="text" name="items[{{ $i }}][anggaran]" data-source="desktop" class="ep-cell money anggaran-input" value="{{ number_format($item->anggaran, 0, ',', '.') }}"></td>
                                <td><button type="button" class="ep-del-btn"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARDS --}}
                <div class="ep-mob-list" id="mob_barang_list" style="padding:.875rem 1rem;display:none">
                    @if($proposal->jenis === 'barang')
                    @foreach($proposal->items as $i => $item)
                    <div class="ep-mob-item">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.625rem">
                            <span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase">Item #{{ $i+1 }}</span>
                            <button type="button" class="ep-del-btn" style="width:24px;height:24px;font-size:11px"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="ep-mob-row">
                            <label class="ep-label">Nama Barang</label>
                            <input type="text" name="items[{{ $i }}][nama_barang]" class="ep-input" value="{{ $item->nama_barang }}" required>
                        </div>
                        <div class="ep-mob-2 ep-mob-row">
                            <div><label class="ep-label">Merk / Tipe</label>
                                <input type="text" name="items[{{ $i }}][merk_tipe]" class="ep-input" value="{{ $item->merk_tipe }}">
                            </div>
                            <div><label class="ep-label">Qty</label>
                                <input type="text" name="items[{{ $i }}][jumlah]" class="ep-input" value="{{ $item->jumlah }}">
                            </div>
                        </div>
                        <div class="ep-mob-row">
                            <label class="ep-label">Tujuan Pembelian</label>
                            <input type="text" name="items[{{ $i }}][tujuan_pembelian]" class="ep-input" value="{{ $item->tujuan_pembelian }}">
                        </div>
                        <div class="ep-mob-row">
                            <label class="ep-label">Anggaran (Rp)</label>
                            <input type="text" name="items[{{ $i }}][anggaran]" data-source="mobile" class="ep-input anggaran-input" value="{{ number_format($item->anggaran, 0, ',', '.') }}">
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

                <div class="ep-table-foot">
                    <button type="button" class="ep-add-btn" id="addRow">
                        <i class="fas fa-plus"></i> Tambah Barang
                    </button>
                    <div class="ep-total-box">
                        <span class="ep-total-label">Total</span>
                        <span class="ep-total-amount" id="barangTotal">
                            Rp {{ $proposal->jenis==='barang' ? number_format($proposal->total_anggaran,0,',','.') : '0' }}
                        </span>
                    </div>
                </div>
                <input type="hidden" name="total_anggaran" id="totalBarangInput">
            </div>
        </div>

        {{-- ═══ FORM MAINTENANCE ═══ --}}
        <div id="formMaintenance" class="{{ $proposal->jenis === 'maintenance' ? '' : 'd-none' }}">
            <div class="ep-card">
                <div class="ep-card-hd">
                    <span class="cdot"></span> Rincian Maintenance
                    <span id="maintCount" style="margin-left:auto;font-size:10.5px;background:#ccfbf1;color:#0f766e;border-radius:20px;padding:2px 10px;font-weight:700">
                        {{ $proposal->jenis==='maintenance' ? $proposal->items->count() : 0 }} item
                    </span>
                </div>

                {{-- DESKTOP TABLE --}}
                <div class="ep-table-wrap">
                    <table class="ep-table">
                        <thead>
                            <tr>
                                <th style="min-width:150px">Nama Alat</th>
                                <th style="min-width:140px">Jenis Perawatan</th>
                                <th style="min-width:130px">Vendor / Teknisi</th>
                                <th style="width:140px;text-align:right">Anggaran (Rp)</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="maint_body">
                            @if($proposal->jenis === 'maintenance')
                            @foreach($proposal->items as $i => $item)
                            <tr>
                                <td><input type="text" name="items[{{ $i }}][nama_barang]" class="ep-cell" value="{{ $item->nama_barang }}" required></td>
                                <td><input type="text" name="items[{{ $i }}][tujuan_pembelian]" class="ep-cell" value="{{ $item->tujuan_pembelian }}"></td>
                                <td><input type="text" name="items[{{ $i }}][merk_tipe]" class="ep-cell" value="{{ $item->merk_tipe }}"></td>
                                <td><input type="text" name="items[{{ $i }}][anggaran]" data-source="desktop" class="ep-cell money anggaran-input" value="{{ number_format($item->anggaran, 0, ',', '.') }}"></td>
                                <td><button type="button" class="ep-del-btn"><i class="fas fa-trash"></i></button></td>
                            </tr>
                            @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>

                {{-- MOBILE CARDS --}}
                <div class="ep-mob-list" id="mob_maint_list" style="padding:.875rem 1rem;display:none">
                    @if($proposal->jenis === 'maintenance')
                    @foreach($proposal->items as $i => $item)
                    <div class="ep-mob-item">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.625rem">
                            <span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase">Item #{{ $i+1 }}</span>
                            <button type="button" class="ep-del-btn" style="width:24px;height:24px;font-size:11px"><i class="fas fa-trash"></i></button>
                        </div>
                        <div class="ep-mob-row">
                            <label class="ep-label">Nama Alat</label>
                            <input type="text" name="items[{{ $i }}][nama_barang]" class="ep-input" value="{{ $item->nama_barang }}" required>
                        </div>
                        <div class="ep-mob-row">
                            <label class="ep-label">Jenis Perawatan</label>
                            <input type="text" name="items[{{ $i }}][tujuan_pembelian]" class="ep-input" value="{{ $item->tujuan_pembelian }}">
                        </div>
                        <div class="ep-mob-2 ep-mob-row">
                            <div><label class="ep-label">Vendor / Teknisi</label>
                                <input type="text" name="items[{{ $i }}][merk_tipe]" class="ep-input" value="{{ $item->merk_tipe }}">
                            </div>
                            <div><label class="ep-label">Anggaran (Rp)</label>
                                <input type="text" name="items[{{ $i }}][anggaran]" data-source="mobile" class="ep-input anggaran-input" value="{{ number_format($item->anggaran, 0, ',', '.') }}">
                            </div>
                        </div>
                    </div>
                    @endforeach
                    @endif
                </div>

                <div class="ep-table-foot">
                    <button type="button" class="ep-add-btn" id="addMaintenanceRow">
                        <i class="fas fa-plus"></i> Tambah Maintenance
                    </button>
                    <div class="ep-total-box">
                        <span class="ep-total-label">Total</span>
                        <span class="ep-total-amount" id="maintTotal">
                            Rp {{ $proposal->jenis==='maintenance' ? number_format($proposal->total_anggaran,0,',','.') : '0' }}
                        </span>
                    </div>
                </div>
                <input type="hidden" name="total_anggaran" id="totalMaintenanceInput">
            </div>
        </div>

        {{-- ═══ SUBMIT BAR ═══ --}}
        <div class="ep-submit-bar">
            <a href="{{ route('admin.proposal.index') }}" class="ep-cancel-link">Batal</a>
            <button type="submit" class="ep-save-btn">
                <i class="fas fa-save"></i> Simpan Perubahan
            </button>
        </div>

    </form>
</div>

<script>
/* ─────────────────────────────────────────
   DETEKSI LAYAR
───────────────────────────────────────── */
function isMobile() {
    return window.innerWidth < 768;
}

/* ─────────────────────────────────────────
   JENIS TOGGLE
───────────────────────────────────────── */
function setJenis(jenis, btn) {
    document.getElementById('jenisInput').value = jenis;
    document.querySelectorAll('.ep-pill').forEach(p =>
        p.classList.remove('active-barang','active-acara','active-maint'));
    const cls = { barang:'active-barang', acara:'active-acara', maintenance:'active-maint' };
    if (btn) btn.classList.add(cls[jenis]);
    document.getElementById('formAcara').classList.toggle('d-none', jenis !== 'acara');
    document.getElementById('formBarang').classList.toggle('d-none', jenis !== 'barang');
    document.getElementById('formMaintenance').classList.toggle('d-none', jenis !== 'maintenance');
    updateTotal();
}

/* ─────────────────────────────────────────
   FORMAT RUPIAH
───────────────────────────────────────── */
function toRaw(val) {
    return parseInt((val || '').replace(/\D/g, '')) || 0;
}
function formatDot(val) {
    const n = parseInt((val || '').replace(/\D/g, '')) || 0;
    return n.toLocaleString('id-ID');
}
function formatInput(el) {
    const raw = el.value.replace(/\D/g, '');
    el.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
}
function attachFormatter(el) {
    el.addEventListener('input', function () {
        formatInput(this);
        updateTotal();
    });
}
document.querySelectorAll('.anggaran-input').forEach(attachFormatter);

const totalManualEl = document.getElementById('totalManual');
if (totalManualEl) {
    totalManualEl.addEventListener('input', function () {
        const raw = this.value.replace(/\D/g, '');
        this.value = raw ? raw.replace(/\B(?=(\d{3})+(?!\d))/g, '.') : '';
    });
}

/* ─────────────────────────────────────────
   HITUNG TOTAL — FIX DOUBLE COUNT
   Hanya ambil dari sumber yang aktif:
   - Desktop (data-source="desktop") jika layar ≥768px
   - Mobile  (data-source="mobile")  jika layar <768px
───────────────────────────────────────── */
function updateTotal() {
    const source = isMobile() ? 'mobile' : 'desktop';

    /* Barang */
    let totalB = 0;
    document.querySelectorAll(
        `#items_body .anggaran-input[data-source="${source}"],
         #mob_barang_list .anggaran-input[data-source="${source}"]`
    ).forEach(el => { totalB += toRaw(el.value); });
    const barangEl = document.getElementById('barangTotal');
    if (barangEl) barangEl.textContent = 'Rp ' + totalB.toLocaleString('id-ID');
    const biHidden = document.getElementById('totalBarangInput');
    if (biHidden) biHidden.value = totalB;

    /* Maintenance */
    let totalM = 0;
    document.querySelectorAll(
        `#maint_body .anggaran-input[data-source="${source}"],
         #mob_maint_list .anggaran-input[data-source="${source}"]`
    ).forEach(el => { totalM += toRaw(el.value); });
    const maintEl = document.getElementById('maintTotal');
    if (maintEl) maintEl.textContent = 'Rp ' + totalM.toLocaleString('id-ID');
    const miHidden = document.getElementById('totalMaintenanceInput');
    if (miHidden) miHidden.value = totalM;
}
updateTotal();
window.addEventListener('resize', updateTotal);

/* ─────────────────────────────────────────
   COUNTER BADGE
───────────────────────────────────────── */
function updateCount(badgeId, desktopBodyId, mobListId) {
    const desk = document.getElementById(desktopBodyId)?.querySelectorAll('tr').length || 0;
    const mob  = document.getElementById(mobListId)?.querySelectorAll('.ep-mob-item').length || 0;
    const n = isMobile() ? mob : desk;
    const el = document.getElementById(badgeId);
    if (el) el.textContent = n + ' item';
}

/* ─────────────────────────────────────────
   TAMBAH BARANG
───────────────────────────────────────── */
let rowIdx = {{ $proposal->items->count() }};

document.getElementById('addRow')?.addEventListener('click', () => {
    const idx = rowIdx++;

    /* Desktop */
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${idx}][nama_barang]" class="ep-cell" required placeholder="Nama Barang"></td>
        <td><input type="text" name="items[${idx}][merk_tipe]" class="ep-cell" placeholder="Merk / Tipe"></td>
        <td><input type="text" name="items[${idx}][jumlah]" class="ep-cell" value="1" style="text-align:center"></td>
        <td><input type="text" name="items[${idx}][tujuan_pembelian]" class="ep-cell" placeholder="Tujuan"></td>
        <td><input type="text" name="items[${idx}][anggaran]" data-source="desktop" class="ep-cell money anggaran-input" placeholder="0"></td>
        <td><button type="button" class="ep-del-btn"><i class="fas fa-trash"></i></button></td>`;
    document.getElementById('items_body').appendChild(tr);
    attachFormatter(tr.querySelector('.anggaran-input'));

    /* Mobile */
    const mob = document.createElement('div');
    mob.className = 'ep-mob-item';
    mob.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.625rem">
            <span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase">Item Baru</span>
            <button type="button" class="ep-del-btn" style="width:24px;height:24px;font-size:11px"><i class="fas fa-trash"></i></button>
        </div>
        <div class="ep-mob-row"><label class="ep-label">Nama Barang</label>
            <input type="text" name="items[${idx}][nama_barang]" class="ep-input" required></div>
        <div class="ep-mob-2 ep-mob-row">
            <div><label class="ep-label">Merk / Tipe</label><input type="text" name="items[${idx}][merk_tipe]" class="ep-input"></div>
            <div><label class="ep-label">Qty</label><input type="text" name="items[${idx}][jumlah]" class="ep-input" value="1"></div>
        </div>
        <div class="ep-mob-row"><label class="ep-label">Tujuan</label>
            <input type="text" name="items[${idx}][tujuan_pembelian]" class="ep-input"></div>
        <div class="ep-mob-row"><label class="ep-label">Anggaran (Rp)</label>
            <input type="text" name="items[${idx}][anggaran]" data-source="mobile" class="ep-input anggaran-input" placeholder="0"></div>`;
    document.getElementById('mob_barang_list').appendChild(mob);
    mob.querySelectorAll('.anggaran-input').forEach(attachFormatter);

    updateCount('barangCount', 'items_body', 'mob_barang_list');
});

/* ─────────────────────────────────────────
   TAMBAH MAINTENANCE
───────────────────────────────────────── */
document.getElementById('addMaintenanceRow')?.addEventListener('click', () => {
    const idx = rowIdx++;

    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td><input type="text" name="items[${idx}][nama_barang]" class="ep-cell" required placeholder="Nama Alat"></td>
        <td><input type="text" name="items[${idx}][tujuan_pembelian]" class="ep-cell" placeholder="Jenis Perawatan"></td>
        <td><input type="text" name="items[${idx}][merk_tipe]" class="ep-cell" placeholder="Vendor / Teknisi"></td>
        <td><input type="text" name="items[${idx}][anggaran]" data-source="desktop" class="ep-cell money anggaran-input" placeholder="0"></td>
        <td><button type="button" class="ep-del-btn"><i class="fas fa-trash"></i></button></td>`;
    document.getElementById('maint_body').appendChild(tr);
    attachFormatter(tr.querySelector('.anggaran-input'));

    const mob = document.createElement('div');
    mob.className = 'ep-mob-item';
    mob.innerHTML = `
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.625rem">
            <span style="font-size:11px;font-weight:700;color:#6b7280;text-transform:uppercase">Item Baru</span>
            <button type="button" class="ep-del-btn" style="width:24px;height:24px;font-size:11px"><i class="fas fa-trash"></i></button>
        </div>
        <div class="ep-mob-row"><label class="ep-label">Nama Alat</label>
            <input type="text" name="items[${idx}][nama_barang]" class="ep-input" required></div>
        <div class="ep-mob-row"><label class="ep-label">Jenis Perawatan</label>
            <input type="text" name="items[${idx}][tujuan_pembelian]" class="ep-input"></div>
        <div class="ep-mob-2 ep-mob-row">
            <div><label class="ep-label">Vendor / Teknisi</label><input type="text" name="items[${idx}][merk_tipe]" class="ep-input"></div>
            <div><label class="ep-label">Anggaran (Rp)</label>
                <input type="text" name="items[${idx}][anggaran]" data-source="mobile" class="ep-input anggaran-input" placeholder="0"></div>
        </div>`;
    document.getElementById('mob_maint_list').appendChild(mob);
    mob.querySelectorAll('.anggaran-input').forEach(attachFormatter);

    updateCount('maintCount', 'maint_body', 'mob_maint_list');
});

/* ─────────────────────────────────────────
   HAPUS ROW — hapus keduanya (desktop+mobile)
   berdasarkan name index yang sama
───────────────────────────────────────── */
document.addEventListener('click', e => {
    const btn = e.target.closest('.ep-del-btn');
    if (!btn) return;

    const tr      = btn.closest('tr');
    const mobItem = btn.closest('.ep-mob-item');

    if (tr) {
        /* Ambil index dari nama input di row ini */
        const nameInput = tr.querySelector('input[name]');
        const match = nameInput?.name.match(/items\[(\d+)\]/);
        if (match) {
            const idx = match[1];
            /* Hapus mob card yang punya input dengan index sama */
            document.querySelectorAll(`.ep-mob-item input[name^="items[${idx}]"]`).forEach(inp => {
                inp.closest('.ep-mob-item')?.remove();
            });
        }
        tr.remove();
    } else if (mobItem) {
        const nameInput = mobItem.querySelector('input[name]');
        const match = nameInput?.name.match(/items\[(\d+)\]/);
        if (match) {
            const idx = match[1];
            /* Hapus desktop row yang punya input dengan index sama */
            document.querySelectorAll(`#items_body input[name^="items[${idx}]"], #maint_body input[name^="items[${idx}]"]`).forEach(inp => {
                inp.closest('tr')?.remove();
            });
        }
        mobItem.remove();
    }

    updateTotal();
    updateCount('barangCount', 'items_body', 'mob_barang_list');
    updateCount('maintCount', 'maint_body', 'mob_maint_list');
});

/* ─────────────────────────────────────────
   SUBMIT — bersihkan format sebelum kirim
   Nonaktifkan input dari layer yang TIDAK aktif
   agar tidak ada duplikat nama field ke server
───────────────────────────────────────── */
document.getElementById('ep-form').addEventListener('submit', function (e) {
    const mobile = isMobile();

    /* Nonaktifkan layer yang tidak dipakai */
    if (mobile) {
        /* Di mobile: disable semua desktop inputs */
        document.querySelectorAll('#items_body input, #maint_body input').forEach(el => el.disabled = true);
    } else {
        /* Di desktop: disable semua mobile inputs */
        document.querySelectorAll('#mob_barang_list input, #mob_maint_list input').forEach(el => el.disabled = true);
    }

    /* Bersihkan format Rupiah dari field yang aktif */
    this.querySelectorAll('.anggaran-input:not([disabled])').forEach(el => {
        el.value = el.value.replace(/\D/g, '');
    });
    if (totalManualEl) {
        totalManualEl.value = totalManualEl.value.replace(/\D/g, '');
    }
});
</script>

@endsection