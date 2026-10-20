@extends('backend.layouts.app')
@php use Illuminate\Support\Str; @endphp

@section('content')

<style>
/* ============================================================
   PENGEMBALIAN DANA INDEX — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.pd-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pd-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pd-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pd-export-btn { background:#fff; color:#4c1d95; border:none; border-radius:9px; padding:8px 18px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:opacity .15s }
.pd-export-btn:hover { opacity:.9; text-decoration:none; color:#4c1d95 }

/* ── Alerts ── */
.pd-alert { border-radius:10px; padding:.75rem 1rem; font-size:13px; margin-bottom:1rem; display:flex; align-items:flex-start; gap:9px; border:0.5px solid }
.pd-alert.success { background:#ecfdf5; color:#065f46; border-color:#6ee7b7 }

/* ── Metric Cards ── */
.pd-metrics { display:grid; grid-template-columns:repeat(4,1fr); gap:.875rem; margin-bottom:1.25rem }
@media(max-width:768px){ .pd-metrics { grid-template-columns:repeat(2,1fr) } }
@media(max-width:480px){ .pd-metrics { grid-template-columns:1fr 1fr } }
.pd-metric { background:#fff; border:0.5px solid #e5e7eb; border-radius:13px; padding:.875rem 1rem; border-left:3px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pd-metric.purple { border-left-color:#7c3aed }
.pd-metric.green  { border-left-color:#10b981 }
.pd-metric.amber  { border-left-color:#f59e0b }
.pd-metric.red    { border-left-color:#ef4444 }
.pd-metric .m-icon { font-size:1.25rem; margin-bottom:.5rem }
.pd-metric .m-icon.purple { color:#7c3aed }
.pd-metric .m-icon.green  { color:#10b981 }
.pd-metric .m-icon.amber  { color:#f59e0b }
.pd-metric .m-icon.red    { color:#ef4444 }
.pd-metric .m-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.pd-metric .m-value { font-size:22px; font-weight:700; color:#111; line-height:1.2 }
.pd-metric .m-value.sm { font-size:14px; padding-top:3px }
.pd-metric .m-sub { font-size:10.5px; color:#9ca3af; margin-top:3px }

/* ── Filter Card ── */
.pd-filter-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; padding:1rem 1.25rem; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pd-filter-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.75rem; display:flex; align-items:center; gap:6px }
.pd-filter-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:.75rem; margin-bottom:.875rem }
@media(max-width:768px){ .pd-filter-grid { grid-template-columns:1fr 1fr } }
@media(max-width:480px){ .pd-filter-grid { grid-template-columns:1fr } }
.pd-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.pd-field select,
.pd-field input { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:8px; padding:7px 10px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; outline:none; transition:border-color .15s,box-shadow .15s; appearance:none; -webkit-appearance:none }
.pd-field select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.pd-field select:focus,
.pd-field input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.pd-field input[type="date"]::-webkit-calendar-picker-indicator { opacity:.5 }

.pd-filter-actions { display:flex; gap:.5rem; flex-wrap:wrap }
.pd-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; border:none; text-decoration:none; transition:opacity .15s,transform .1s; white-space:nowrap }
.pd-btn:hover { opacity:.88; transform:translateY(-1px); text-decoration:none }
.pd-btn-primary { background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff }
.pd-btn-reset { background:#f3f4f6; color:#374151 }

/* ── Main Card ── */
.pd-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.pd-card-header { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; flex-wrap:wrap; gap:.5rem }
.pd-card-title { font-size:13px; font-weight:700; color:#111; display:flex; align-items:center; gap:6px }
.pd-card-title i { color:#7c3aed }
.pd-count-pill { font-size:11px; font-weight:700; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 12px }

/* ── Table ── */
.pd-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pd-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.pd-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pd-table tbody tr:last-child { border-bottom:none }
.pd-table tbody tr:hover td { background:#faf8ff }
.pd-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }

/* ── Badges ── */
.pd-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-amber  { background:#fef3c7; color:#92400e }
.b-green  { background:#d1fae5; color:#065f46 }
.b-red    { background:#fee2e2; color:#991b1b }

/* ── Metode chip ── */
.pd-metode { font-size:10px; font-weight:700; border-radius:5px; padding:2px 8px; display:inline-block; background:#ede9fe; color:#5b21b6; text-transform:uppercase }

/* ── Action buttons ── */
.pd-acts { display:flex; gap:4px; align-items:center; justify-content:center }
.pd-act { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:opacity .15s; text-decoration:none }
.pd-act:hover { opacity:.8 }
.pd-act.view { background:#ede9fe; color:#5b21b6 }

/* ── Pagination ── */
.pd-pagination { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-top:0.5px solid #f3f4f6; font-size:12px; color:#6b7280; flex-wrap:wrap; gap:.5rem }

/* ── Empty ── */
.pd-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.pd-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Mobile Cards ── */
.pd-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pd-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.pd-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.pd-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .pd-card > .table-responsive { display:none }
    .pd-pagination-desktop { display:none }
}
@media(min-width:768px){ .pd-mob-list { display:none } }
</style>

<div class="pd-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pd-topbar">
        <h1>
            <i class="fas fa-hand-holding-usd"></i>
            Daftar Pengembalian Dana
        </h1>
        <a href="{{ route('admin.pengembalian_dana.export', request()->all()) }}" class="pd-export-btn">
            <i class="fas fa-file-pdf"></i> Ekspor PDF
        </a>
    </div>

    {{-- ═══ SESSION ALERT ═══ --}}
    @if(session('success'))
        <div class="pd-alert success">
            <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('success') }}</div>
        </div>
    @endif

    {{-- ═══ METRIC CARDS ═══ --}}
    <div class="pd-metrics">
        <div class="pd-metric purple">
            <div class="m-icon purple"><i class="fas fa-list-alt"></i></div>
            <div class="m-label">Total Pengajuan</div>
            <div class="m-value">{{ $data->total() }}</div>
            <div class="m-sub">Semua status</div>
        </div>
        <div class="pd-metric green">
            <div class="m-icon green"><i class="fas fa-coins"></i></div>
            <div class="m-label">Total Disetujui</div>
            <div class="m-value sm">Rp {{ number_format($totalDiterima, 0, ',', '.') }}</div>
            <div class="m-sub">Dana dikembalikan</div>
        </div>
        <div class="pd-metric amber">
            <div class="m-icon amber"><i class="fas fa-clock"></i></div>
            <div class="m-label">Pending</div>
            <div class="m-value">{{ $data->getCollection()->where('status','pending')->count() }}</div>
            <div class="m-sub">Menunggu review</div>
        </div>
        <div class="pd-metric red">
            <div class="m-icon red"><i class="fas fa-times-circle"></i></div>
            <div class="m-label">Ditolak</div>
            <div class="m-value">{{ $data->getCollection()->where('status','ditolak')->count() }}</div>
            <div class="m-sub">Tidak disetujui</div>
        </div>
    </div>

    {{-- ═══ FILTER ═══ --}}
    <div class="pd-filter-card">
        <div class="pd-filter-title">
            <i class="fas fa-sliders-h" style="color:#7c3aed"></i> Filter Data
        </div>
        <form method="GET" action="{{ route('admin.pengembalian_dana.index') }}">
            <div class="pd-filter-grid">
                <div class="pd-field">
                    <label>Grup</label>
                    <select name="grup_id">
                        <option value="">Semua Grup</option>
                        @foreach($grups as $grup)
                            <option value="{{ $grup->id }}" {{ request('grup_id') == $grup->id ? 'selected' : '' }}>
                                {{ $grup->nama_grup }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="pd-field">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="pending"  {{ request('status')=='pending'  ? 'selected':'' }}>Pending</option>
                        <option value="diterima" {{ request('status')=='diterima' ? 'selected':'' }}>Diterima</option>
                        <option value="ditolak"  {{ request('status')=='ditolak'  ? 'selected':'' }}>Ditolak</option>
                    </select>
                </div>
                <div class="pd-field">
                    <label>Tanggal Awal</label>
                    <input type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}">
                </div>
                <div class="pd-field">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}">
                </div>
            </div>
            <div class="pd-filter-actions">
                <button type="submit" class="pd-btn pd-btn-primary">
                    <i class="fas fa-search"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.pengembalian_dana.index') }}" class="pd-btn pd-btn-reset">
                    <i class="fas fa-undo"></i> Reset
                </a>
            </div>
        </form>
    </div>

    {{-- ═══ TABLE (DESKTOP) ═══ --}}
    <div class="pd-card">
        <div class="pd-card-header">
            <div class="pd-card-title">
                <i class="fas fa-list-ul"></i> Daftar Pengembalian Dana
            </div>
            <span class="pd-count-pill">{{ $data->total() }} Data</span>
        </div>

        <div class="table-responsive">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Proposal</th>
                        <th style="min-width:120px">Grup</th>
                        <th style="width:130px;text-align:right">Jumlah</th>
                        <th style="width:100px;text-align:center">Metode</th>
                        <th style="width:110px;text-align:center">Status</th>
                        <th style="width:90px;text-align:center">Tanggal</th>
                        <th style="width:70px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $item)
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">
                            {{ $data->firstItem() + $loop->index }}
                        </td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ Str::limit($item->proposal->judul ?? '-', 40) }}
                            </div>
                            <div style="font-size:10.5px;color:#9ca3af;margin-top:2px">
                                <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                                {{ $item->proposal->user->name ?? '-' }}
                            </div>
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $item->proposal->user->grup->nama_grup ?? '-' }}
                        </td>
                        <td style="text-align:right;font-weight:700;color:#059669;font-size:12px">
                            Rp {{ number_format($item->jumlah, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center">
                            <span class="pd-metode">{{ $item->metode ?? '-' }}</span>
                        </td>
                        <td style="text-align:center">
                            @if($item->status === 'pending')
                                <span class="pd-badge b-amber">⏳ Pending</span>
                            @elseif($item->status === 'diterima')
                                <span class="pd-badge b-green">✓ Diterima</span>
                            @else
                                <span class="pd-badge b-red">✕ Ditolak</span>
                            @endif
                        </td>
                        <td style="text-align:center;font-size:11px;color:#6b7280">
                            {{ $item->created_at->format('d/m/Y') }}
                        </td>
                        <td>
                            <div class="pd-acts">
                                <a href="{{ route('admin.pengembalian_dana.show', $item->id) }}"
                                   class="pd-act view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="pd-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Tidak ada data pengembalian dana</div>
                                <div style="font-size:13px">Silakan sesuaikan filter untuk melihat hasil</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="pd-pagination pd-pagination-desktop">
            <span>
                @if($data->total())
                    Menampilkan {{ $data->firstItem() }}–{{ $data->lastItem() }} dari {{ $data->total() }} data
                @else
                    Tidak ada data
                @endif
            </span>
            <div>{{ $data->withQueryString()->links() }}</div>
        </div>
    </div>

    {{-- ═══ MOBILE CARDS ═══ --}}
    <div class="pd-mob-list">
        @forelse($data as $item)
        <div class="pd-mob-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                <div style="font-weight:700;font-size:13.5px;color:#111;line-height:1.3;flex:1">
                    {{ Str::limit($item->proposal->judul ?? '-', 40) }}
                </div>
                @if($item->status === 'pending')
                    <span class="pd-badge b-amber">Pending</span>
                @elseif($item->status === 'diterima')
                    <span class="pd-badge b-green">Diterima</span>
                @else
                    <span class="pd-badge b-red">Ditolak</span>
                @endif
            </div>

            <div class="pd-mob-meta">
                <span class="pd-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $item->proposal->user->grup->nama_grup ?? '-' }}</span>
                <span class="pd-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $item->proposal->user->name ?? '-' }}</span>
                <span class="pd-mob-chip"><i class="fas fa-calendar fa-sm"></i> {{ $item->created_at->format('d/m/Y') }}</span>
                <span class="pd-metode">{{ $item->metode ?? '-' }}</span>
            </div>

            <div style="background:#f9fafb;border-radius:9px;padding:.625rem .875rem;margin:.5rem 0;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:11px;color:#6b7280">Jumlah Pengembalian</span>
                <span style="font-weight:700;font-size:13px;color:#059669">Rp {{ number_format($item->jumlah, 0, ',', '.') }}</span>
            </div>

            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <a href="{{ route('admin.pengembalian_dana.show', $item->id) }}"
                   style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;text-decoration:none">
                    <i class="fas fa-eye me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        @empty
        <div class="pd-empty">
            <span class="ei"><i class="fas fa-inbox"></i></span>
            <div style="font-weight:600;color:#374151">Tidak ada data pengembalian dana</div>
        </div>
        @endforelse

        <div style="margin-top:1rem">{{ $data->withQueryString()->links() }}</div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection