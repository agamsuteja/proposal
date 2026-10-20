@extends('backend.layouts.app')

@section('content')

@php
    $badgeMap = [
        'diajukan'                => ['label'=>'Diajukan',          'class'=>'b-gray'],
        'disetujui_mh'            => ['label'=>'Disetujui MH',      'class'=>'b-blue'],
        'disetujui_gh'            => ['label'=>'Disetujui GH',      'class'=>'b-blue'],
        'disetujui_senior_pastor' => ['label'=>'Disetujui SP',      'class'=>'b-teal'],
        'dicairkan_sebagian'      => ['label'=>'Sebagian Cair',     'class'=>'b-amber'],
        'dicairkan'               => ['label'=>'Dicairkan',         'class'=>'b-green'],
        'revisi'                  => ['label'=>'Perlu Revisi',      'class'=>'b-amber'],
        'ditolak'                 => ['label'=>'Ditolak',           'class'=>'b-red'],
        'dana_dikembalikan'       => ['label'=>'Dana Dikembalikan', 'class'=>'b-teal'],
    ];
@endphp

<style>
/* ============================================================
   PROPOSAL INDEX — DESIGN SYSTEM
   ============================================================ */
.pi-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pi-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pi-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pi-new-btn { background:#fff; color:#4c1d95; border:none; border-radius:9px; padding:8px 18px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:opacity .15s }
.pi-new-btn:hover { opacity:.9; text-decoration:none; color:#4c1d95 }

/* ── Alerts ── */
.pi-alert { border-radius:10px; padding:.75rem 1rem; font-size:13px; margin-bottom:1rem; display:flex; align-items:flex-start; gap:9px; border:0.5px solid; }
.pi-alert.info  { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe }
.pi-alert.success { background:#ecfdf5; color:#065f46; border-color:#6ee7b7 }
.pi-alert.danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca }

/* ── Metric Cards ── */
.pi-metrics { display:grid; grid-template-columns:repeat(4,1fr); gap:.875rem; margin-bottom:1.25rem }
@media(max-width:768px){ .pi-metrics { grid-template-columns:repeat(2,1fr) } }
@media(max-width:480px){ .pi-metrics { grid-template-columns:1fr 1fr } }
.pi-metric { background:#fff; border:0.5px solid #e5e7eb; border-radius:13px; padding:.875rem 1rem; border-left:3px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pi-metric.purple { border-left-color:#7c3aed }
.pi-metric.green  { border-left-color:#10b981 }
.pi-metric.amber  { border-left-color:#f59e0b }
.pi-metric.blue   { border-left-color:#3b82f6 }
.pi-metric .m-icon { font-size:1.25rem; margin-bottom:.5rem }
.pi-metric .m-icon.purple { color:#7c3aed }
.pi-metric .m-icon.green  { color:#10b981 }
.pi-metric .m-icon.amber  { color:#f59e0b }
.pi-metric .m-icon.blue   { color:#3b82f6 }
.pi-metric .m-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.pi-metric .m-value { font-size:22px; font-weight:700; color:#111; line-height:1.2 }
.pi-metric .m-value.sm { font-size:14px; padding-top:3px }
.pi-metric .m-sub { font-size:10.5px; color:#9ca3af; margin-top:3px }

/* ── Main Card ── */
.pi-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }

/* ── Table ── */
.pi-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pi-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.pi-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pi-table tbody tr:last-child { border-bottom:none }
.pi-table tbody tr:hover td { background:#faf8ff }
.pi-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }
.pi-table tfoot td { padding:10px 14px; background:#f9fafb; font-size:12px; color:#6b7280; border-top:0.5px solid #e5e7eb }

/* ── Badges ── */
.pi-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-gray   { background:#e5e7eb; color:#374151 }
.b-blue   { background:#dbeafe; color:#1e40af }
.b-teal   { background:#ccfbf1; color:#0f766e }
.b-green  { background:#d1fae5; color:#065f46 }
.b-amber  { background:#fef3c7; color:#92400e }
.b-red    { background:#fee2e2; color:#991b1b }
.b-purple { background:#ede9fe; color:#5b21b6 }

/* ── Jenis chip ── */
.pi-jenis { font-size:10px; font-weight:700; border-radius:5px; padding:2px 8px; display:inline-block }
.jenis-barang  { background:#dbeafe; color:#1e40af }
.jenis-acara   { background:#fef3c7; color:#92400e }
.jenis-maint   { background:#ccfbf1; color:#0f766e }

/* ── Progress ── */
.pi-prog-track { height:5px; background:#f3f4f6; border-radius:10px; overflow:hidden; border:0.5px solid #e5e7eb; margin-top:3px }
.pi-prog-fill { height:100%; border-radius:10px }
.pi-prog-label { font-size:10px; color:#6b7280; display:flex; justify-content:space-between; margin-bottom:2px }

/* ── Action buttons ── */
.pi-acts { display:flex; gap:4px; align-items:center; justify-content:center }
.pi-act { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:opacity .15s; text-decoration:none }
.pi-act:hover { opacity:.8 }
.pi-act.view { background:#ede9fe; color:#5b21b6 }
.pi-act.edit { background:#fef3c7; color:#92400e }
.pi-act.del  { background:#fee2e2; color:#991b1b }

/* ── Pagination container ── */
.pi-pagination { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-top:0.5px solid #f3f4f6; font-size:12px; color:#6b7280; flex-wrap:wrap; gap:.5rem }

/* ── Pagination Style Override (Bootstrap 5) ── */
.pi-pagination .pagination,
.pi-mob-list .pagination {
    margin-bottom: 0;
    gap: 4px;
    flex-wrap: wrap;
}

.pi-pagination .pagination .page-link,
.pi-mob-list .pagination .page-link {
    border: 0.5px solid #e5e7eb;
    border-radius: 7px !important;
    color: #4c1d95;
    font-size: 11.5px;
    font-weight: 600;
    padding: 5px 10px;
    min-width: 30px;
    text-align: center;
    background: #fff;
    transition: all .15s;
    line-height: 1.4;
    box-shadow: none;
}

.pi-pagination .pagination .page-link:hover,
.pi-mob-list .pagination .page-link:hover {
    background: #f5f3ff;
    border-color: #c4b5fd;
    color: #4c1d95;
}

.pi-pagination .pagination .page-item.active .page-link,
.pi-mob-list .pagination .page-item.active .page-link {
    background: linear-gradient(135deg, #7c3aed 0%, #4c1d95 100%);
    border-color: #7c3aed;
    color: #fff;
    box-shadow: 0 2px 6px rgba(124, 58, 237, .25);
}

.pi-pagination .pagination .page-item.disabled .page-link,
.pi-mob-list .pagination .page-item.disabled .page-link {
    background: #f9fafb;
    color: #d1d5db;
    border-color: #f3f4f6;
    cursor: not-allowed;
}

.pi-pagination .pagination .page-item:first-child .page-link,
.pi-pagination .pagination .page-item:last-child .page-link,
.pi-mob-list .pagination .page-item:first-child .page-link,
.pi-mob-list .pagination .page-item:last-child .page-link {
    border-radius: 7px !important;
}

/* Sembunyikan teks hasil "Showing ... results" bawaan Bootstrap-5 */
.pi-pagination .pagination + p,
.pi-pagination small.text-muted,
.pi-mob-list .pagination + p,
.pi-mob-list small.text-muted {
    display: none;
}

/* ── No data ── */
.pi-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.pi-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Mobile Cards ── */
.pi-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pi-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.pi-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.pi-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .pi-card > .table-responsive { display:none }
    .pi-pagination-desktop { display:none }
}
@media(min-width:768px){
    .pi-mob-list { display:none }
}
</style>

<div class="pi-wrap container-fluid">

    {{-- ═══════════ TOPBAR ═══════════ --}}
    <div class="pi-topbar">
        <h1>
            <i class="fas fa-file-invoice"></i>
            Daftar Pengajuan Proposal 
        </h1>
        <a href="{{ route('admin.proposal.create') }}" class="pi-new-btn">
            <i class="fas fa-plus"></i> Buat Proposal Baru
        </a>
    </div>

    {{-- ═══════════ ROLE ALERT ═══════════ --}}
    @if(session('success'))
        <div class="pi-alert success">
            <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif
    @if(session('error'))
        <div class="pi-alert danger">
            <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('error') }}</div>
        </div>
    @endif

    @if($user->role === 'karyawan')
        <div class="pi-alert info">
            <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>Anda hanya melihat proposal dari departemen: <strong>{{ $user->departemen->nama_departemen ?? '-' }}</strong></div>
        </div>
    @elseif($user->role === 'group_head')
        <div class="pi-alert info">
            <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>Anda melihat proposal dari semua departemen di grup: <strong>{{ $user->grup->nama_grup ?? '-' }}</strong></div>
        </div>
    @endif

    {{-- ═══════════ METRIC CARDS ═══════════ --}}
    <div class="pi-metrics">
        <div class="pi-metric purple">
            <div class="m-icon purple"><i class="fas fa-list-alt"></i></div>
            <div class="m-label">Total Proposal</div>
            <div class="m-value">{{ $proposals->total() }}</div>
            <div class="m-sub">Semua proposal</div>
        </div>
        <div class="pi-metric green">
            <div class="m-icon green"><i class="fas fa-coins"></i></div>
            <div class="m-label">Total Anggaran</div>
            <div class="m-value sm">Rp {{ number_format($proposals->sum('total_anggaran'), 0, ',', '.') }}</div>
            <div class="m-sub">Akumulasi semua</div>
        </div>
        <div class="pi-metric amber">
            <div class="m-icon amber"><i class="fas fa-clock"></i></div>
            <div class="m-label">Pending Review</div>
            <div class="m-value">
                {{ $proposals->whereIn('status', ['diajukan','revisi','disetujui_mh','disetujui_gh'])->count() }}
            </div>
            <div class="m-sub">Menunggu persetujuan</div>
        </div>
        <div class="pi-metric blue">
            <div class="m-icon blue"><i class="fas fa-check-circle"></i></div>
            <div class="m-label">Disetujui</div>
            <div class="m-value">
                {{ $proposals->whereIn('status', ['disetujui_mh','disetujui_gh','disetujui_senior_pastor'])->count() }}
            </div>
            <div class="m-sub">Sudah disetujui</div>
        </div>
    </div>

    {{-- ═══════════ TABLE (DESKTOP) ═══════════ --}}
    <div class="pi-card">
        <div class="table-responsive">
            <table class="pi-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:180px">Judul Proposal</th>
                        <th style="width:80px;text-align:center">Jenis</th>
                        <th style="min-width:110px">Departemen</th>
                        <th style="width:130px;text-align:right">Anggaran</th>
                        <th style="width:90px;text-align:center">Tanggal</th>
                        <th style="width:110px;text-align:center">Status</th>
                        <th style="min-width:100px">Pembuat</th>
                        <th style="min-width:110px">Progress</th>
                        <th style="width:90px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $p)
                    @php
                        $totalCair    = optional($p->pencairan)->sum('jumlah_dana') ?? 0;
                        $totalAnggaran= (float)($p->total_anggaran ?? 0);
                        $persen       = $totalAnggaran > 0 ? round(($totalCair / $totalAnggaran) * 100, 1) : 0;
                        $progColor    = $persen >= 100 ? '#10b981' : ($persen > 0 ? '#f59e0b' : '#9ca3af');
                        $badge        = $badgeMap[$p->status] ?? ['label'=>ucfirst(str_replace('_',' ',$p->status)),'class'=>'b-gray'];
                    @endphp
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">
                            {{ $proposals->firstItem() + $loop->index }}
                        </td>

                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ $p->judul }}
                            </div>
                            @if($p->jenis === 'acara' && ($p->tanggal_acara || $p->lokasi_acara))
                                <div style="font-size:10.5px;color:#6b7280;margin-top:2px">
                                    @if($p->tanggal_acara)
                                        <i class="fas fa-calendar-day fa-sm"></i>
                                        {{ \Carbon\Carbon::parse($p->tanggal_acara)->format('d M Y') }}
                                    @endif
                                    @if($p->lokasi_acara)
                                        &nbsp;· <i class="fas fa-map-marker-alt fa-sm"></i> {{ $p->lokasi_acara }}
                                    @endif
                                </div>
                            @endif
                        </td>

                        <td style="text-align:center">
                            @if($p->jenis === 'barang')
                                <span class="pi-jenis jenis-barang">BARANG</span>
                            @elseif($p->jenis === 'acara')
                                <span class="pi-jenis jenis-acara">ACARA</span>
                            @else
                                <span class="pi-jenis jenis-maint">MAINT.</span>
                            @endif
                        </td>

                        <td>
                            <span style="font-size:11.5px;color:#6b7280">
                                <i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                                {{ $p->departemen->nama_departemen ?? '-' }}
                            </span>
                        </td>

                        <td style="text-align:right;font-weight:700;color:#059669;font-size:12px">
                            Rp {{ number_format($p->total_anggaran, 0, ',', '.') }}
                        </td>

                        <td style="text-align:center;font-size:11px;color:#6b7280">
                            {{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('d/m/Y') }}
                        </td>

                        <td style="text-align:center">
                            <span class="pi-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                        </td>

                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $p->user->name ?? '-' }}
                        </td>

                        <td>
                            <div class="pi-prog-label">
                                <span>{{ $persen }}%</span>
                                @if($persen >= 100)<span style="color:#10b981">✓</span>@endif
                            </div>
                            <div class="pi-prog-track">
                                <div class="pi-prog-fill" style="width:{{ min($persen,100) }}%;background:{{ $progColor }}"></div>
                            </div>
                        </td>

                        <td>
                            <div class="pi-acts">
                                <a href="{{ route('admin.proposal.show', $p->id) }}"
                                   class="pi-act view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(!in_array($p->status, ['dicairkan','disetujui_senior_pastor']))
                                    <a href="{{ route('admin.proposal.edit', $p->id) }}"
                                       class="pi-act edit" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.proposal.destroy', $p->id) }}"
                                          method="POST" class="d-inline"
                                          onsubmit="return confirm('Hapus proposal ini?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="pi-act del" title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                @else
                                    <span class="pi-badge b-green" style="font-size:9.5px">Sudah Cair</span>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10">
                            <div class="pi-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Belum ada proposal</div>
                                <div style="font-size:13px">Silakan buat proposal baru untuk memulai</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination Desktop --}}
        @if($proposals->count() > 0)
        <div class="pi-pagination pi-pagination-desktop">
            <span>
                Menampilkan {{ $proposals->firstItem() }}–{{ $proposals->lastItem() }}
                dari {{ $proposals->total() }} proposal
            </span>
            <div>{{ $proposals->withQueryString()->links('pagination::bootstrap-5') }}</div>
        </div>
        @endif
    </div>

    {{-- ═══════════ MOBILE CARDS ═══════════ --}}
    <div class="pi-mob-list">
        @forelse($proposals as $p)
        @php
            $totalCair    = optional($p->pencairan)->sum('jumlah_dana') ?? 0;
            $totalAnggaran= (float)($p->total_anggaran ?? 0);
            $persen       = $totalAnggaran > 0 ? round(($totalCair / $totalAnggaran) * 100, 1) : 0;
            $progColor    = $persen >= 100 ? '#10b981' : ($persen > 0 ? '#f59e0b' : '#9ca3af');
            $badge        = $badgeMap[$p->status] ?? ['label'=>ucfirst(str_replace('_',' ',$p->status)),'class'=>'b-gray'];
        @endphp
        <div class="pi-mob-card">
            {{-- Header --}}
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                <div style="font-weight:700;font-size:13.5px;color:#111;line-height:1.3;flex:1">{{ $p->judul }}</div>
                <span class="pi-badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
            </div>

            @if($p->jenis === 'acara' && ($p->tanggal_acara || $p->lokasi_acara))
                <div style="font-size:11px;color:#6b7280;margin-bottom:.375rem">
                    @if($p->tanggal_acara)<i class="fas fa-calendar-day fa-sm"></i> {{ \Carbon\Carbon::parse($p->tanggal_acara)->format('d M Y') }} @endif
                    @if($p->lokasi_acara)· <i class="fas fa-map-marker-alt fa-sm"></i> {{ $p->lokasi_acara }} @endif
                </div>
            @endif

            {{-- Meta chips --}}
            <div class="pi-mob-meta">
                @if($p->jenis === 'barang')
                    <span class="pi-jenis jenis-barang">BARANG</span>
                @elseif($p->jenis === 'acara')
                    <span class="pi-jenis jenis-acara">ACARA</span>
                @else
                    <span class="pi-jenis jenis-maint">MAINT.</span>
                @endif
                <span class="pi-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $p->departemen->nama_departemen ?? '-' }}</span>
                <span class="pi-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $p->user->name ?? '-' }}</span>
                <span class="pi-mob-chip"><i class="fas fa-calendar fa-sm"></i> {{ \Carbon\Carbon::parse($p->tanggal_pengajuan)->format('d/m/Y') }}</span>
            </div>

            {{-- Anggaran + Progress --}}
            <div style="background:#f9fafb;border-radius:9px;padding:.625rem .875rem;margin:.5rem 0">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.375rem">
                    <span style="font-size:11px;color:#6b7280">Total Anggaran</span>
                    <span style="font-weight:700;font-size:13px;color:#059669">Rp {{ number_format($p->total_anggaran,0,',','.') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#6b7280;margin-bottom:3px">
                    <span>Progress Pencairan</span>
                    <span>{{ $persen }}%</span>
                </div>
                <div class="pi-prog-track">
                    <div class="pi-prog-fill" style="width:{{ min($persen,100) }}%;background:{{ $progColor }}"></div>
                </div>
            </div>

            {{-- Actions --}}
            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <a href="{{ route('admin.proposal.show', $p->id) }}"
                   style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;text-decoration:none">
                    <i class="fas fa-eye me-1"></i> Lihat Detail
                </a>
                @if(!in_array($p->status, ['dicairkan','disetujui_senior_pastor']))
                    <a href="{{ route('admin.proposal.edit', $p->id) }}"
                       style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#fef3c7;color:#92400e;font-size:12px;font-weight:700;text-decoration:none">
                        <i class="fas fa-edit me-1"></i> Edit
                    </a>
                @endif
            </div>
        </div>
        @empty
        <div class="pi-empty">
            <span class="ei"><i class="fas fa-inbox"></i></span>
            <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Belum ada proposal</div>
            <a href="{{ route('admin.proposal.create') }}"
               style="display:inline-block;margin-top:.75rem;padding:8px 20px;background:linear-gradient(135deg,#7c3aed,#4c1d95);color:#fff;border-radius:9px;font-size:12.5px;font-weight:700;text-decoration:none">
                <i class="fas fa-plus me-1"></i> Buat Proposal
            </a>
        </div>
        @endforelse

        {{-- Pagination Mobile --}}
        @if($proposals->hasPages())
        <div style="margin-top:1rem">
            {{ $proposals->withQueryString()->links('pagination::bootstrap-5') }}
        </div>
        @endif
    </div>

</div>{{-- end .pi-wrap --}}

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection