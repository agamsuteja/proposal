@extends('backend.layouts.app')

@section('content')

@php
    $badgeMap = [
        'diajukan'               => ['label' => 'Diajukan',               'class' => 'pd-badge-secondary'],
        'disetujui_mh'           => ['label' => 'Disetujui MH',           'class' => 'pd-badge-blue'],
        'disetujui_gh'           => ['label' => 'Disetujui GH',           'class' => 'pd-badge-blue'],
        'disetujui_senior_pastor'=> ['label' => 'Disetujui Senior Pastor','class' => 'pd-badge-success'],
        'dicairkan'              => ['label' => 'Dicairkan Penuh',         'class' => 'pd-badge-success'],
        'dicairkan_sebagian'     => ['label' => 'Dicairkan Sebagian',      'class' => 'pd-badge-warning'],
        'ditolak'                => ['label' => 'Ditolak',                 'class' => 'pd-badge-danger'],
        'dana_dikembalikan'      => ['label' => 'Dana Dikembalikan',       'class' => 'pd-badge-blue'],
        'revisi'                 => ['label' => 'Perlu Revisi',            'class' => 'pd-badge-warning'],
    ];
    $badge  = $badgeMap[$proposal->status] ?? ['label' => ucfirst(str_replace('_',' ',$proposal->status)), 'class' => 'pd-badge-secondary'];
    $jenisLabel = ['acara' => 'ACARA', 'barang' => 'BARANG', 'maintenance' => 'MAINTENANCE'];

    /* Pencairan & realisasi */
    $totalCair  = optional($proposal->pencairan)->sum('jumlah_dana') ?? 0;
    $totalNota  = optional($proposal->nota_penggunaan)->sum('jumlah') ?? 0;
    $sisaDana   = $totalCair - $totalNota;
    $persen     = $proposal->total_anggaran > 0
                    ? round(($totalCair / $proposal->total_anggaran) * 100, 1)
                    : 0;
    $persenColor = $persen >= 100 ? '#10b981' : ($persen > 0 ? '#f59e0b' : '#9ca3af');

    /* Pengembalian */
    $pengembalian           = $proposal->pengembalianDana ?? collect();
    $pengembalianDiterima   = $pengembalian->where('status','diterima')->first();
    $pengembalianPending    = $pengembalian->where('status','pending')->first();
    $totalDiterima          = $pengembalian->whereIn('status',['diterima'])->sum('jumlah');
    $sisaYangHarusDikembalikan = $sisaDana - $totalDiterima;
@endphp

<style>
/* ============================================================
   PROPOSAL DETAIL — DESIGN SYSTEM
   ============================================================ */
