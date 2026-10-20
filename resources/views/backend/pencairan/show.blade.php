@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   PROPOSAL PENCAIRAN SHOW — DESIGN SYSTEM
   ============================================================ */
.pds-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pds-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pds-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pds-back-btn { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:9px; padding:7px 16px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:all .15s }
.pds-back-btn:hover { background:rgba(255,255,255,.25); color:#fff; text-decoration:none }

/* ── Stat Row ── */
.pds-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.875rem; margin-bottom:1.25rem }
@media(max-width:768px){ .pds-stats { grid-template-columns:1fr } }
.pds-stat { background:#fff; border:0.5px solid #e5e7eb; border-radius:13px; padding:.875rem 1rem; border-left:3px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pds-stat.purple { border-left-color:#7c3aed }
.pds-stat.green  { border-left-color:#10b981 }
.pds-stat.blue   { border-left-color:#3b82f6 }
.pds-stat.amber  { border-left-color:#f59e0b }
.pds-stat .s-icon { font-size:1.1rem; margin-bottom:.4rem }
.pds-stat .s-icon.purple { color:#7c3aed }
.pds-stat .s-icon.green  { color:#10b981 }
.pds-stat .s-icon.blue   { color:#3b82f6 }
.pds-stat .s-icon.amber  { color:#f59e0b }
.pds-stat .s-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:6px }
.pds-stat .s-value { font-size:18px; font-weight:700; color:#111; line-height:1.2 }
.pds-stat .s-value.sm { font-size:14px }

/* ── Badges ── */
.pds-badge { font-size:11px; font-weight:700; border-radius:20px; padding:4px 12px; white-space:nowrap; display:inline-flex; align-items:center; gap:5px }
.b-amber  { background:#fef3c7; color:#92400e }
.b-green  { background:#d1fae5; color:#065f46 }
.b-red    { background:#fee2e2; color:#991b1b }
.b-purple { background:#ede9fe; color:#5b21b6 }
.b-blue   { background:#dbeafe; color:#1e40af }
.b-gray   { background:#f3f4f6; color:#374151 }

/* ── Detail Card ── */
.pds-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); margin-bottom:1rem }
.pds-card-header { background:#f9fafb; border-bottom:0.5px solid #e5e7eb; padding:.75rem 1.25rem; display:flex; align-items:center; gap:7px }
.pds-card-header i { color:#7c3aed; font-size:13px }
.pds-card-header span { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#374151 }

/* ── Detail Table ── */
.pds-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pds-table tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pds-table tr:last-child { border-bottom:none }
.pds-table tr:hover td, .pds-table tr:hover th { background:#faf8ff }
.pds-table th { padding:11px 14px 11px 1.25rem; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; width:28%; white-space:nowrap; vertical-align:top; padding-top:13px }
.pds-table td { padding:11px 14px; color:#374151; vertical-align:middle }
.pds-table .td-main { font-weight:700; color:#111 }
.pds-table .td-amount { font-weight:700; color:#059669; font-size:14px }

/* ── Note box ── */
.pds-note { background:#eff6ff; border:0.5px solid #bfdbfe; border-radius:8px; padding:.625rem .875rem; font-size:12.5px; color:#1e40af; line-height:1.55 }
.pds-note.danger { background:#fef2f2; border-color:#fecaca; color:#991b1b }
.pds-note.success { background:#d1fae5; border-color:#10b981; color:#065f46 }

/* ── Metode tag ── */
.pds-metode { font-size:10.5px; font-weight:700; border-radius:5px; padding:3px 10px; background:#ede9fe; color:#5b21b6; display:inline-block; text-transform:uppercase }

/* ── Bukti button ── */
.pds-bukti-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:8px; background:#ede9fe; color:#5b21b6; font-size:12px; font-weight:700; text-decoration:none; transition:opacity .15s }
.pds-bukti-btn:hover { opacity:.8; color:#5b21b6 }

/* ── Items List (Mobile) ── */
.pds-mobile-list { padding:1rem }
.pds-mobile-item { background:#fff; border:0.5px solid #e5e7eb; border-radius:10px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 2px rgba(0,0,0,.03) }
.pds-mobile-item-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:.75rem; padding-bottom:.75rem; border-bottom:0.5px solid #f3f4f6 }
.pds-mobile-item-num { background:#7c3aed; color:#fff; padding:3px 10px; border-radius:10px; font-size:11px; font-weight:700 }
.pds-mobile-item-price { color:#059669; font-weight:700; font-size:13px }
.pds-mobile-item-name { font-size:14px; font-weight:700; color:#111; margin-bottom:.5rem }
.pds-mobile-detail-row { display:flex; justify-content:space-between; padding:.3rem 0; font-size:12px; border-bottom:0.5px solid #f9fafb }
.pds-mobile-detail-row:last-child { border-bottom:none }
.pds-mobile-detail-label { color:#6b7280; font-weight:500 }
.pds-mobile-detail-value { color:#374151; text-align:right; word-break:break-word }
.pds-mobile-total { background:#f9fafb; padding:.875rem 1rem; border-radius:10px; display:flex; justify-content:space-between; align-items:center; font-weight:700; margin-top:.5rem }
.pds-mobile-total .label { color:#6b7280; font-size:12px }
.pds-mobile-total .value { color:#059669; font-size:13px }

/* ── Approval Timeline (Mobile) ── */
.pds-approval-timeline { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.pds-approval-timeline-item { padding:1rem 1.25rem; border-bottom:0.5px solid #f3f4f6; display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap }
.pds-approval-timeline-item:last-child { border-bottom:none }
.pds-approval-stage { font-size:13px; font-weight:700; color:#111; margin-bottom:.25rem }
.pds-approval-approver { font-size:12px; color:#6b7280 }
.pds-approval-note { font-size:12px; color:#6b7280; font-style:italic; margin-top:.5rem; padding-left:1.5rem; border-left:2px solid #e5e7eb }
.pds-approval-date { font-size:11px; color:#9ca3af; margin-top:.5rem }

/* ── Disbursement Info Box ── */
.pds-disb-info { background:linear-gradient(135deg,#dbeafe,#eff6ff); border:0.5px solid #bfdbfe; border-radius:10px; padding:1rem; margin-bottom:1rem }
.pds-disb-info .label { font-size:11px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#1e40af }
.pds-disb-info .value { font-size:15px; font-weight:700; color:#1e3a8a }

/* ── Action Button ── */
.pds-btn-cair { display:inline-flex; align-items:center; gap:6px; background:linear-gradient(135deg,#10b981,#059669); color:#fff; border:none; border-radius:9px; padding:10px 1.5rem; font-size:13px; font-weight:700; cursor:pointer; transition:opacity .15s,transform .1s }
.pds-btn-cair:hover { opacity:.88; transform:translateY(-1px); color:#fff }

/* ── Modal Styling ── */
.pds-modal .modal-content { border:0; border-radius:14px; box-shadow:0 10px 40px rgba(0,0,0,.15) }
.pds-modal .modal-header { background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff; border-radius:14px 14px 0 0; padding:1rem 1.5rem; border:none }
.pds-modal .modal-title { font-size:16px; font-weight:700 }
.pds-modal .modal-body { padding:1.5rem }
.pds-modal .modal-footer { border:none; padding:1rem 1.5rem 1.5rem; background:#f9fafb; border-radius:0 0 14px 14px }

/* ── Form Elements ── */
.pds-field { margin-bottom:1.25rem }
.pds-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:5px }
.pds-input, .pds-select, .pds-textarea { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:9px; padding:9px 12px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:13px; outline:none; transition:border-color .15s,box-shadow .15s }
.pds-input:focus, .pds-select:focus, .pds-textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.pds-select { appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .875rem center; padding-right:2.5rem }
.pds-textarea { min-height:80px; resize:vertical }
.pds-input-hint { font-size:11px; color:#9ca3af; margin-top:4px }

/* ── Radio Group ── */
.pds-radio-group { display:flex; flex-wrap:wrap; gap:1rem }
.pds-radio { display:flex; align-items:center; gap:6px; font-size:13px; color:#374151; cursor:pointer }
.pds-radio input { width:16px; height:16px; accent-color:#7c3aed }

/* ── Currency Input Wrapper ── */
.pds-currency-wrap { position:relative }
.pds-currency-wrap .currency-prefix { position:absolute; left:12px; top:50%; transform:translateY(-50%); color:#6b7280; font-weight:600; font-size:13px }
.pds-currency-wrap .pds-input { padding-left:2.5rem !important }

/* ── File Upload ── */
.pds-file-upload { border:1px dashed #d1d5db; border-radius:9px; padding:1rem; text-align:center; background:#f9fafb; transition:border-color .15s }
.pds-file-upload:hover { border-color:#7c3aed }
.pds-file-upload input { display:none }
.pds-file-upload label { display:block; cursor:pointer; font-size:12px; color:#6b7280 }
.pds-file-upload i { font-size:1.2rem; color:#7c3aed; margin-bottom:4px }

/* ── Responsive ── */
@media (max-width: 768px) {
    .pds-wrap { padding:1rem 0 2rem }
    .pds-topbar { padding:.875rem 1rem }
    .pds-topbar h1 { font-size:14px }
    .pds-card-header, .pds-approval-header { padding:.625rem 1rem }
    .pds-card-header span, .pds-approval-header span { font-size:10px }
    .pds-table { font-size:11.5px }
    .pds-table th { padding:9px 12px 9px 1rem; width:35% }
    .pds-table td { padding:9px 12px }
    .pds-btn { padding:9px 1.25rem; font-size:12px }
    .pds-btn-cair { width:100%; justify-content:center }
    .pds-modal .modal-dialog { margin:1rem }
    .pds-modal .modal-body { padding:1.25rem }
    .pds-field label { font-size:10px }
    .pds-input, .pds-select, .pds-textarea { font-size:13px !important }
}

@media (max-width: 480px) {
    .pds-stats { grid-template-columns:1fr }
    .pds-mobile-list { padding:.75rem }
    .pds-mobile-item { padding:.875rem }
    .pds-mobile-item-name { font-size:13px }
    .pds-mobile-detail-row { font-size:11px }
    .pds-approval-timeline-item { padding:.875rem 1rem }
    .pds-approval-stage { font-size:12px }
}
</style>

<div class="pds-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pds-topbar">
        <h1>
            <i class="fas fa-file-invoice-dollar"></i>
            Detail Proposal untuk Pencairan
        </h1>
        {{-- <a href="{{ route('admin.pencairan.index') }}" class="pds-back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a> --}}
    </div>

    {{-- ═══ ALERTS ═══ --}}
    @if(session('success'))
        <div class="pds-note success mb-3">
            <i class="fas fa-check-circle" style="margin-right:5px"></i>
            {{ session('success') }}
        </div>
    @elseif(session('error'))
        <div class="pds-note danger mb-3">
            <i class="fas fa-exclamation-circle" style="margin-right:5px"></i>
            {{ session('error') }}
        </div>
    @endif

    {{-- ═══ STAT ROW ═══ --}}
    <div class="pds-stats">
        <div class="pds-stat purple">
            <div class="s-icon purple"><i class="fas fa-info-circle"></i></div>
            <div class="s-label">Status</div>
            <div>
                @if($proposal->status === 'disetujui_senior_pastor')
                    <span class="pds-badge b-green"><i class="fas fa-check-circle"></i> Siap Dicairkan</span>
                @elseif($proposal->status === 'dicairkan')
                    <span class="pds-badge b-blue"><i class="fas fa-check-double"></i> Sudah Dicairkan Penuh</span>
                @elseif($proposal->status === 'dicairkan_sebagian')
                    <span class="pds-badge b-amber"><i class="fas fa-clock"></i> Dicairkan Sebagian</span>
                @else
                    <span class="pds-badge b-gray">{{ ucfirst($proposal->status) }}</span>
                @endif
            </div>
        </div>

        <div class="pds-stat green">
            <div class="s-icon green"><i class="fas fa-coins"></i></div>
            <div class="s-label">Total Anggaran</div>
            <div class="s-value">Rp {{ number_format($proposal->total_anggaran, 0, ',', '.') }}</div>
        </div>

        <div class="pds-stat blue">
            <div class="s-icon blue"><i class="fas fa-building"></i></div>
            <div class="s-label">Departemen</div>
            <div class="s-value sm">{{ $proposal->departemen->nama_departemen ?? '-' }}</div>
        </div>
    </div>

    {{-- ═══ INFORMASI DASAR ═══ --}}
    <div class="pds-card">
        <div class="pds-card-header">
            <i class="fas fa-info-circle"></i>
            <span>Informasi Proposal</span>
        </div>
        <table class="pds-table">
            <tbody>
                <tr>
                    <th><i class="fas fa-file-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Judul Proposal</th>
                    <td class="td-main">{{ $proposal->judul }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Pemohon</th>
                    <td>{{ $proposal->user->name ?? '-' }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-calendar fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Tanggal Pengajuan</th>
                    <td>{{ \Carbon\Carbon::parse($proposal->tanggal_pengajuan)->format('d F Y') }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-bullseye fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Tujuan</th>
                    <td>{{ $proposal->tujuan ?? '-' }}</td>
                </tr>
                @if($proposal->lampiran)
                <tr>
                    <th><i class="fas fa-paperclip fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Lampiran</th>
                    <td>
                        <a href="{{ asset('storage/'.$proposal->lampiran) }}" target="_blank" class="pds-bukti-btn">
                            <i class="fas fa-eye"></i> Lihat Lampiran
                        </a>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>
    </div>

    {{-- ═══ DETAIL ACARA / BARANG ═══ --}}
    <div class="pds-card">
        <div class="pds-card-header">
            <i class="fas fa-list-ul"></i>
            <span>{{ $proposal->jenis === 'maintenance' ? 'Rincian Maintenance' : ($proposal->jenis === 'acara' ? 'Detail Acara' : 'Rincian Barang') }}</span>
        </div>
        
        @if($proposal->jenis === 'acara')
            <div class="pds-card-body p-3 p-md-4">
                <table class="pds-table">
                    <tbody>
                        <tr>
                            <th><i class="fas fa-calendar-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Judul Acara</th>
                            <td class="td-main">{{ $proposal->judul_acara ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-day fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Tanggal Acara</th>
                            <td>{{ $proposal->tanggal_acara ? \Carbon\Carbon::parse($proposal->tanggal_acara)->format('d F Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-map-marker-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Lokasi</th>
                            <td>{{ $proposal->lokasi_acara ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-sticky-note fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Rincian Kebutuhan</th>
                            <td>{{ $proposal->keterangan ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-coins fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Total Anggaran</th>
                            <td class="td-amount">Rp {{ number_format($proposal->total_anggaran_manual ?? $proposal->total_anggaran, 0, ',', '.') }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        @else
            {{-- Desktop Table --}}
            <div class="table-responsive d-none d-md-block">
                <table class="pds-table">
                    <thead>
                        <tr>
                            <th style="width:50px;text-align:center">#</th>
                            <th>Nama {{ $proposal->jenis === 'maintenance' ? 'Alat' : 'Barang' }}</th>
                            @if($proposal->jenis !== 'maintenance')
                                <th>Merk / Tipe</th>
                                <th style="text-align:center">Jumlah</th>
                            @else
                                <th>Jenis Perawatan</th>
                                <th>Vendor / Teknisi</th>
                            @endif
                            <th style="text-align:right">Anggaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proposal->items as $i => $item)
                            <tr>
                                <td style="text-align:center">{{ $i + 1 }}</td>
                                <td class="td-main">{{ $item->nama_barang }}</td>
                                @if($proposal->jenis !== 'maintenance')
                                    <td>{{ $item->merk_tipe }}</td>
                                    <td style="text-align:center">{{ $item->jumlah }}</td>
                                @else
                                    <td>{{ $item->tujuan_pembelian }}</td>
                                    <td>{{ $item->merk_tipe }}</td>
                                @endif
                                <td style="text-align:right" class="td-amount">Rp {{ number_format($item->anggaran,0,',','.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $proposal->jenis === 'maintenance' ? 5 : 6 }}" style="text-align:center;color:#9ca3af;padding:1.5rem">
                                    Tidak ada data {{ $proposal->jenis === 'maintenance' ? 'maintenance' : 'barang' }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if($proposal->items->count() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="{{ $proposal->jenis === 'maintenance' ? 4 : 5 }}" style="text-align:right;font-weight:700;background:#f9fafb">Total Anggaran</td>
                            <td style="text-align:right;font-weight:700;color:#059669;background:#f9fafb">
                                Rp {{ number_format($proposal->items->sum('anggaran'),0,',','.') }}
                            </td>
                        </tr>
                    </tfoot>
                    @endif
                </table>
            </div>

            {{-- Mobile Cards --}}
            <div class="pds-mobile-list d-md-none">
                @forelse($proposal->items as $i => $item)
                    <div class="pds-mobile-item">
                        <div class="pds-mobile-item-header">
                            <span class="pds-mobile-item-num">#{{ $i + 1 }}</span>
                            <span class="pds-mobile-item-price">Rp {{ number_format($item->anggaran, 0, ',', '.') }}</span>
                        </div>
                        <div class="pds-mobile-item-name">{{ $item->nama_barang }}</div>
                        <div class="pds-mobile-detail-row">
                            <span class="pds-mobile-detail-label">{{ $proposal->jenis === 'maintenance' ? 'Perawatan' : 'Merk/Tipe' }}:</span>
                            <span class="pds-mobile-detail-value">{{ $proposal->jenis === 'maintenance' ? $item->tujuan_pembelian : ($item->merk_tipe ?? '-') }}</span>
                        </div>
                        @if($proposal->jenis !== 'maintenance')
                        <div class="pds-mobile-detail-row">
                            <span class="pds-mobile-detail-label">Jumlah:</span>
                            <span class="pds-mobile-detail-value">{{ $item->jumlah ?? '-' }}</span>
                        </div>
                        @endif
                        @if($proposal->jenis === 'maintenance')
                        <div class="pds-mobile-detail-row">
                            <span class="pds-mobile-detail-label">Vendor:</span>
                            <span class="pds-mobile-detail-value">{{ $item->merk_tipe ?? '-' }}</span>
                        </div>
                        @endif
                        <div class="pds-mobile-detail-row">
                            <span class="pds-mobile-detail-label">Tujuan:</span>
                            <span class="pds-mobile-detail-value">{{ $item->tujuan_pembelian ?? '-' }}</span>
                        </div>
                    </div>
                @empty
                    <div style="text-align:center;color:#9ca3af;padding:1.5rem">
                        Tidak ada item dalam proposal ini.
                    </div>
                @endforelse

                @if($proposal->items && $proposal->items->count() > 0)
                    <div class="pds-mobile-total">
                        <span class="label">Total Anggaran:</span>
                        <span class="value">Rp {{ number_format($proposal->items->sum('anggaran'), 0, ',', '.') }}</span>
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- ═══ NOTA PENGGUNAAN ═══ --}}
    @if($proposal->nota_penggunaan->count() > 0)
    <div class="pds-card">
        <div class="pds-card-header">
            <i class="fas fa-receipt"></i>
            <span>Bukti Penggunaan Dana</span>
        </div>
        
        @php
            $totalRealisasi = $proposal->nota_penggunaan->sum('jumlah');
            $totalCair = $proposal->pencairan->sum('jumlah_dana');
        @endphp

        @if($totalRealisasi >= $totalCair)
            <div class="pds-note success m-3">
                <i class="fas fa-check-circle" style="margin-right:5px"></i>
                Realisasi Selesai
            </div>
        @endif

        @if($totalRealisasi > $totalCair)
            <div class="pds-note danger m-3">
                <i class="fas fa-exclamation-triangle" style="margin-right:5px"></i>
                Realisasi melebihi dana yang dicairkan!
            </div>
        @endif

        <div class="table-responsive">
            <table class="pds-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">#</th>
                        <th>Keterangan</th>
                        <th style="text-align:right">Jumlah</th>
                        <th style="text-align:center">File</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($proposal->nota_penggunaan as $i => $nota)
                    <tr>
                        <td style="text-align:center">{{ $i + 1 }}</td>
                        <td class="td-main">{{ $nota->keterangan ?? '-' }}</td>
                        <td style="text-align:right" class="td-amount">Rp {{ number_format($nota->jumlah,0,',','.') }}</td>
                        <td style="text-align:center">
                            <a href="{{ asset('storage/'.$nota->file_nota) }}" target="_blank" class="pds-bukti-btn">
                                <i class="fas fa-eye"></i> Lihat
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align:right;font-weight:700;background:#f9fafb">Total Realisasi</td>
                        <td style="text-align:right;font-weight:700;color:#059669;background:#f9fafb">
                            Rp {{ number_format($totalRealisasi,0,',','.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══ RIWAYAT PERSETUJUAN ═══ --}}
    <div class="pds-card">
        <div class="pds-card-header">
            <i class="fas fa-history"></i>
            <span>Riwayat Persetujuan</span>
        </div>
        
        {{-- Desktop Table --}}
        <div class="table-responsive d-none d-md-block">
            <table class="pds-table">
                <thead>
                    <tr>
                        <th style="width:40px;text-align:center">#</th>
                        <th>Tahap</th>
                        <th>Approver</th>
                        <th style="text-align:center">Status</th>
                        <th>Catatan</th>
                        <th style="text-align:center">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposal->approval_flows as $i => $flow)
                        <tr>
                            <td style="text-align:center">{{ $i + 1 }}</td>
                            <td class="td-main">{{ ucfirst(str_replace('_', ' ', $flow->tahap)) }}</td>
                            <td>{{ $flow->approver->name ?? '-' }}</td>
                            <td style="text-align:center">
                                @if($flow->status === 'disetujui')
                                    <span class="pds-badge b-green"><i class="fas fa-check"></i> Disetujui</span>
                                @elseif($flow->status === 'ditolak')
                                    <span class="pds-badge b-red"><i class="fas fa-times"></i> Ditolak</span>
                                @elseif($flow->status === 'pending')
                                    <span class="pds-badge b-amber"><i class="fas fa-clock"></i> Menunggu</span>
                                @else
                                    <span class="pds-badge b-gray">{{ ucfirst($flow->status) }}</span>
                                @endif
                            </td>
                            <td>{{ $flow->catatan ?? '-' }}</td>
                            <td style="text-align:center;font-size:11px;color:#6b7280">
                                {{ $flow->tanggal_approve ? \Carbon\Carbon::parse($flow->tanggal_approve)->format('d/m/Y H:i') : '-' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" style="text-align:center;color:#9ca3af;padding:1.5rem">Belum ada riwayat persetujuan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Mobile Timeline --}}
        <div class="pds-approval-timeline d-md-none">
            @forelse($proposal->approval_flows as $i => $flow)
                <div class="pds-approval-timeline-item">
                    <div>
                        <div class="pds-approval-stage">{{ ucfirst(str_replace('_', ' ', $flow->tahap)) }}</div>
                        <div class="pds-approval-approver">{{ $flow->approver->name ?? '-' }}</div>
                        <div style="margin:.375rem 0">
                            @if($flow->status === 'disetujui')
                                <span class="pds-badge b-green"><i class="fas fa-check"></i> Disetujui</span>
                            @elseif($flow->status === 'ditolak')
                                <span class="pds-badge b-red"><i class="fas fa-times"></i> Ditolak</span>
                            @elseif($flow->status === 'pending')
                                <span class="pds-badge b-amber"><i class="fas fa-clock"></i> Menunggu</span>
                            @else
                                <span class="pds-badge b-gray">{{ ucfirst($flow->status) }}</span>
                            @endif
                        </div>
                        @if($flow->catatan)
                            <div class="pds-approval-note">"{{ $flow->catatan }}"</div>
                        @endif
                    </div>
                    @if($flow->tanggal_approve)
                        <div class="pds-approval-date">
                            {{ \Carbon\Carbon::parse($flow->tanggal_approve)->format('d/m/Y H:i') }}
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align:center;color:#9ca3af;padding:1.5rem">
                    Belum ada riwayat persetujuan.
                </div>
            @endforelse
        </div>
    </div>

    {{-- ═══ TOMBOL CAIRKAN ═══ --}}
    @php
        $totalCair = $proposal->pencairan->sum('jumlah_dana');
        $sisaDana  = $proposal->total_anggaran - $totalCair;
    @endphp

    @if(in_array($proposal->status, ['disetujui_senior_pastor', 'dicairkan_sebagian']) && $sisaDana > 0)
        <div class="pds-disb-info">
            <div style="margin-bottom:.5rem">
                <span class="label">Sudah Dicairkan:</span>
                <div class="value">Rp {{ number_format($totalCair, 0, ',', '.') }}</div>
            </div>
            <div>
                <span class="label">Sisa Dana:</span>
                <div class="value">Rp {{ number_format($sisaDana, 0, ',', '.') }}</div>
            </div>
        </div>
        <button class="pds-btn-cair" data-bs-toggle="modal" data-bs-target="#cairModal{{ $proposal->id }}">
            <i class="fas fa-money-bill-wave"></i> Cairkan Dana
        </button>
    @endif

</div>

{{-- ═══ MODAL PENCAIRAN ═══ --}}
<div class="modal fade pds-modal" id="cairModal{{ $proposal->id }}" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
    <div class="modal-content">
      <form action="{{ route('admin.pencairan.store') }}" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
        @csrf
        <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">

        {{-- HEADER --}}
        <div class="modal-header">
            <h5 class="modal-title">
                <i class="fas fa-money-bill-wave me-2"></i>Cairkan Dana Proposal
            </h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
        </div>

        {{-- BODY --}}
        <div class="modal-body">
            {{-- Info Proposal --}}
            <div style="background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px;padding:1rem;margin-bottom:1.5rem">
                <strong style="color:#111;font-size:14px">{{ $proposal->judul }}</strong><br>
                <span style="color:#6b7280;font-size:12px">
                    Total Anggaran: <strong style="color:#059669">Rp {{ number_format($proposal->total_anggaran, 0, ',', '.') }}</strong><br>
                    Departemen: {{ $proposal->departemen->nama_departemen ?? '-' }}
                </span>
            </div>

            {{-- Rekening --}}
            <div class="pds-field">
                <label><i class="fas fa-university fa-sm" style="margin-right:3px"></i>Pilih Rekening</label>
                <select name="rekening_id" class="pds-select" required>
                    <option value="">-- Pilih Rekening --</option>
                    @foreach(\App\Models\Rekening::orderBy('nama_rekening')->get() as $r)
                        <option value="{{ $r->id }}">
                            {{ $r->nama_rekening }} (Saldo: Rp {{ number_format($r->saldo, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Metode Pencairan --}}
            <div class="pds-field">
                <label class="mb-2"><i class="fas fa-exchange-alt fa-sm" style="margin-right:3px"></i>Metode Pencairan</label>
                <div class="pds-radio-group">
                    <label class="pds-radio">
                        <input class="metode-pencairan" type="radio" name="metode" id="transfer{{ $proposal->id }}" value="transfer" required>
                        <i class="fas fa-university"></i> Transfer
                    </label>
                    <label class="pds-radio">
                        <input class="metode-pencairan" type="radio" name="metode" id="cash{{ $proposal->id }}" value="cash">
                        <i class="fas fa-money-bill"></i> Cash
                    </label>
                </div>
            </div>

            {{-- Jumlah Dana --}}
            <div class="pds-field">
                <label><i class="fas fa-coins fa-sm" style="margin-right:3px"></i>Jumlah Dana (Rp)</label>
                <div class="pds-currency-wrap">
                    <span class="currency-prefix">Rp</span>
                    <input type="text"
                          id="jumlahDana{{ $proposal->id }}"
                          name="jumlah_dana"
                          class="pds-input uang-format"
                          value="{{ number_format($sisaDana, 0, ',', '.') }}"
                          data-max="{{ $sisaDana }}"
                          required>
                </div>
                <div class="pds-input-hint">Maksimal pencairan: Rp {{ number_format($sisaDana, 0, ',', '.') }}</div>
            </div>

            {{-- Catatan --}}
            <div class="pds-field">
                <label><i class="fas fa-sticky-note fa-sm" style="margin-right:3px"></i>Catatan (Opsional)</label>
                <textarea name="catatan" class="pds-textarea" placeholder="Tambahkan keterangan bila diperlukan..."></textarea>
            </div>

            {{-- Upload Bukti Transfer --}}
            <div class="pds-field" id="buktiTransferGroup{{ $proposal->id }}" style="display: none;">
                <label><i class="fas fa-paperclip fa-sm" style="margin-right:3px"></i>Upload Bukti Transfer</label>
                <div class="pds-file-upload">
                    <input type="file" name="bukti_transfer" id="buktiFile{{ $proposal->id }}" accept=".pdf,.jpg,.jpeg,.png">
                    <label for="buktiFile{{ $proposal->id }}">
                        <i class="fas fa-cloud-upload-alt"></i><br>
                        Klik untuk upload (PDF/JPG/PNG)
                    </label>
                </div>
                <div class="pds-input-hint">*Wajib diisi jika metode Transfer</div>
            </div>
        </div>

        {{-- FOOTER --}}
        <div class="modal-footer">
            <button type="button" class="pds-btn" style="background:#f3f4f6;color:#374151;border:0.5px solid #e5e7eb" data-bs-dismiss="modal">
                <i class="fas fa-times"></i> Batal
            </button>
            <button type="submit" class="pds-btn" style="background:linear-gradient(135deg,#10b981,#059669);color:#fff">
                <i class="fas fa-check-circle"></i> Cairkan
            </button>
        </div>
      </form>
    </div>
  </div>
</div>

{{-- SCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Format input uang
  document.querySelectorAll('.uang-format').forEach(input => {
    formatInputValue(input);
    input.addEventListener('input', e => formatInputValue(e.target));
    input.form?.addEventListener('submit', () => {
      input.value = input.value.replace(/[^0-9]/g, '');
    });
  });

  // Batasi input sesuai sisa maksimal
  document.querySelectorAll('.uang-format').forEach(input => {
    input.addEventListener('blur', function() {
      const max = parseFloat(this.dataset.max || 0);
      let value = parseFloat(this.value.replace(/\./g, '').replace(/[^0-9]/g, '')) || 0;
      if (max > 0 && value > max) {
        alert('Jumlah dana melebihi sisa anggaran!');
        this.value = new Intl.NumberFormat('id-ID').format(max);
      }
    });
  });

  // Tampilkan upload bukti jika metode transfer
  document.querySelectorAll('.metode-pencairan').forEach(radio => {
    radio.addEventListener('change', function () {
      const id = this.id.replace(/[^0-9]/g, '');
      const buktiGroup = document.getElementById(`buktiTransferGroup${id}`);
      buktiGroup.style.display = this.value === 'transfer' ? 'block' : 'none';
    });
  });

  function formatInputValue(input) {
    let value = input.value.replace(/[^0-9]/g, '');
    if (!value) return input.value = '';
    input.value = new Intl.NumberFormat('id-ID').format(value);
  }

  // Tooltip initialization
  var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
  tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection


