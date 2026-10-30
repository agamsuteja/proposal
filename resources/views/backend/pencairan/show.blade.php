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
.pds-modal .modal-dialog { max-width:780px }
.pds-modal .modal-content { max-height:calc(100dvh - 48px); overflow:hidden; border:0; border-radius:18px; box-shadow:0 24px 70px rgba(31,16,60,.24) }
.pds-modal [data-payout-form] { display:flex; flex-direction:column; min-height:0; max-height:inherit; margin:0 }
.pds-modal .modal-header { flex-shrink:0; justify-content:space-between; align-items:center; background:linear-gradient(120deg,#7c3aed,#4c1d95); color:#fff; padding:18px 22px; border:0; border-radius:0; gap:12px }
.pds-modal .modal-title { font-size:17px; font-weight:800; color:#fff; margin:0; line-height:1.35 }
.payout-heading { display:flex; align-items:center; gap:12px }
.payout-heading-icon { display:grid; place-items:center; width:40px; height:40px; flex-shrink:0; border-radius:12px; background:rgba(255,255,255,.15); font-size:18px }
.payout-heading p { margin:3px 0 0; font-size:12px; color:#ede9fe }
.pds-modal .btn-close { flex-shrink:0; margin:0; padding:8px; opacity:.9; filter:brightness(0) invert(1) }
.pds-modal .modal-body { flex:1 1 auto; min-height:0; overflow-y:auto; overscroll-behavior:contain; padding:20px 22px; background:#fff }
.payout-summary { padding:16px; border:1px solid #e9e3f1; border-radius:13px; background:#fcfbfe; margin-bottom:16px }
.payout-summary h6 { font-size:15px; font-weight:800; color:#202538; margin:0 0 5px; line-height:1.4; overflow-wrap:anywhere }
.payout-meta { color:#667085; font-size:11.5px; line-height:1.6; overflow-wrap:anywhere }
.payout-amounts { display:grid; grid-template-columns:repeat(3,minmax(0,1fr)); gap:8px; margin-top:14px }
.payout-amounts>div { min-width:0; padding:10px; border:1px solid #e8e9ef; background:#fff; border-radius:9px }
.payout-amounts small { display:block; font-size:10px; color:#667085; margin-bottom:4px }
.payout-amounts strong { display:block; font-size:14px; font-weight:800; color:#25314a; overflow-wrap:anywhere }
.payout-amounts .payout-remaining { background:#f2ecff; border-color:#e5d9ff }
.payout-remaining strong { color:#6d28d9 }
.payout-source { padding:13px 14px; border:1px solid #e5e7eb; border-radius:11px; margin-bottom:18px; background:#f8fafc }
.payout-source-heading { display:flex; justify-content:space-between; gap:8px; align-items:center; margin-bottom:7px }
.payout-source-heading label { font-size:11px; font-weight:700; color:#667085; margin:0 }
.payout-auto { border-radius:20px; background:#e8f7f1; color:#047857; padding:3px 8px; font-size:10px; font-weight:700 }
.payout-source-name { font-size:13px; font-weight:700; color:#273448; overflow-wrap:anywhere }
.payout-source p { margin:5px 0 0; font-size:11px; color:#667085; line-height:1.5 }
.payout-fields { display:grid; grid-template-columns:minmax(0,1fr) minmax(0,1fr); gap:16px }
.pds-modal .pds-field { min-width:0; margin-bottom:16px }
.pds-modal .pds-field label { text-transform:none; letter-spacing:0; font-size:12px; color:#344054; margin-bottom:7px }
.pds-modal .pds-input, .pds-modal .pds-select, .pds-modal .pds-textarea { background:#fff; border:1px solid #dce0e8; border-radius:10px; font-size:13px; padding:11px 12px; line-height:1.5 }
.payout-currency { position:relative }
.payout-currency>span { position:absolute; left:12px; top:12px; font-size:13px; font-weight:700; color:#667085 }
.payout-currency .pds-input { padding-left:40px }
.pds-modal .pds-textarea { min-height:76px; max-height:150px }
.pds-modal .pds-input-hint { color:#667085; font-size:11px; line-height:1.5; margin-top:6px }
.payout-limit { color:#6d28d9; font-weight:700 }
.payout-upload-wrap { position:relative }
.payout-file-input { position:absolute; width:1px; height:1px; opacity:0 }
.pds-modal .payout-upload { display:flex; align-items:center; gap:12px; border:1px dashed #cbb9eb; border-radius:11px; padding:13px; background:#fcfaff; cursor:pointer; margin:0 }
.payout-upload>i { font-size:23px; color:#7c3aed }
.payout-upload>span { min-width:0; flex:1 }
.payout-upload strong { display:block; color:#493467; font-size:12px }
.payout-upload small { display:block; color:#667085; font-size:11px; overflow-wrap:anywhere; margin-top:3px }
.payout-upload em { flex-shrink:0; font-style:normal; border:1px solid #e2d6f4; border-radius:7px; background:#fff; padding:6px 9px; color:#6d28d9; font-size:11px; font-weight:700 }
.payout-file-input:focus + .payout-upload { outline:2px solid #7c3aed; outline-offset:2px }
.pds-modal .modal-footer { flex-shrink:0; display:flex; flex-wrap:nowrap; gap:9px; padding:14px 22px; border-top:1px solid #edf0f5; background:#fff; border-radius:0 }
.pds-modal .modal-footer>* { margin:0 }
.payout-button { display:inline-flex; align-items:center; justify-content:center; gap:7px; border:1px solid #dce0e8; border-radius:10px; padding:10px 17px; color:#475467; font-family:inherit; font-size:12px; font-weight:700; line-height:1.5; background:#fff; cursor:pointer }
.payout-button:hover { background:#f8fafc }
.payout-button.primary { color:#fff; border-color:#6d28d9; background:#6d28d9; box-shadow:0 3px 8px rgba(109,40,217,.15) }
.payout-button.primary:hover { background:#5b21b6 }
.payout-button:focus-visible { outline:3px solid #c4b5fd; outline-offset:2px }
.payout-button:disabled { opacity:.65; cursor:wait }

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
@media (max-width:575.98px) {
    .pds-modal .modal-dialog.modal-fullscreen-sm-down { width:100%; max-width:none; height:100%; margin:0 }
    .pds-modal .modal-content { border-radius:0; height:100dvh; max-height:100dvh; min-height:0 }
    .pds-modal [data-payout-form] { height:100%; max-height:100%; min-height:0 }
    .pds-modal .modal-header { padding:15px 16px }
    .pds-modal .modal-body { padding:16px }
    .pds-modal .modal-footer { padding:12px 16px }
    .payout-heading-icon { width:34px; height:34px; border-radius:10px; font-size:15px }
    .pds-modal .modal-title { font-size:15px }
    .payout-heading p { font-size:11px }
    .payout-summary { padding:12px }
    .payout-amounts { gap:5px }
    .payout-amounts>div { padding:8px 6px }
    .payout-amounts small { font-size:9px }
    .payout-amounts strong { font-size:11px }
    .payout-fields { grid-template-columns:1fr; gap:0 }
    .payout-upload em { display:none }
    .payout-button.primary { flex:1 }
}
</style>

<div class="pds-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pds-topbar">
        <h1>
            <i class="fas fa-file-invoice-dollar"></i>
            Detail Proposal untuk Pencairan
        </h1>
        <div style="display:flex;gap:8px;flex-wrap:wrap">
            <a href="{{ route('admin.pencairan.pdf', $proposal->id) }}" class="pds-back-btn"><i class="fas fa-file-pdf"></i> Cetak PDF Pencairan</a>
            <a href="{{ route('admin.pencairan.index') }}" class="pds-back-btn">Kembali</a>
        </div>
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

    @if($errors->any())
        <div class="pds-note danger mb-3" role="alert">
            <strong>Pencairan belum tersimpan.</strong>
            <ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
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
            <div class="s-value">Rp {{ number_format($proposal->total_anggaran, 2, ',', '.') }}</div>
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
                        <a href="{{ route('admin.documents.show', ['path' => $proposal->lampiran]) }}" target="_blank" class="pds-bukti-btn">
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
                            <td class="td-amount">Rp {{ number_format($proposal->total_anggaran_manual ?? $proposal->total_anggaran, 2, ',', '.') }}</td>
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
                                <td style="text-align:right" class="td-amount">Rp {{ number_format($item->anggaran,2,',','.') }}</td>
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
                                Rp {{ number_format($proposal->items->sum('anggaran'),2,',','.') }}
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
                            <span class="pds-mobile-item-price">Rp {{ number_format($item->anggaran, 2, ',', '.') }}</span>
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
                        <span class="value">Rp {{ number_format($proposal->items->sum('anggaran'), 2, ',', '.') }}</span>
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

        @if($totalCair > 0 && $totalRealisasi >= $totalCair)
            <div class="pds-note success m-3">
                <i class="fas fa-check-circle" style="margin-right:5px"></i>
                Nota mencakup dana yang sudah dicairkan
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
                        <td style="text-align:right" class="td-amount">Rp {{ number_format($nota->jumlah,2,',','.') }}</td>
                        <td style="text-align:center">
                            <a href="{{ route('admin.documents.show', ['path' => $nota->file_nota]) }}" target="_blank" class="pds-bukti-btn">
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
                            Rp {{ number_format($totalRealisasi,2,',','.') }}
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

    @php
        $rupiah = fn ($cents) => 'Rp '.number_format($cents / 100, 2, ',', '.');
        $needsPayout = in_array($proposal->status, ['disetujui_senior_pastor', 'dicairkan_sebagian']) && $remainingCents > 0;
    @endphp
    <div class="pds-disb-info">
        <div><span class="label">Total sudah dicairkan</span><div class="value">{{ $rupiah($paidCents) }}</div></div>
        <div><span class="label">Sisa anggaran yang belum dicairkan</span><div class="value">{{ $rupiah($remainingCents) }}</div></div>
        <div class="pds-input-hint" style="margin-top:10px">Pencairan boleh dilakukan beberapa tahap. Total seluruh tahap tidak boleh melebihi anggaran yang disetujui. Pengembalian dana tidak menambah batas pencairan proposal.</div>
    </div>

    <div class="pds-card" id="riwayat-pencairan">
        <div class="pds-card-header"><span>Riwayat pencairan per tahap</span></div>
        <div class="table-responsive">
            <table class="pds-table">
                <thead><tr><th style="width:auto">Tahap / Waktu</th><th style="width:auto">Rekening</th><th style="width:auto">Nominal / Metode</th><th style="width:auto">Catatan / Bukti</th></tr></thead>
                <tbody>
                @forelse($proposal->pencairan->sortBy('id') as $payment)
                    <tr>
                        <td>Tahap {{ $loop->iteration }}<br><small>{{ $payment->created_at?->timezone('Asia/Jakarta')->format('d/m/Y H:i') }} WIB</small></td>
                        <td>{{ $payment->rekening?->nama_rekening ?? '—' }}</td>
                        <td>{{ $rupiah(\App\Services\DepartmentBudgetService::cents($payment->jumlah_dana)) }}<br>{{ $payment->metode === 'transfer' ? 'Transfer bank' : 'Tunai' }}</td>
                        <td>{{ $payment->catatan ?: '—' }}@if($payment->bukti_transfer)<br><a class="pds-bukti-btn" href="{{ route('admin.documents.show', ['path'=>$payment->bukti_transfer]) }}" target="_blank" rel="noopener">Lihat bukti transfer</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="4">Belum ada pencairan dana.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if($needsPayout && $blockReason)
        <div class="pds-note danger mb-3" role="alert">{{ $blockReason }}</div>
    @endif
    @if($canDisburse)
        <button class="pds-btn-cair" data-bs-toggle="modal" data-bs-target="#cairModal{{ $proposal->id }}"><i class="fas fa-money-bill-wave"></i> Catat pencairan tahap berikutnya</button>
    @elseif($proposal->status === 'dicairkan' && $remainingCents === 0)
        <div class="pds-note success">Seluruh anggaran proposal telah dicairkan. Penyelesaian nota dan pengembalian sisa dana tetap dilakukan melalui detail proposal.</div>
    @endif
</div>

@if($canDisburse)
<div class="modal fade pds-modal" id="cairModal{{ $proposal->id }}" tabindex="-1" aria-hidden="true" aria-labelledby="payoutModalTitle">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-fullscreen-sm-down">
        <div class="modal-content">
            <form action="{{ route('admin.pencairan.store') }}" method="POST" enctype="multipart/form-data" data-payout-form>
                @csrf
                <input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">
                <input type="hidden" name="rekening_id" value="{{ $sourceAccount->id }}">
                <div class="modal-header">
                    <div class="payout-heading"><span class="payout-heading-icon" aria-hidden="true"><svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"><rect x="3" y="7" width="18" height="14" rx="2"></rect><path d="M3 9V5a2 2 0 0 1 2-2h13v4M16 12h5v5h-5a2.5 2.5 0 0 1 0-5Z"></path></svg></span><div><h5 class="modal-title" id="payoutModalTitle">Pencairan tahap {{ $proposal->pencairan->count() + 1 }}</h5><p>Catat dana yang dicairkan pada tahap ini.</p></div></div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Tutup"></button>
                </div>
                <div class="modal-body">
                    <section class="payout-summary" aria-label="Ringkasan proposal">
                        <h6>{{ $proposal->judul }}</h6>
                        <div class="payout-meta">Pemohon: {{ $proposal->user?->name ?? '—' }}<br>{{ $proposal->departemen->nama_departemen }} · Grup {{ $proposal->departemen->grup?->nama_grup ?? '—' }}</div>
                        <div class="payout-amounts">
                            <div><small>Anggaran disetujui</small><strong>{{ $rupiah(\App\Services\DepartmentBudgetService::cents($proposal->total_anggaran)) }}</strong></div>
                            <div><small>Sudah dicairkan</small><strong>{{ $rupiah($paidCents) }}</strong></div>
                            <div class="payout-remaining"><small>Belum dicairkan</small><strong>{{ $rupiah($remainingCents) }}</strong></div>
                        </div>
                    </section>
                    <section class="payout-source" aria-labelledby="labelRekeningSumber">
                        <div class="payout-source-heading"><label id="labelRekeningSumber">Rekening sumber grup · {{ $year }}</label><span class="payout-auto">Otomatis</span></div>
                        <div class="payout-source-name" id="rekeningSumber">{{ $sourceAccount->bank }} — {{ $sourceAccount->nama_rekening }}</div>
                        <p>Saldo bersama grup: {{ $rupiah(\App\Services\DepartmentBudgetService::cents($sourceAccount->saldo)) }}. Pencairan tetap mengikuti batas budget departemen.</p>
                    </section>
                    <div class="payout-fields">
                        <div class="pds-field">
                            <label for="nominalTahap">Nominal tahap ini <span aria-hidden="true" style="color:#dc2626">*</span></label>
                            <div class="payout-currency"><span aria-hidden="true">Rp</span><input class="pds-input" id="nominalTahap" type="number" name="jumlah_dana" min="0.01" step="0.01" max="{{ \App\Services\DepartmentBudgetService::amount($maximumCents) }}" value="{{ (string) old('proposal_id') === (string) $proposal->id ? old('jumlah_dana') : '' }}" placeholder="Contoh: 1000000" aria-describedby="payoutLimit" required></div>
                            <div class="pds-input-hint" id="payoutLimit">Maksimal <span class="payout-limit">{{ $rupiah($maximumCents) }}</span>. Tanpa pemisah ribuan.</div>
                        </div>
                        <div class="pds-field">
                            <label for="metodeTahap">Metode pencairan <span aria-hidden="true" style="color:#dc2626">*</span></label>
                            <select class="pds-select" name="metode" id="metodeTahap" required>
                                <option value="">Pilih metode</option>
                                <option value="cash" @selected(old('metode') === 'cash')>Tunai</option>
                                <option value="transfer" @selected(old('metode') === 'transfer')>Transfer bank</option>
                            </select>
                        </div>
                    </div>
                    <div class="pds-field">
                        <label for="catatanTahap">Catatan pencairan <span aria-hidden="true" style="color:#dc2626">*</span></label>
                        <textarea class="pds-textarea" name="catatan" id="catatanTahap" maxlength="500" rows="2" placeholder="Contoh: Uang muka pembelian mini PC" required>{{ old('catatan') }}</textarea>
                    </div>
                    <div class="pds-field" data-transfer-proof hidden>
                        <label>Bukti transfer <span aria-hidden="true" style="color:#dc2626">*</span></label>
                        <div class="payout-upload-wrap">
                            <input class="payout-file-input" type="file" name="bukti_transfer" id="buktiTahap" accept=".pdf,.jpg,.jpeg,.png">
                            <label for="buktiTahap" class="payout-upload"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c3aed" stroke-width="1.7" aria-hidden="true"><path d="M12 16V3m-4 4 4-4 4 4M4 15v5a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-5"></path></svg><span><strong>Pilih bukti transfer</strong><small data-file-name aria-live="polite">PDF, JPG, atau PNG · Maksimal 2 MB</small></span><em>Pilih file</em></label>
                        </div>
                    </div>
                    <p class="payout-tip"><i class="fas fa-info-circle" aria-hidden="true"></i> Boleh dicairkan sebagian. Sisa anggaran dapat dilanjutkan pada tahap berikutnya.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="payout-button" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="payout-button primary"><i class="fas fa-check" aria-hidden="true"></i> Simpan pencairan</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('[data-payout-form]');
    if (!form) return;
    const method = form.querySelector('[name="metode"]');
    const proof = form.querySelector('[name="bukti_transfer"]');
    function updateProof() {
        const transfer = method.value === 'transfer';
        form.querySelector('[data-transfer-proof]').hidden = !transfer;
        proof.required = transfer;
        proof.disabled = !transfer;
    }
    proof.addEventListener('change', function () {
        form.querySelector('[data-file-name]').textContent = proof.files[0]?.name || 'PDF, JPG, atau PNG · Maksimal 2 MB';
    });
    method.addEventListener('change', updateProof);
    updateProof();
    form.addEventListener('submit', function (event) {
        if (!form.checkValidity()) { event.preventDefault(); form.reportValidity(); return; }
        const button = form.querySelector('[type="submit"]');
        button.disabled = true;
        button.textContent = 'Menyimpan pencairan…';
    });
});
</script>
@endif
@endsection