.pd-wrap{font-family:'Nunito',system-ui,sans-serif;color:#1a1a1a;padding:1.5rem 0 3rem}

/* ── Topbar ── */
.pd-topbar{background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%);border-radius:14px;padding:1.25rem 1.5rem;display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem}
.pd-topbar h1{color:#fff;font-size:15px;font-weight:700;margin:0;display:flex;align-items:center;gap:8px}
.pd-topbar-actions{display:flex;gap:8px}
.pd-btn{font-size:12px;font-weight:600;border-radius:8px;padding:7px 16px;border:none;cursor:pointer;display:inline-flex;align-items:center;gap:5px;text-decoration:none;transition:opacity .15s}
.pd-btn:hover{opacity:.85;text-decoration:none}
.pd-btn-edit{background:rgba(255,255,255,.15);color:#fff;border:1px solid rgba(255,255,255,.3)}
.pd-btn-back{background:#fff;color:#4c1d95}

/* ── Status bar ── */
.pd-statusbar{background:#f8f7ff;border:0.5px solid #ddd8f7;border-radius:12px;padding:1rem 1.25rem;display:flex;justify-content:space-between;align-items:center;margin-bottom:1.25rem;flex-wrap:wrap;gap:.75rem}
.pd-statusbar .sb-label{font-size:10.5px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.05em;margin-bottom:3px}
.pd-statusbar .sb-title{font-size:14px;font-weight:700;color:#111}

/* ── Badges ── */
.pd-badge{font-size:11px;font-weight:700;border-radius:20px;padding:5px 14px;white-space:nowrap;display:inline-block}
.pd-badge-secondary{background:#e5e7eb;color:#374151}
.pd-badge-blue{background:#dbeafe;color:#1e40af}
.pd-badge-success{background:#d1fae5;color:#065f46}
.pd-badge-warning{background:#fef3c7;color:#92400e}
.pd-badge-danger{background:#fee2e2;color:#991b1b}
.pd-badge-purple{background:#ede9fe;color:#5b21b6}

/* ── Cards ── */
.pd-card{background:#fff;border:0.5px solid #e5e7eb;border-radius:14px;overflow:hidden;margin-bottom:1.25rem;box-shadow:0 1px 4px rgba(0,0,0,.04)}
.pd-card-header{padding:.875rem 1.25rem;border-bottom:0.5px solid #f3f4f6;display:flex;justify-content:space-between;align-items:center;background:#fafafa;flex-wrap:wrap;gap:.5rem}
.pd-card-header h2{font-size:13px;font-weight:700;margin:0;color:#111;display:flex;align-items:center;gap:7px}
.pd-card-dot{width:7px;height:7px;border-radius:50%;background:#7c3aed;flex-shrink:0}
.pd-card-body{padding:1.25rem}

/* ── Field layout ── */
.pd-field-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
@media(max-width:600px){.pd-field-grid{grid-template-columns:1fr}}
.pd-field{margin-bottom:0}
.pd-field-label{font-size:10.5px;font-weight:600;color:#6b7280;margin-bottom:4px;display:flex;align-items:center;gap:5px;text-transform:uppercase;letter-spacing:.04em}
.pd-field-value{font-size:13.5px;font-weight:600;color:#111}
.pd-field-value.money{font-size:20px;font-weight:700;color:#059669}
.pd-dept-chip{font-size:11px;font-weight:700;border-radius:6px;padding:3px 10px;display:inline-block}

/* ── Metrics ── */
.pd-metrics{display:grid;grid-template-columns:repeat(3,1fr);gap:.75rem;margin-bottom:1rem}
@media(max-width:500px){.pd-metrics{grid-template-columns:1fr 1fr}}
.pd-metric{background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px;padding:.75rem 1rem}
.pd-metric .m-label{font-size:10px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;margin-bottom:4px}
.pd-metric .m-value{font-size:15px;font-weight:700;color:#111}
.pd-metric .m-value.green{color:#059669}
.pd-metric .m-value.amber{color:#d97706}
.pd-metric .m-value.red{color:#dc2626}

/* ── Progress bar ── */
.pd-progress-wrap{margin-top:.25rem}
.pd-progress-label{display:flex;justify-content:space-between;font-size:11px;color:#6b7280;margin-bottom:5px}
.pd-progress-track{height:6px;background:#f3f4f6;border-radius:10px;overflow:hidden;border:0.5px solid #e5e7eb}
.pd-progress-fill{height:100%;border-radius:10px;transition:width .4s}

/* ── Table ── */
.pd-table{width:100%;border-collapse:collapse;font-size:12.5px}
.pd-table thead th{background:#f9fafb;color:#6b7280;font-weight:600;font-size:10.5px;text-transform:uppercase;letter-spacing:.04em;padding:9px 14px;text-align:left;border-bottom:0.5px solid #e5e7eb;white-space:nowrap}
.pd-table tbody tr{border-bottom:0.5px solid #f3f4f6}
.pd-table tbody tr:last-child{border-bottom:none}
.pd-table tbody tr:hover{background:#fafafe}
.pd-table tbody td{padding:11px 14px;color:#374151;vertical-align:middle}
.pd-table tfoot td{background:#f9fafb;padding:10px 14px;font-weight:700;font-size:12.5px;border-top:0.5px solid #e5e7eb}
.pd-num{background:#f3f4f6;color:#6b7280;border-radius:5px;padding:2px 7px;font-size:10.5px;font-weight:700}
.td-money{text-align:right;font-weight:700;color:#059669}
.td-money-neg{text-align:right;font-weight:700;color:#d97706}
.th-right{text-align:right!important}

/* ── Alert boxes ── */
.pd-alert{border-radius:10px;padding:.875rem 1rem;font-size:13px;margin-bottom:.75rem;display:flex;align-items:flex-start;gap:10px;border-width:0.5px;border-style:solid}
.pd-alert i{flex-shrink:0;margin-top:1px;font-size:16px}
.pd-alert.info{background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe}
.pd-alert.warning{background:#fffbeb;color:#92400e;border-color:#fde68a}
.pd-alert.success{background:#ecfdf5;color:#065f46;border-color:#6ee7b7}
.pd-alert.danger{background:#fef2f2;color:#991b1b;border-color:#fecaca}

/* ── Approval flow ── */
.pd-flow-row{display:flex;align-items:flex-start;gap:12px;padding:12px 0;border-bottom:0.5px solid #f3f4f6}
.pd-flow-row:last-child{border-bottom:none}
.pd-flow-icon{width:32px;height:32px;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:15px}
.pd-flow-icon.approved{background:#d1fae5;color:#059669}
.pd-flow-icon.pending{background:#fef3c7;color:#d97706}
.pd-flow-icon.rejected{background:#fee2e2;color:#dc2626}
.pd-flow-info{flex:1}
.pd-flow-stage{font-size:13px;font-weight:700;color:#111;margin-bottom:2px}
.pd-flow-sub{font-size:11.5px;color:#6b7280}
.pd-flow-note{font-size:11.5px;color:#6b7280;font-style:italic;margin-top:2px}
.pd-flow-badge{font-size:10.5px;font-weight:700;border-radius:20px;padding:3px 10px;flex-shrink:0;align-self:flex-start}
.pd-flow-badge.approved{background:#d1fae5;color:#065f46}
.pd-flow-badge.pending{background:#fef3c7;color:#92400e}
.pd-flow-badge.rejected{background:#fee2e2;color:#991b1b}

/* ── Upload nota form ── */
.pd-nota-item{background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px;padding:1rem;margin-bottom:.75rem}
.pd-nota-item .row-2{display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem}
@media(max-width:500px){.pd-nota-item .row-2{grid-template-columns:1fr}}
.pd-label{font-size:10.5px;font-weight:600;color:#6b7280;text-transform:uppercase;letter-spacing:.04em;display:block;margin-bottom:4px}
.pd-input{width:100%;box-sizing:border-box;font-size:13px;padding:7px 11px;border-radius:8px;border:0.5px solid #d1d5db;background:#fff;color:#111;font-family:inherit}
.pd-input:focus{outline:none;border-color:#7c3aed;box-shadow:0 0 0 3px rgba(124,58,237,.1)}
.pd-textarea{resize:vertical;min-height:60px}
.pd-select{width:100%;box-sizing:border-box;font-size:13px;padding:7px 11px;border-radius:8px;border:0.5px solid #d1d5db;background:#fff;color:#111;font-family:inherit;cursor:pointer}
.pd-input-group{display:flex;border:0.5px solid #d1d5db;border-radius:8px;overflow:hidden}
.pd-input-group span{background:#f3f4f6;padding:7px 11px;font-size:12px;color:#6b7280;border-right:0.5px solid #d1d5db;white-space:nowrap}
.pd-input-group input{border:none;flex:1;padding:7px 11px;font-size:13px;color:#111;font-family:inherit;background:#fff}
.pd-input-group input:focus{outline:none}

/* ── Buttons ── */
.pd-btn-primary{background:linear-gradient(135deg,#7c3aed,#4c1d95);color:#fff;border:none;border-radius:8px;padding:9px 22px;font-size:13px;font-weight:700;cursor:pointer;display:inline-flex;align-items:center;gap:7px;font-family:inherit;transition:opacity .15s}
.pd-btn-primary:hover{opacity:.9}
.pd-btn-add{background:#fff;border:0.5px solid #d1d5db;color:#374151;border-radius:8px;padding:7px 14px;font-size:12px;font-weight:600;cursor:pointer;display:inline-flex;align-items:center;gap:5px;font-family:inherit;transition:background .15s}
.pd-btn-add:hover{background:#f9fafb}
.pd-btn-remove{background:#fee2e2;border:none;color:#dc2626;border-radius:7px;padding:7px 11px;cursor:pointer;font-family:inherit;line-height:1}
.pd-btn-view{background:#ede9fe;color:#5b21b6;border:none;border-radius:7px;font-size:11px;font-weight:600;padding:4px 10px;cursor:pointer;display:inline-flex;align-items:center;gap:4px;text-decoration:none}
.pd-btn-view:hover{opacity:.85;text-decoration:none}

/* ── Upload zone ── */
.pd-upload-zone{border:1.5px dashed #c4b5fd;border-radius:10px;padding:1.25rem;text-align:center;background:#faf9ff;cursor:pointer;transition:border-color .15s}
.pd-upload-zone:hover{border-color:#7c3aed}
.pd-upload-zone p{margin:0;font-size:12px;color:#6b7280}
.pd-upload-zone strong{color:#7c3aed}
.pd-filename{font-size:12px;color:#059669;font-weight:600;margin-top:.5rem}

/* ── Section divider ── */
.pd-divider{border:none;border-top:0.5px solid #f3f4f6;margin:1rem 0}

/* ── Mobile tweaks ── */
@media(max-width:640px){
    .pd-card-body{padding:1rem}
    .pd-table{font-size:12px}
    .pd-table thead th,.pd-table tbody td,.pd-table tfoot td{padding:8px 10px}
}
</style>

<div class="pd-wrap container-fluid">

    {{-- ═══════════ TOPBAR ═══════════ --}}
    <div class="pd-topbar">
        <h1>
            <i class="fas fa-file-invoice"></i>
            Detail Proposal
        </h1>
        <div class="pd-topbar-actions">
            @if(in_array($proposal->status, ['diajukan','revisi']))
                <a href="{{ route('admin.proposal.edit', $proposal->id) }}" class="pd-btn pd-btn-edit">
                    <i class="fas fa-edit"></i> Edit
                </a>
            @endif
            <a href="{{ route('admin.proposal.index') }}" class="pd-btn pd-btn-back">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    {{-- ═══════════ STATUS BAR ═══════════ --}}
    <div class="pd-statusbar">
        <div>
            <div class="sb-label">Judul Proposal</div>
            <div class="sb-title">{{ $proposal->judul }}</div>
        </div>
        <span class="pd-badge {{ $badge['class'] }}">● {{ $badge['label'] }}</span>
    </div>

    {{-- ═══════════ INFORMASI UMUM ═══════════ --}}
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Informasi Umum</h2>
        </div>
        <div class="pd-card-body">
            <div class="pd-field-grid">
                <div class="pd-field">
                    <div class="pd-field-label"><i class="fas fa-user fa-sm"></i> Dibuat Oleh</div>
                    <div class="pd-field-value">{{ $proposal->user->name ?? '-' }}</div>
                </div>
                <div class="pd-field">
                    <div class="pd-field-label"><i class="fas fa-building fa-sm"></i> Departemen</div>
                    <div class="pd-field-value">
                        <span class="pd-dept-chip" style="background:#ede9fe;color:#5b21b6">
                            {{ $proposal->departemen->nama_departemen ?? '-' }}
                        </span>
                    </div>
                </div>
                <div class="pd-field" style="margin-top:.875rem">
                    <div class="pd-field-label"><i class="fas fa-calendar-alt fa-sm"></i> Tanggal Pengajuan</div>
                    <div class="pd-field-value">
                        {{ \Carbon\Carbon::parse($proposal->tanggal_pengajuan)->translatedFormat('d F Y') }}
                    </div>
                </div>
                <div class="pd-field" style="margin-top:.875rem">
                    <div class="pd-field-label"><i class="fas fa-tag fa-sm"></i> Jenis Proposal</div>
                    <div class="pd-field-value">
                        @php
                            $jenisChip = ['acara'=>['#dbeafe','#1e40af'],'maintenance'=>['#fef9c3','#854d0e'],'barang'=>['#dcfce7','#14532d']];
                            [$jBg,$jTxt] = $jenisChip[$proposal->jenis] ?? ['#f3f4f6','#374151'];
                        @endphp
                        <span class="pd-dept-chip" style="background:{{ $jBg }};color:{{ $jTxt }}">
                            {{ strtoupper($proposal->jenis) }}
                        </span>
                    </div>
                </div>
                <div class="pd-field" style="margin-top:.875rem">
                    <div class="pd-field-label"><i class="fas fa-money-bill-wave fa-sm"></i> Total Anggaran</div>
                    <div class="pd-field-value money">Rp {{ number_format($proposal->total_anggaran,0,',','.') }}</div>
                </div>
                @if($proposal->lampiran)
                <div class="pd-field" style="margin-top:.875rem">
                    <div class="pd-field-label"><i class="fas fa-paperclip fa-sm"></i> Lampiran</div>
                    <div class="pd-field-value">
                        <a href="{{ route('admin.documents.show', ['path' => $proposal->lampiran]) }}" target="_blank" class="pd-btn-view">
                            <i class="fas fa-eye"></i> Lihat Lampiran
                        </a>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══════════ DETAIL ACARA ═══════════ --}}
    @if($proposal->jenis === 'acara')
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Detail Acara</h2>
        </div>
        <div class="pd-card-body">
            <div class="pd-field-grid">
                <div class="pd-field">
                    <div class="pd-field-label"><i class="fas fa-bullhorn fa-sm"></i> Nama Acara</div>
                    <div class="pd-field-value">{{ $proposal->judul ?? '-' }}</div>
                </div>
                <div class="pd-field">
                    <div class="pd-field-label"><i class="fas fa-calendar-day fa-sm"></i> Tanggal Acara</div>
                    <div class="pd-field-value">
                        {{ $proposal->tanggal_acara ? \Carbon\Carbon::parse($proposal->tanggal_acara)->translatedFormat('d F Y') : '-' }}
                    </div>
                </div>
                <div class="pd-field" style="margin-top:.875rem">
                    <div class="pd-field-label"><i class="fas fa-map-marker-alt fa-sm"></i> Lokasi</div>
                    <div class="pd-field-value">{{ $proposal->lokasi_acara ?? '-' }}</div>
                </div>
            </div>
            @if($proposal->keterangan)
            <div style="margin-top:1rem;background:#f9fafb;border:0.5px solid #e5e7eb;border-radius:10px;padding:.875rem 1rem">
                <div class="pd-field-label" style="margin-bottom:.5rem"><i class="fas fa-sticky-note fa-sm"></i> Keterangan / Rincian</div>
                <p style="margin:0;font-size:13.5px;color:#374151;line-height:1.6">{{ $proposal->keterangan }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif

    {{-- ═══════════ DAFTAR BARANG / MAINTENANCE ═══════════ --}}
    @if(in_array($proposal->jenis, ['barang','maintenance']))
    <div class="pd-card">
        <div class="pd-card-header">
            <h2>
                <span class="pd-card-dot"></span>
                {{ $proposal->jenis === 'maintenance' ? 'Daftar Kebutuhan Maintenance' : 'Daftar Barang Diajukan' }}
            </h2>
            <span class="pd-badge pd-badge-purple">{{ $proposal->items->count() }} item</span>
        </div>
        <div class="pd-card-body" style="padding:0">
            <div class="table-responsive">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            @if($proposal->jenis === 'maintenance')
                                <th>Nama Alat</th>
                                <th>Jenis Perawatan</th>
                                <th>Vendor / Teknisi</th>
                            @else
                                <th>Nama Barang</th>
                                <th>Merk / Tipe</th>
                                <th style="text-align:center;width:60px">Qty</th>
                                <th>Tujuan Pembelian</th>
                            @endif
                            <th class="th-right" style="width:140px">Anggaran</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proposal->items as $i => $item)
                        <tr>
                            <td><span class="pd-num">{{ $i+1 }}</span></td>
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
                            <td class="td-money">Rp {{ number_format($item->anggaran,0,',','.') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ $proposal->jenis === 'barang' ? 6 : 5 }}" style="text-align:center;padding:2.5rem;color:#9ca3af">
                                <i class="fas fa-inbox" style="font-size:2rem;display:block;margin-bottom:.5rem"></i>
                                Tidak ada data tercantum
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="{{ $proposal->jenis === 'barang' ? 5 : 4 }}" style="text-align:right;color:#6b7280;font-weight:500;font-size:12px">Total Anggaran</td>
                            <td class="td-money">Rp {{ number_format($proposal->total_anggaran,0,',','.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════ RIWAYAT PENCAIRAN ═══════════ --}}
    @if($proposal->pencairan && $proposal->pencairan->count() > 0)
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Riwayat Pencairan Dana</h2>
            <span class="pd-badge {{ $persen >= 100 ? 'pd-badge-success' : 'pd-badge-warning' }}">{{ $persen }}%</span>
        </div>
        <div class="pd-card-body">
            {{-- Metric Summary --}}
            <div class="pd-metrics">
                <div class="pd-metric">
                    <div class="m-label">Total Anggaran</div>
                    <div class="m-value">Rp {{ number_format($proposal->total_anggaran,0,',','.') }}</div>
                </div>
                <div class="pd-metric">
                    <div class="m-label">Total Dicairkan</div>
                    <div class="m-value green">Rp {{ number_format($totalCair,0,',','.') }}</div>
                </div>
                <div class="pd-metric">
                    <div class="m-label">Sisa Pencairan</div>
                    <div class="m-value amber">Rp {{ number_format($proposal->total_anggaran - $totalCair,0,',','.') }}</div>
                </div>
            </div>
            {{-- Progress --}}
            <div class="pd-progress-wrap" style="margin-bottom:1.25rem">
                <div class="pd-progress-label">
                    <span>Progress pencairan</span>
                    <span>{{ $persen }}%</span>
                </div>
                <div class="pd-progress-track">
                    <div class="pd-progress-fill" style="width:{{ min($persen,100) }}%;background:{{ $persenColor }}"></div>
                </div>
            </div>
            {{-- Table --}}
            <div class="table-responsive">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Tanggal</th>
                            <th class="th-right">Jumlah Dana</th>
                            <th>Rekening</th>
                            <th>Catatan</th>
                            <th style="text-align:center;width:80px">Bukti</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proposal->pencairan as $i => $p)
                        <tr>
                            <td><span class="pd-num">{{ $i+1 }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($p->created_at)->translatedFormat('d F Y') }}</td>
                            <td class="td-money">Rp {{ number_format($p->jumlah_dana,0,',','.') }}</td>
                            <td style="color:#6b7280">{{ $p->rekening->nama_rekening ?? '-' }}</td>
                            <td style="color:#6b7280">{{ $p->catatan ?? '-' }}</td>
                            <td style="text-align:center">
                                @if($p->bukti_transfer)
                                    <a href="{{ route('admin.documents.show', ['path' => $p->bukti_transfer]) }}" target="_blank" class="pd-btn-view">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                @else
                                    <span style="font-size:11px;color:#9ca3af;font-style:italic">Cash</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align:right;color:#6b7280;font-weight:500;font-size:12px">Total Dicairkan</td>
                            <td class="td-money">Rp {{ number_format($totalCair,0,',','.') }}</td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    @endif

    {{-- ═══════════ BUKTI PENGGUNAAN DANA (NOTA) ═══════════ --}}
    @if(in_array($proposal->status, ['dicairkan','dicairkan_sebagian']))
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Bukti Penggunaan Dana (Nota)</h2>
            <span class="pd-badge pd-badge-blue">Realisasi: Rp {{ number_format($totalNota,0,',','.') }}</span>
        </div>
        <div class="pd-card-body">
            {{-- Metric --}}
            <div class="pd-metrics" style="margin-bottom:.875rem">
                <div class="pd-metric">
                    <div class="m-label">Dana Dicairkan</div>
                    <div class="m-value">Rp {{ number_format($totalCair,0,',','.') }}</div>
                </div>
                <div class="pd-metric">
                    <div class="m-label">Total Realisasi</div>
                    <div class="m-value green">Rp {{ number_format($totalNota,0,',','.') }}</div>
                </div>
                <div class="pd-metric">
                    <div class="m-label">Sisa Dana</div>
                    <div class="m-value {{ $sisaDana < 0 ? 'red' : 'amber' }}">
                        Rp {{ number_format($sisaDana,0,',','.') }}
                    </div>
                </div>
            </div>

            @if($proposal->nota_penggunaan->count() > 0)
            <div class="table-responsive" style="margin-bottom:1.25rem">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th style="width:40px">#</th>
                            <th>Keterangan</th>
                            <th class="th-right" style="width:160px">Jumlah</th>
                            <th style="text-align:center;width:80px">File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($proposal->nota_penggunaan as $i => $nota)
                        <tr>
                            <td><span class="pd-num">{{ $i+1 }}</span></td>
                            <td>{{ $nota->keterangan ?? '-' }}</td>
                            <td class="td-money">Rp {{ number_format($nota->jumlah,0,',','.') }}</td>
                            <td style="text-align:center">
                                <a href="{{ route('admin.documents.show', ['path' => $nota->file_nota]) }}" target="_blank" class="pd-btn-view">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2" style="text-align:right;color:#6b7280;font-weight:500;font-size:12px">Total Realisasi</td>
                            <td class="td-money">Rp {{ number_format($totalNota,0,',','.') }}</td>
                            <td></td>
                        </tr>
                        <tr>
                            <td colspan="2" style="text-align:right;color:#6b7280;font-weight:500;font-size:12px">Sisa Dana Dicairkan</td>
                            <td class="{{ $sisaDana < 0 ? 'td-money' : 'td-money-neg' }}" style="{{ $sisaDana < 0 ? 'color:#dc2626!important' : '' }}">
                                Rp {{ number_format($sisaDana,0,',','.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            @else
            <div class="pd-alert info" style="margin-bottom:1.25rem">
                <i class="fas fa-info-circle"></i>
                <div>Belum ada nota pembelian yang diunggah.</div>
            </div>
            @endif

            {{-- Form Upload Nota --}}
            @if(Auth::id() === $proposal->user_id)
            <hr class="pd-divider">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:.75rem;color:#374151;display:flex;align-items:center;gap:6px">
                <i class="fas fa-upload" style="color:#7c3aed"></i> Upload Nota Baru
            </div>
            <form action="{{ route('admin.proposal.uploadNota', $proposal->id) }}" method="POST" enctype="multipart/form-data" id="form-nota">
                @csrf<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <div id="nota-wrapper"></div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="button" class="pd-btn-add" onclick="tambahNota()">
                        <i class="fas fa-plus"></i> Tambah nota
                    </button>
                    <button type="submit" class="pd-btn-primary">
                        <i class="fas fa-upload"></i> Upload Semua
                    </button>
                </div>
            </form>
            @endif
        </div>
    </div>
    @endif

    {{-- ═══════════ PENGEMBALIAN DANA ═══════════ --}}
    @php
        $pengembalian           = $proposal->pengembalianDana ?? collect();
        $pengembalianDiterima   = $pengembalian->where('status','diterima')->first();
        $pengembalianPending    = $pengembalian->where('status','pending')->first();
        $totalDiterima          = $pengembalian->whereIn('status',['diterima'])->sum('jumlah');
        $sisaYangHarusDikembalikan = $sisaDana - $totalDiterima;
    @endphp
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Pengembalian Dana</h2>
        </div>
        <div class="pd-card-body">

            {{-- Dana sudah diterima --}}
            @if($pengembalianDiterima)
            <div class="pd-alert success">
                <i class="fas fa-check-circle"></i>
                <div>
                    <div style="font-weight:700;margin-bottom:3px">Dana Telah Dikembalikan</div>
                    <div style="font-size:12px">
                        Diterima pada {{ \Carbon\Carbon::parse($pengembalianDiterima->updated_at)->translatedFormat('d F Y') }}
                        &nbsp;·&nbsp; Jumlah: <strong>Rp {{ number_format($pengembalianDiterima->jumlah,0,',','.') }}</strong>
                        &nbsp;·&nbsp; Metode: <strong>{{ ucfirst($pengembalianDiterima->metode) }}</strong>
                    </div>
                    @if($pengembalianDiterima->catatan)
                    <div style="font-size:12px;font-style:italic;margin-top:3px;color:#047857">"{{ $pengembalianDiterima->catatan }}"</div>
                    @endif
                </div>
            </div>
            @endif

            {{-- Menunggu verifikasi --}}
            @if($pengembalianPending && !$pengembalianDiterima)
            <div class="pd-alert warning">
                <i class="fas fa-clock"></i>
                <div>
                    <div style="font-weight:700;margin-bottom:2px">Menunggu Verifikasi</div>
                    <div style="font-size:12px">
                        Pengajuan sebesar <strong>Rp {{ number_format($pengembalianPending->jumlah,0,',','.') }}</strong> sedang diproses tim keuangan.
                    </div>
                </div>
            </div>
            @endif

            {{-- Form pengajuan --}}
            @if(Auth::id() === $proposal->user_id && in_array($proposal->status,['dicairkan','dicairkan_sebagian']) && $sisaYangHarusDikembalikan > 0 && !$pengembalianPending && !$pengembalianDiterima)
            <div class="pd-alert info" style="margin-bottom:1.25rem">
                <i class="fas fa-info-circle"></i>
                <div>
                    <div style="font-weight:700;margin-bottom:2px">Sisa Dana yang Harus Dikembalikan</div>
                    <div style="font-size:18px;font-weight:800;color:#1d4ed8">Rp {{ number_format($sisaYangHarusDikembalikan,0,',','.') }}</div>
                </div>
            </div>

            <form action="{{ route('admin.pengembalian_dana.store') }}" method="POST" enctype="multipart/form-data" id="form-kembalikan">
                @csrf<input type="hidden" name="operation_id" value="{{ (string) \Illuminate\Support\Str::uuid() }}">
                <input type="hidden" name="proposal_id" value="{{ $proposal->id }}">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;margin-bottom:.75rem">
                    <div>
                        <label class="pd-label"><i class="fas fa-coins fa-sm"></i> Jumlah Dikembalikan</label>
                        <div class="pd-input-group">
                            <span>Rp</span>
                            <input type="text" name="jumlah" id="jumlahInput" placeholder="{{ number_format($sisaYangHarusDikembalikan,0,',','.') }}">
                        </div>
                        @error('jumlah')<div style="font-size:11px;color:#dc2626;margin-top:3px">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label class="pd-label"><i class="fas fa-exchange-alt fa-sm"></i> Metode Pengembalian</label>
                        <select name="metode" id="metodeSelect" class="pd-select">
                            <option value="" disabled selected>-- Pilih metode --</option>
                            <option value="transfer">💳 Transfer Bank</option>
                            <option value="cash">💰 Tunai / Cash</option>
                        </select>
                        @error('metode')<div style="font-size:11px;color:#dc2626;margin-top:3px">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div id="buktiWrapper" style="display:none;margin-bottom:.75rem">
                    <label class="pd-label"><i class="fas fa-paperclip fa-sm"></i> Bukti Transfer</label>
                    <div class="pd-upload-zone" onclick="document.getElementById('buktiInput').click()">
                        <i class="fas fa-cloud-upload-alt" style="font-size:1.5rem;color:#c4b5fd;display:block;margin-bottom:.5rem"></i>
                        <p><strong>Klik untuk upload</strong> &nbsp;·&nbsp; JPG, PNG, PDF (maks 2MB)</p>
                        <div class="pd-filename" id="fileName"></div>
                        <input type="file" name="bukti" id="buktiInput" class="d-none" accept=".jpg,.jpeg,.png,.pdf">
                    </div>
                    @error('bukti')<div style="font-size:11px;color:#dc2626;margin-top:3px">{{ $message }}</div>@enderror
                </div>
                <div style="margin-bottom:.75rem">
                    <label class="pd-label"><i class="fas fa-sticky-note fa-sm"></i> Catatan <small style="font-weight:400;text-transform:none">(opsional)</small></label>
                    <textarea name="catatan" class="pd-input pd-textarea" rows="2" placeholder="Tambahkan keterangan bila perlu..."></textarea>
                    @error('catatan')<div style="font-size:11px;color:#dc2626;margin-top:3px">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="pd-btn-primary" style="width:100%;justify-content:center;padding:11px">
                    <i class="fas fa-paper-plane"></i> Ajukan Pengembalian Dana
                </button>
            </form>

            @elseif($sisaYangHarusDikembalikan <= 0 && ($pengembalianDiterima || $pengembalian->count() > 0))
            <div style="text-align:center;padding:1.5rem 0">
                <div style="font-size:2rem;color:#10b981;margin-bottom:.5rem">✅</div>
                <div style="font-weight:700;color:#065f46;font-size:15px">Semua dana telah dikembalikan</div>
                <div style="font-size:12px;color:#6b7280;margin-top:4px">Terima kasih atas tanggung jawab Anda</div>
            </div>
            @endif

            {{-- Riwayat pengembalian --}}
            @if($pengembalian->count() > 0)
            <hr class="pd-divider">
            <div style="font-size:12.5px;font-weight:700;margin-bottom:.75rem;color:#374151;display:flex;justify-content:space-between;align-items:center">
                <span><i class="fas fa-history" style="color:#7c3aed;margin-right:6px"></i>Riwayat Pengajuan</span>
                <span class="pd-badge pd-badge-secondary">{{ $pengembalian->count() }} data</span>
            </div>
            <div class="table-responsive">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th class="th-right">Jumlah</th>
                            <th>Metode</th>
                            <th style="text-align:center">Status</th>
                            <th>Catatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($pengembalian->sortByDesc('created_at') as $item)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($item->created_at)->translatedFormat('d M Y') }}</td>
                            <td class="td-money">Rp {{ number_format($item->jumlah,0,',','.') }}</td>
                            <td>
                                <span class="pd-badge pd-badge-secondary" style="font-size:10px">{{ ucfirst($item->metode) }}</span>
                            </td>
                            <td style="text-align:center">
                                @if($item->status=='diterima')
                                    <span class="pd-badge pd-badge-success" style="font-size:10px"><i class="fas fa-check fa-sm"></i> Diterima</span>
                                @elseif($item->status=='ditolak')
                                    <span class="pd-badge pd-badge-danger" style="font-size:10px"><i class="fas fa-times fa-sm"></i> Ditolak</span>
                                @else
                                    <span class="pd-badge pd-badge-warning" style="font-size:10px"><i class="fas fa-clock fa-sm"></i> Menunggu</span>
                                @endif
                            </td>
                            <td style="color:#6b7280;font-size:12px">{{ Str::limit($item->catatan,40,'...') ?? '-' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════════ ALUR PERSETUJUAN ═══════════ --}}
    <div class="pd-card">
        <div class="pd-card-header">
            <h2><span class="pd-card-dot"></span> Alur Persetujuan</h2>
        </div>
        <div class="pd-card-body" style="padding:.75rem 1.25rem">
            @foreach($proposal->approval_flows->sortBy('urutan') as $flow)
            @php
                $fClass = match($flow->status) { 'disetujui'=>'approved','ditolak'=>'rejected',default=>'pending' };
                $fIcon  = match($flow->status) { 'disetujui'=>'fa-check','ditolak'=>'fa-times',default=>'fa-clock' };
                $fLabel = match($flow->status) { 'disetujui'=>'Disetujui','ditolak'=>'Ditolak',default=>'Menunggu' };
            @endphp
            <div class="pd-flow-row">
                <div class="pd-flow-icon {{ $fClass }}">
                    <i class="fas {{ $fIcon }}"></i>
                </div>
                <div class="pd-flow-info">
                    <div class="pd-flow-stage">{{ ucfirst(str_replace('_',' ',$flow->tahap)) }}</div>
                    <div class="pd-flow-sub">
                        <i class="fas fa-user fa-sm"></i> {{ $flow->approver->name ?? '-' }}
                        @if($flow->tanggal_approve)
                            &nbsp;·&nbsp; <i class="fas fa-calendar fa-sm"></i> {{ \Carbon\Carbon::parse($flow->tanggal_approve)->translatedFormat('d F Y') }}
                        @endif
                    </div>
                    @if($flow->catatan)
                    <div class="pd-flow-note">{{ $flow->catatan }}</div>
                    @endif
                </div>
                <span class="pd-flow-badge {{ $fClass }}">{{ $fLabel }}</span>
            </div>
            @endforeach
        </div>
    </div>

</div>{{-- end .pd-wrap --}}

<script>
/* ── Format Rupiah otomatis ── */
document.addEventListener('input', function(e) {
    if (e.target.matches('input[name^="nota["][name$="[jumlah]"], input[name="jumlah"]')) {
        let raw = e.target.value.replace(/\D/g,'');
        e.target.setAttribute('data-raw', raw);
        e.target.value = raw ? new Intl.NumberFormat('id-ID').format(raw) : '';
    }
});

/* ── Kembalikan ke angka murni sebelum submit ── */
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        this.querySelectorAll('[data-raw]').forEach(el => {
            el.value = el.getAttribute('data-raw') || el.value.replace(/\D/g,'');
        });
    });
});

/* ── Toggle bukti transfer ── */
const metodeSelect = document.getElementById('metodeSelect');
const buktiWrapper = document.getElementById('buktiWrapper');
const buktiInput   = document.getElementById('buktiInput');
if (metodeSelect) {
    metodeSelect.addEventListener('change', function() {
        const show = this.value === 'transfer';
        buktiWrapper.style.display = show ? 'block' : 'none';
        show ? buktiInput.setAttribute('required','required') : buktiInput.removeAttribute('required');
        if (!show) buktiInput.value = '';
    });
}

/* ── Preview nama file upload ── */
if (buktiInput) {
    buktiInput.addEventListener('change', function() {
        const el = document.getElementById('fileName');
        el.textContent = this.files[0] ? '✓ ' + this.files[0].name : '';
    });
}

/* ── Tambah nota dinamis ── */
let notaIdx = 0;
function tambahNota() {
    const wrapper = document.getElementById('nota-wrapper');
    const div = document.createElement('div');
    div.className = 'pd-nota-item';
    div.innerHTML = `
        <div class="row-2">
            <div>
                <label class="pd-label">File Nota</label>
                <input type="file" name="nota[${notaIdx}][file]" class="pd-input" accept=".pdf,.jpg,.jpeg,.png" required>
            </div>
            <div>
                <label class="pd-label">Jumlah</label>
                <input type="text" name="nota[${notaIdx}][jumlah]" class="pd-input" placeholder="Contoh: 2.000.000" required>
            </div>
        </div>
        <div style="display:flex;gap:8px;align-items:flex-end">
            <div style="flex:1">
                <label class="pd-label">Keterangan</label>
                <input type="text" name="nota[${notaIdx}][keterangan]" class="pd-input" placeholder="Contoh: Nota pembelian alat musik">
            </div>
            <button type="button" class="pd-btn-remove" onclick="this.closest('.pd-nota-item').remove()">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    `;
    wrapper.appendChild(div);
    notaIdx++;
}

/* Auto-tambah 1 nota pertama jika ada wrapper */
if (document.getElementById('nota-wrapper')) tambahNota();
</script>

@if(auth()->user()->role==='admin' && !$proposal->pencairan()->exists())
<div class="card p-3 mt-3"><details><summary>Kembalikan untuk revisi (admin)</summary><form method="post" action="{{ route('admin.proposal.request-revision',$proposal->id) }}">@csrf<label for="admin-revision-reason">Alasan permintaan revisi</label><textarea id="admin-revision-reason" name="alasan" required minlength="5" maxlength="1000" class="form-control mb-2"></textarea><button class="btn btn-warning">Minta revisi dan approval ulang</button></form></details></div>
@endif
@endsection