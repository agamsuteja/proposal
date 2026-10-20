@extends('backend.layouts.app')

@section('content')

@php
    $statusMap = [
        'diajukan'                => ['label'=>'Diajukan',           'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'disetujui_mh'            => ['label'=>'Disetujui MH',       'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'disetujui_gh'            => ['label'=>'Disetujui GH',       'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'disetujui_senior_pastor' => ['label'=>'Disetujui SP',       'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'dicairkan'               => ['label'=>'Dicairkan Penuh',     'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'dicairkan_sebagian'      => ['label'=>'Dicairkan Sebagian', 'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
        'ditolak'                 => ['label'=>'Ditolak',            'bg'=>'rgba(239,68,68,.25)',   'color'=>'#fecaca'],
        'revisi'                  => ['label'=>'Perlu Revisi',       'bg'=>'rgba(255,255,255,.18)', 'color'=>'#fff'],
    ];
    $st = $statusMap[$proposal->status] ?? ['label'=>ucfirst(str_replace('_',' ',$proposal->status)),'bg'=>'rgba(255,255,255,.18)','color'=>'#fff'];

    $jenisChip = [
        'barang'      => ['bg'=>'#dbeafe','color'=>'#1e40af','label'=>'BARANG'],
        'acara'       => ['bg'=>'#fef3c7','color'=>'#92400e','label'=>'ACARA'],
        'maintenance' => ['bg'=>'#ccfbf1','color'=>'#0f766e','label'=>'MAINTENANCE'],
    ];
    $jenis = $jenisChip[$proposal->jenis] ?? ['bg'=>'#f3f4f6','color'=>'#374151','label'=>strtoupper($proposal->jenis)];

    $currentFlow = $proposal->approval_flows->firstWhere('approver_id', Auth::id());
    $allApproved = $proposal->approval_flows->every(fn($f) => $f->status === 'disetujui');

    $tahapan = [
        'departemen_head' => 'Departemen Head',
        'group_head'      => 'Group Head',
        'gembala'         => 'Senior Pastor',
    ];
@endphp

<style>
/* ============================================================
   APPROVAL DETAIL — DESIGN SYSTEM
   ============================================================ */
.ap-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Header ── */
.ap-header { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.25rem 1.4rem; margin-bottom:1rem }
.ap-header-nav { color:#fff; font-size:14px; font-weight:700; margin:0 0 .875rem; display:flex; align-items:center; gap:7px }
.ap-header-body { display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:.875rem }
.ap-title { color:#fff; font-size:17px; font-weight:700; margin-bottom:5px; line-height:1.35 }
.ap-meta { color:rgba(255,255,255,.75); font-size:12px; display:flex; gap:14px; flex-wrap:wrap; margin-bottom:8px }
.ap-status-pill { display:inline-block; font-size:11px; font-weight:700; border-radius:20px; padding:4px 13px }
.ap-anggaran { color:#fff; font-size:22px; font-weight:800; text-align:right }
.ap-tanggal { color:rgba(255,255,255,.7); font-size:11.5px; text-align:right; margin-top:3px }

/* ── Cards ── */
.ap-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; margin-bottom:1rem; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.ap-card-hd { padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; background:#fafafa; display:flex; align-items:center; gap:7px; font-size:13px; font-weight:700; color:#111 }
.ap-card-hd .cdot { width:7px; height:7px; border-radius:50%; background:#7c3aed; flex-shrink:0 }
.ap-card-bd { padding:1.25rem }

/* ── Field grid ── */
.ap-field-grid { display:grid; grid-template-columns:1fr 1fr; gap:.875rem }
@media(max-width:560px){ .ap-field-grid { grid-template-columns:1fr } }
.ap-field .lbl { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; margin-bottom:4px; display:flex; align-items:center; gap:5px }
.ap-field .val { font-size:13.5px; font-weight:600; color:#111 }

/* ── Table ── */
.ap-table { width:100%; border-collapse:collapse; font-size:12.5px }
.ap-table thead th { background:#f9fafb; color:#6b7280; font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.04em; padding:9px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.ap-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .1s }
.ap-table tbody tr:last-child { border-bottom:none }
.ap-table tbody tr:hover td { background:#faf8ff }
.ap-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }
.ap-table tfoot td { padding:10px 14px; background:#f9fafb; font-weight:700; border-top:0.5px solid #e5e7eb }
.ap-num { background:#f3f4f6; color:#6b7280; border-radius:5px; padding:2px 7px; font-size:10.5px; font-weight:700 }
.td-money { text-align:right; font-weight:700; color:#059669 }

/* ── Timeline ── */
.ap-tl { padding:.75rem 1.25rem }
.ap-tl-row { display:flex; align-items:flex-start; gap:12px; padding:12px 0; border-bottom:0.5px solid #f3f4f6 }
.ap-tl-row:last-child { border-bottom:none }
.ap-tl-icon { width:34px; height:34px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:15px; flex-shrink:0 }
.tl-approved { background:#d1fae5; color:#059669 }
.tl-rejected  { background:#fee2e2; color:#dc2626 }
.tl-pending   { background:#fef3c7; color:#d97706 }
.tl-none      { background:#f3f4f6; color:#9ca3af }
.ap-tl-info { flex:1 }
.ap-tl-stage { font-size:13px; font-weight:700; color:#111; margin-bottom:2px }
.ap-tl-sub { font-size:11.5px; color:#6b7280 }
.ap-tl-note { font-size:11.5px; color:#6b7280; font-style:italic; margin-top:5px; padding:.5rem .75rem; border-left:2px solid #c4b5fd; border-radius:0 6px 6px 0; background:#faf8ff }
.ap-tl-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; flex-shrink:0; align-self:flex-start }
.tl-badge-approved { background:#d1fae5; color:#065f46 }
.tl-badge-rejected  { background:#fee2e2; color:#991b1b }
.tl-badge-pending   { background:#fef3c7; color:#92400e }
.tl-badge-none      { background:#f3f4f6; color:#6b7280 }

/* ── Alerts ── */
.ap-alert { border-radius:10px; padding:.875rem 1rem; font-size:13px; display:flex; align-items:flex-start; gap:10px; border:0.5px solid; margin-bottom:1rem }
.ap-alert.info    { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe }
.ap-alert.success { background:#ecfdf5; color:#065f46; border-color:#6ee7b7 }
.ap-alert.warn    { background:#fffbeb; color:#92400e; border-color:#fde68a }
.ap-alert.danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca }

/* ── Action Buttons ── */
.ap-actions { display:flex; gap:.75rem; flex-wrap:wrap }
.ap-btn { border:none; border-radius:9px; padding:11px 24px; font-size:13px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:7px; transition:opacity .15s; text-decoration:none }
.ap-btn:hover { opacity:.88; text-decoration:none }
.ap-btn-approve  { background:#059669; color:#fff }
.ap-btn-reject   { background:#dc2626; color:#fff }
.ap-btn-back     { background:#f3f4f6; color:#374151; border:0.5px solid #d1d5db }
.ap-btn-wa       { background:#16a34a; color:#fff }
.ap-btn-wa:hover { opacity:.88; color:#fff; text-decoration:none }

/* ── WA Sent Badge ── */
.wa-sent-badge { display:inline-flex; align-items:center; gap:6px; background:#dcfce7; color:#15803d; border-radius:8px; padding:9px 14px; font-size:12.5px; font-weight:700; border:0.5px solid #86efac; width:100%; justify-content:center }

/* ── Modal ── */
.ap-modal-backdrop { display:none; position:fixed; inset:0; background:rgba(0,0,0,.45); z-index:9999; align-items:center; justify-content:center }
.ap-modal-backdrop.show { display:flex }
.ap-modal { background:#fff; border-radius:14px; border:0.5px solid #e5e7eb; width:100%; max-width:480px; overflow:hidden; margin:1rem }
.ap-modal-hd { background:#dc2626; padding:.875rem 1.25rem; display:flex; justify-content:space-between; align-items:center }
.ap-modal-hd span { color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; gap:7px }
.ap-modal-bd { padding:1.25rem }
.ap-modal-ft { padding:.875rem 1.25rem; border-top:0.5px solid #f3f4f6; display:flex; justify-content:flex-end; gap:8px }
.ap-label { font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em; color:#6b7280; display:block; margin-bottom:4px }
.ap-input { width:100%; box-sizing:border-box; font-size:13px; padding:8px 11px; border-radius:8px; border:0.5px solid #d1d5db; background:#fff; color:#111; font-family:inherit }
.ap-input:focus { outline:none; border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.1) }
.ap-modal-btn-cancel { background:#f3f4f6; border:0.5px solid #d1d5db; border-radius:8px; padding:7px 16px; font-size:12.5px; font-weight:700; cursor:pointer; color:#374151; font-family:inherit }
.ap-modal-btn-submit { background:#dc2626; border:none; border-radius:8px; padding:7px 18px; font-size:12.5px; font-weight:700; cursor:pointer; color:#fff; display:flex; align-items:center; gap:5px; font-family:inherit }

/* ── Mobile item cards ── */
.ap-mob-item { background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:10px; padding:.875rem 1rem; margin-bottom:.625rem }
.ap-mob-item:last-child { margin-bottom:0 }

@media(max-width:640px){
    .ap-anggaran { font-size:17px }
    .ap-title { font-size:14px }
    .ap-card-bd { padding:1rem }
    .ap-table { font-size:11.5px }
    .ap-table thead th, .ap-table tbody td, .ap-table tfoot td { padding:8px 10px }
}
</style>

<div class="ap-wrap container-fluid">

    {{-- ═══════════ HEADER ═══════════ --}}
    <div class="ap-header">
        <div class="ap-header-nav">
            <i class="fas fa-file-invoice"></i>
            Detail Proposal — Approval
        </div>
        <div class="ap-header-body">
            <div>
                <div class="ap-title">{{ $proposal->judul }}</div>
                <div class="ap-meta">
                    <span><i class="fas fa-building fa-sm"></i> {{ $proposal->departemen->nama_departemen ?? '-' }}</span>
                    <span><i class="fas fa-user fa-sm"></i> {{ $proposal->user->name ?? '-' }}</span>
                </div>
                <span class="ap-status-pill" style="background:{{ $st['bg'] }};color:{{ $st['color'] }}">
                    ● {{ $st['label'] }}
                </span>
            </div>
            <div>
                <div class="ap-anggaran">Rp {{ number_format($proposal->total_anggaran, 0, ',', '.') }}</div>
                <div class="ap-tanggal">
                    <i class="fas fa-calendar fa-sm"></i>
                    {{ \Carbon\Carbon::parse($proposal->tanggal_pengajuan)->translatedFormat('d F Y') }}
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════ SESSION ALERTS ═══════════ --}}
    @if(session('warning'))
        <div class="ap-alert warn">
            <i class="fas fa-exclamation-triangle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('warning') }}</div>
        </div>
    @endif
    @if(session('success'))
        <div class="ap-alert success">
            <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- ═══════════ INFORMASI PROPOSAL ═══════════ --}}
    <div class="ap-card">
        <div class="ap-card-hd"><span class="cdot"></span> Informasi Proposal</div>
        <div class="ap-card-bd">
            <div class="ap-field-grid">
                <div class="ap-field">
                    <div class="lbl"><i class="fas fa-tag fa-sm"></i> Jenis Proposal</div>
                    <div class="val">
                        <span style="background:{{ $jenis['bg'] }};color:{{ $jenis['color'] }};border-radius:6px;padding:3px 10px;font-size:11px;font-weight:700;display:inline-block">
                            {{ $jenis['label'] }}
                        </span>
                    </div>
                </div>
                <div class="ap-field">
                    <div class="lbl"><i class="fas fa-calendar fa-sm"></i> Tanggal Pengajuan</div>
                    <div class="val">{{ \Carbon\Carbon::parse($proposal->tanggal_pengajuan)->translatedFormat('d F Y') }}</div>
                </div>
                @if($proposal->lampiran)
                <div class="ap-field" style="margin-top:.625rem">
                    <div class="lbl"><i class="fas fa-paperclip fa-sm"></i> Lampiran</div>
                    <div class="val">
                        <a href="{{ asset('storage/'.$proposal->lampiran) }}" target="_blank"
                           style="background:#ede9fe;color:#5b21b6;border:none;border-radius:7px;font-size:12px;font-weight:700;padding:5px 12px;cursor:pointer;display:inline-flex;align-items:center;gap:5px;text-decoration:none">
                            <i class="fas fa-eye"></i> Lihat Dokumen
                        </a>
                    </div>
                </div>
                @endif
                @if($proposal->tujuan)
                <div class="ap-field" style="margin-top:.625rem">
                    <div class="lbl"><i class="fas fa-bullseye fa-sm"></i> Tujuan</div>
                    <div class="val">{{ $proposal->tujuan }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════ DETAIL ACARA ═══════════ --}}
    @if($proposal->jenis === 'acara')
    <div class="ap-card">
        <div class="ap-card-hd"><span class="cdot"></span> Detail Acara</div>
        <div class="ap-card-bd">
            <div class="ap-field-grid">
                <div class="ap-field">
                    <div class="lbl"><i class="fas fa-calendar-day fa-sm"></i> Tanggal Acara</div>
                    <div class="val">{{ $proposal->tanggal_acara ? \Carbon\Carbon::parse($proposal->tanggal_acara)->translatedFormat('d F Y') : '-' }}</div>
                </div>
                <div class="ap-field">
                    <div class="lbl"><i class="fas fa-map-marker-alt fa-sm"></i> Lokasi</div>
                    <div class="val">{{ $proposal->lokasi_acara ?? '-' }}</div>
                </div>
                @if($proposal->keterangan)
                <div class="ap-field" style="grid-column:1/-1;margin-top:.5rem">
                    <div class="lbl"><i class="fas fa-sticky-note fa-sm"></i> Keterangan</div>
                    <div class="val" style="font-size:13px;font-weight:400;color:#374151;line-height:1.6">{{ $proposal->keterangan }}</div>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════ DAFTAR BARANG / MAINTENANCE ═══════════ --}}
    @if(in_array($proposal->jenis, ['barang','maintenance']))
    <div class="ap-card">
        <div class="ap-card-hd">
            <span class="cdot"></span>
            {{ $proposal->jenis === 'maintenance' ? 'Rincian Maintenance' : 'Daftar Barang Diajukan' }}
            <span style="margin-left:auto;font-size:10.5px;background:#ede9fe;color:#5b21b6;border-radius:20px;padding:2px 10px;font-weight:700">
                {{ $proposal->items->count() }} item
            </span>
        </div>

        {{-- Desktop Table --}}
        <div class="d-none d-md-block" style="overflow-x:auto">
            <table class="ap-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        @if($proposal->jenis === 'maintenance')
                            <th>Nama Alat</th><th>Jenis Perawatan</th><th>Vendor / Teknisi</th>
                        @else
                            <th>Nama Barang</th><th>Merk / Tipe</th><th style="text-align:center;width:55px">Qty</th><th>Tujuan</th>
                        @endif
                        <th style="text-align:right;width:140px">Anggaran</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposal->items as $i => $item)
                    <tr>
                        <td><span class="ap-num">{{ $i+1 }}</span></td>
                        @if($proposal->jenis === 'maintenance')
                            <td style="font-weight:600">{{ $item->nama_barang }}</td>
                            <td style="color:#6b7280">{{ $item->tujuan_pembelian ?? '-' }}</td>
                            <td style="color:#6b7280">{{ $item->merk_tipe ?? '-' }}</td>
                        @else
                            <td style="font-weight:600">{{ $item->nama_barang }}</td>
                            <td style="color:#6b7280">{{ $item->merk_tipe ?? '-' }}</td>
                            <td style="text-align:center">{{ $item->jumlah ?? '-' }}</td>
                            <td style="color:#6b7280">{{ $item->tujuan_pembelian ?? '-' }}</td>
                        @endif
                        <td class="td-money">Rp {{ number_format($item->anggaran, 0, ',', '.') }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="{{ $proposal->jenis === 'barang' ? 6 : 5 }}" style="text-align:center;padding:2.5rem;color:#9ca3af">
                            <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                            Tidak ada data
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="{{ $proposal->jenis === 'barang' ? 5 : 4 }}" style="text-align:right;color:#6b7280;font-size:12px;font-weight:500">Total Anggaran</td>
                        <td class="td-money" style="font-size:13.5px">Rp {{ number_format($proposal->total_anggaran, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Mobile Cards --}}
        <div class="d-md-none" style="padding:.875rem 1rem">
            @forelse($proposal->items as $i => $item)
            <div class="ap-mob-item">
                <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:.5rem">
                    <div style="font-weight:700;font-size:13px;color:#111;flex:1;padding-right:.5rem">{{ $item->nama_barang }}</div>
                    <div style="font-weight:700;font-size:13px;color:#059669;white-space:nowrap">Rp {{ number_format($item->anggaran, 0, ',', '.') }}</div>
                </div>
                @if($proposal->jenis === 'maintenance')
                    <div style="font-size:11.5px;color:#6b7280">Perawatan: {{ $item->tujuan_pembelian ?? '-' }} · Vendor: {{ $item->merk_tipe ?? '-' }}</div>
                @else
                    <div style="font-size:11.5px;color:#6b7280">{{ $item->merk_tipe ?? '-' }} · Qty: {{ $item->jumlah ?? '-' }} · {{ $item->tujuan_pembelian ?? '-' }}</div>
                @endif
            </div>
            @empty
            <div style="text-align:center;padding:1.5rem;color:#9ca3af">
                <i class="fas fa-inbox fa-2x" style="margin-bottom:.5rem;display:block"></i>Tidak ada data
            </div>
            @endforelse
            @if($proposal->items->count() > 0)
            <div style="background:#f9fafb;border-radius:9px;padding:.875rem 1rem;margin-top:.75rem;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:12px;color:#6b7280;font-weight:600">Total Anggaran</span>
                <span style="font-size:16px;font-weight:700;color:#059669">Rp {{ number_format($proposal->total_anggaran, 0, ',', '.') }}</span>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ═══════════ TIMELINE PERSETUJUAN ═══════════ --}}
    <div class="ap-card">
        <div class="ap-card-hd"><span class="cdot"></span> Riwayat Persetujuan</div>
        <div class="ap-tl">
            @foreach($tahapan as $key => $label)
            @php
                $flow = $proposal->approval_flows->firstWhere('tahap', $key);
                $fClass      = $flow ? match($flow->status) { 'disetujui'=>'tl-approved','ditolak'=>'tl-rejected',default=>'tl-pending' } : 'tl-none';
                $fIcon       = $flow ? match($flow->status) { 'disetujui'=>'fa-check','ditolak'=>'fa-times',default=>'fa-clock' } : 'fa-ellipsis-h';
                $fBadgeClass = $flow ? match($flow->status) { 'disetujui'=>'tl-badge-approved','ditolak'=>'tl-badge-rejected',default=>'tl-badge-pending' } : 'tl-badge-none';
                $fBadgeLabel = $flow ? match($flow->status) { 'disetujui'=>'Disetujui','ditolak'=>'Ditolak',default=>'Pending' } : 'Belum';
            @endphp
            <div class="ap-tl-row">
                <div class="ap-tl-icon {{ $fClass }}">
                    <i class="fas {{ $fIcon }}"></i>
                </div>
                <div class="ap-tl-info">
                    <div class="ap-tl-stage">{{ $label }}</div>
                    <div class="ap-tl-sub">
                        <i class="fas fa-user fa-sm"></i>
                        {{ $flow->approver->name ?? 'Belum ditentukan' }}
                        @if($flow && $flow->tanggal_approve)
                            &nbsp;·&nbsp;<i class="fas fa-calendar fa-sm"></i>
                            {{ \Carbon\Carbon::parse($flow->tanggal_approve)->format('d/m/Y H:i') }}
                        @endif
                    </div>
                    @if($flow && $flow->catatan)
                        <div class="ap-tl-note">{{ $flow->catatan }}</div>
                    @endif
                </div>
                <span class="ap-tl-badge {{ $fBadgeClass }}">{{ $fBadgeLabel }}</span>
            </div>
            @endforeach
        </div>
    </div>

    {{-- ═══════════ TINDAKAN APPROVAL ═══════════ --}}
    <div class="ap-card">
        <div class="ap-card-hd"><span class="cdot"></span> Tindakan Approval</div>
        <div class="ap-card-bd">

            @if($canApprove && $currentFlow && $currentFlow->status == 'pending')
                <div class="ap-alert warn">
                    <i class="fas fa-exclamation-triangle" style="flex-shrink:0;margin-top:1px"></i>
                    <div>
                        <span style="font-weight:700;display:block;margin-bottom:2px">Giliran Anda Meninjau</span>
                        Harap tinjau proposal ini dengan seksama sebelum mengambil keputusan.
                    </div>
                </div>
                <div class="ap-actions">
                    {{-- Form approve --}}
                    <form id="approveForm" action="{{ route('admin.approval.update', $currentFlow->id) }}" method="POST">
                        @csrf @method('PUT')
                        <input type="hidden" name="status" value="disetujui">

                        @if(Auth::user()->role === 'group_head')
                            {{-- Group Head: tampilkan modal konfirmasi keuangan dulu --}}
                            <button type="button" class="ap-btn ap-btn-approve"
                                    onclick="document.getElementById('keuanganModal').classList.add('show')">
                                <i class="fas fa-check-circle"></i> Setujui Proposal
                            </button>
                        @else
                            <button type="submit" class="ap-btn ap-btn-approve">
                                <i class="fas fa-check-circle"></i> Setujui Proposal
                            </button>
                        @endif
                    </form>

                    <button type="button" class="ap-btn ap-btn-reject"
                            onclick="document.getElementById('rejectModal').classList.add('show')">
                        <i class="fas fa-times-circle"></i> Tolak Proposal
                    </button>

                    <a href="{{ route('admin.proposal.index') }}" class="ap-btn ap-btn-back">
                        <i class="fas fa-arrow-left"></i> Kembali
                    </a>
                </div>

            @elseif($allApproved && $proposal->status === 'disetujui_senior_pastor')
                <div class="ap-alert success">
                    <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px;font-size:1.1rem"></i>
                    <div>
                        <span style="font-weight:700;display:block;margin-bottom:2px">Proposal Disetujui Penuh</span>
                        Semua pihak telah menyetujui. Proposal menunggu pencairan dana oleh tim keuangan.
                    </div>
                </div>
                <!-- <a href="{{ route('admin.proposal.index') }}" class="ap-btn ap-btn-back" style="margin-top:.25rem">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a> -->

            @elseif(!$canApprove)
                <div class="ap-alert info">
                    <i class="fas fa-hourglass-half" style="flex-shrink:0;margin-top:1px;font-size:1.1rem"></i>
                    <div>
                        <span style="font-weight:700;display:block;margin-bottom:2px">Menunggu Persetujuan</span>
                        Proposal ini masih dalam proses persetujuan dari tahap sebelumnya.
                    </div>
                </div>
                <!-- <a href="{{ route('admin.proposal.index') }}" class="ap-btn ap-btn-back" style="margin-top:.25rem">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>

            @else
                <a href="{{ route('admin.proposal.index') }}" class="ap-btn ap-btn-back">
                    <i class="fas fa-arrow-left"></i> Kembali ke Daftar
                </a>
            @endif -->

        </div>
    </div>

</div>{{-- end .ap-wrap --}}

{{-- ═══════════ MODAL TOLAK ═══════════ --}}
@if($currentFlow)
<div id="rejectModal" class="ap-modal-backdrop" onclick="if(event.target===this)this.classList.remove('show')">
    <div class="ap-modal">
        <form action="{{ route('admin.approval.update', $currentFlow->id) }}" method="POST">
            @csrf @method('PUT')
            <input type="hidden" name="status" value="ditolak">
            <div class="ap-modal-hd">
                <span><i class="fas fa-times-circle"></i> Tolak Proposal</span>
                <button type="button"
                        onclick="document.getElementById('rejectModal').classList.remove('show')"
                        style="background:rgba(255,255,255,.2);border:none;border-radius:6px;color:#fff;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="ap-modal-bd">
                <div class="ap-alert warn" style="margin-bottom:.875rem;font-size:12.5px">
                    <i class="fas fa-exclamation-triangle" style="flex-shrink:0;margin-top:1px"></i>
                    <div>Penolakan memerlukan catatan yang jelas agar pemohon dapat melakukan revisi yang tepat.</div>
                </div>
                <label class="ap-label" for="catatan-tolak">
                    Catatan Penolakan <span style="color:#dc2626">*</span>
                </label>
                <textarea id="catatan-tolak"
                          name="catatan"
                          class="ap-input"
                          rows="4"
                          required
                          placeholder="Jelaskan alasan penolakan secara detail..."></textarea>
            </div>
            <div class="ap-modal-ft">
                <button type="button" class="ap-modal-btn-cancel"
                        onclick="document.getElementById('rejectModal').classList.remove('show')">
                    Batal
                </button>
                <button type="submit" class="ap-modal-btn-submit">
                    <i class="fas fa-times-circle"></i> Tolak Proposal
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- ═══════════ MODAL KONFIRMASI KEUANGAN (Group Head only) ═══════════ --}}
@if($currentFlow && Auth::user()->role === 'group_head')
<div id="keuanganModal" class="ap-modal-backdrop" onclick="if(event.target===this)this.classList.remove('show')">
    <div class="ap-modal" style="max-width:500px">
        <div class="ap-modal-hd" style="background:#d97706">
            <span><i class="fas fa-phone-alt"></i> Konfirmasi Sebelum Menyetujui</span>
            <button type="button"
                    onclick="document.getElementById('keuanganModal').classList.remove('show')"
                    style="background:rgba(255,255,255,.2);border:none;border-radius:6px;color:#fff;width:28px;height:28px;cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:14px">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="ap-modal-bd">

            <div class="ap-alert warn" style="margin-bottom:1rem;font-size:12.5px">
                <i class="fas fa-exclamation-triangle" style="flex-shrink:0;margin-top:1px"></i>
                <div>Sebelum meneruskan proposal ini ke Senior Pastor, pastikan tim keuangan sudah mengetahui dan dikonfirmasi terlebih dahulu.</div>
            </div>

            <p style="font-size:14px;font-weight:700;color:#111;margin:0 0 1rem;text-align:center;line-height:1.6">
                Apakah Anda sudah menghubungi tim keuangan sebelumnya?
            </p>

            {{-- Panel kirim WA ke Keuangan --}}
            <div style="background:#f0fdf4;border:0.5px solid #86efac;border-radius:10px;padding:.875rem 1rem;margin-bottom:1.125rem">
                <p style="font-size:12px;color:#166534;font-weight:600;margin:0 0 .625rem;display:flex;align-items:center;gap:6px">
                    <i class="fab fa-whatsapp" style="font-size:15px"></i>
                    Belum menghubungi? Kirim notifikasi WhatsApp sekarang:
                </p>

                {{-- Badge sukses (tersembunyi sampai WA berhasil dikirim) --}}
                <div id="waSentState" style="display:none" class="wa-sent-badge">
                    <i class="fas fa-check-circle"></i> Pesan berhasil dikirim ke tim keuangan
                </div>

                {{-- ✅ Form AJAX — action mengarah ke route notif-keuangan --}}
                <form id="waKeuanganForm"
                      action="{{ route('admin.approval.notif-keuangan', $proposal->id) }}"
                      method="POST">
                    @csrf
                    <button type="button"
                            id="btnKirimWA"
                            class="ap-btn ap-btn-wa"
                            style="width:100%;justify-content:center"
                            onclick="kirimWAKeuangan()">
                        <i class="fab fa-whatsapp"></i> Kirim Pesan ke Tim Keuangan
                    </button>
                </form>
            </div>

            {{-- Tombol aksi: Batal / Sudah --}}
            <div style="display:flex;gap:.75rem">
                <button type="button"
                        class="ap-btn ap-btn-back"
                        style="flex:1;justify-content:center"
                        onclick="document.getElementById('keuanganModal').classList.remove('show')">
                    <i class="fas fa-times"></i> Belum — Batalkan
                </button>
                <button type="button"
                        class="ap-btn ap-btn-approve"
                        style="flex:1;justify-content:center"
                        onclick="document.getElementById('keuanganModal').classList.remove('show');
                                 document.getElementById('approveForm').submit()">
                    <i class="fas fa-check"></i> Sudah — Setujui
                </button>
            </div>

        </div>
    </div>
</div>
@endif

<script>
// ── Tutup semua modal dengan tombol ESC ──
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        ['rejectModal', 'keuanganModal'].forEach(function(id) {
            var m = document.getElementById(id);
            if (m) m.classList.remove('show');
        });
    }
});

// ── Kirim WA ke Keuangan via AJAX ──
function kirimWAKeuangan() {
    var btn  = document.getElementById('btnKirimWA');
    var form = document.getElementById('waKeuanganForm');
    var sent = document.getElementById('waSentState');

    // Tampilkan loading
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Mengirim...';

    var formData = new FormData(form);

    fetch(form.action, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': formData.get('_token'),
            'Accept': 'application/json',
        },
        body: formData,
    })
    .then(function(res) { return res.json(); })
    .then(function(data) {
        if (data.success) {
            // Sembunyikan tombol, tampilkan badge sukses
            btn.style.display  = 'none';
            sent.style.display = 'flex';
        } else {
            btn.disabled = false;
            btn.innerHTML = '<i class="fab fa-whatsapp"></i> Kirim Pesan ke Tim Keuangan';
            alert('Gagal mengirim pesan: ' + (data.message || 'Terjadi kesalahan.'));
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.innerHTML = '<i class="fab fa-whatsapp"></i> Kirim Pesan ke Tim Keuangan';
        alert('Terjadi kesalahan jaringan, silakan coba lagi.');
    });
}
</script>

@endsection