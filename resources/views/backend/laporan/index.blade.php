@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   LAPORAN — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.lp-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.lp-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; margin-bottom:1rem }
.lp-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0 0 .2rem; display:flex; align-items:center; gap:7px }
.lp-topbar p  { color:rgba(255,255,255,.7); font-size:12px; margin:0 }

/* ── Metric Cards ── */
.lp-metrics { display:grid; grid-template-columns:repeat(4,1fr); gap:.875rem; margin-bottom:1.25rem }
@media(max-width:768px){ .lp-metrics { grid-template-columns:repeat(2,1fr) } }
@media(max-width:480px){ .lp-metrics { grid-template-columns:1fr 1fr } }
.lp-metric { background:#fff; border:0.5px solid #e5e7eb; border-radius:13px; padding:.875rem 1rem; border-left:3px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.lp-metric.purple { border-left-color:#7c3aed }
.lp-metric.green  { border-left-color:#10b981 }
.lp-metric.amber  { border-left-color:#f59e0b }
.lp-metric.blue   { border-left-color:#3b82f6 }
.lp-metric .m-icon { font-size:1.25rem; margin-bottom:.5rem }
.lp-metric .m-icon.purple { color:#7c3aed }
.lp-metric .m-icon.green  { color:#10b981 }
.lp-metric .m-icon.amber  { color:#f59e0b }
.lp-metric .m-icon.blue   { color:#3b82f6 }
.lp-metric .m-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.lp-metric .m-value { font-size:22px; font-weight:700; color:#111; line-height:1.2 }
.lp-metric .m-value.sm { font-size:13px; padding-top:4px; line-height:1.4 }
.lp-metric .m-sub { font-size:10.5px; color:#9ca3af; margin-top:3px }

/* ── Filter Card ── */
.lp-filter-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; padding:1rem 1.25rem; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.lp-filter-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.75rem; display:flex; align-items:center; gap:6px }
.lp-filter-grid { display:grid; grid-template-columns:repeat(3,1fr); gap:.75rem; margin-bottom:.875rem }
@media(max-width:768px){ .lp-filter-grid { grid-template-columns:1fr 1fr } }
@media(max-width:480px){ .lp-filter-grid { grid-template-columns:1fr } }
.lp-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.lp-field select,
.lp-field input { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:8px; padding:7px 10px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; outline:none; transition:border-color .15s,box-shadow .15s; appearance:none; -webkit-appearance:none }
.lp-field select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.lp-field select:focus,
.lp-field input:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.lp-field input[type="date"]::-webkit-calendar-picker-indicator { opacity:.5 }

.lp-filter-actions { display:flex; gap:.5rem; flex-wrap:wrap }
.lp-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; border:none; text-decoration:none; transition:opacity .15s,transform .1s; white-space:nowrap }
.lp-btn:hover { opacity:.88; transform:translateY(-1px); text-decoration:none }
.lp-btn-primary { background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff }
.lp-btn-excel   { background:linear-gradient(135deg,#10b981,#059669); color:#fff }
.lp-btn-pdf     { background:linear-gradient(135deg,#ef4444,#dc2626); color:#fff }

/* ── Main Card ── */
.lp-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.lp-card-header { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; flex-wrap:wrap; gap:.5rem }
.lp-card-title { font-size:13px; font-weight:700; color:#111; display:flex; align-items:center; gap:6px }
.lp-card-title i { color:#7c3aed }
.lp-count-pill { font-size:11px; font-weight:700; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 12px }
.lp-last-update { font-size:11px; color:#9ca3af }

/* ── Table ── */
.lp-table { width:100%; border-collapse:collapse; font-size:12.5px }
.lp-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.lp-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.lp-table tbody tr:last-child { border-bottom:none }
.lp-table tbody tr:hover td { background:#faf8ff }
.lp-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }

/* ── Badges ── */
.lp-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-success  { background:#d1fae5; color:#065f46 }
.b-danger   { background:#fee2e2; color:#991b1b }
.b-warning  { background:#fef3c7; color:#92400e }
.b-info     { background:#dbeafe; color:#1e40af }
.b-gray     { background:#e5e7eb; color:#374151 }
.b-teal     { background:#ccfbf1; color:#0f766e }
.b-dept     { background:#dbeafe; color:#1e40af }

/* ── Empty ── */
.lp-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.lp-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Mobile Cards ── */
.lp-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:box-shadow .2s }
.lp-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.lp-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.lp-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .lp-table-wrap { display:none }
}
@media(min-width:768px){ .lp-mob-list { display:none } }
</style>

<div class="lp-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="lp-topbar">
        <h1><i class="fas fa-chart-line"></i> Laporan {{ ucfirst($role) }}</h1>
        <p>Tinjau data laporan berdasarkan periode waktu dan departemen</p>
    </div>

    {{-- ═══ METRIC CARDS ═══ --}}
    <div class="lp-metrics">
        <div class="lp-metric purple">
            <div class="m-icon purple"><i class="fas fa-list-alt"></i></div>
            <div class="m-label">Total Data</div>
            <div class="m-value">{{ $data->count() }}</div>
            <div class="m-sub">Semua entri</div>
        </div>

        @if($role === 'keuangan')
        <div class="lp-metric green">
            <div class="m-icon green"><i class="fas fa-money-bill-wave"></i></div>
            <div class="m-label">Total Pencairan</div>
            <div class="m-value sm">Rp {{ number_format($data->sum('jumlah_dana'), 0, ',', '.') }}</div>
            <div class="m-sub">Akumulasi dana cair</div>
        </div>
        @else
        <div class="lp-metric green">
            <div class="m-icon green"><i class="fas fa-coins"></i></div>
            <div class="m-label">Total Anggaran</div>
            <div class="m-value sm">Rp {{ number_format($data->sum('total_anggaran'), 0, ',', '.') }}</div>
            <div class="m-sub">Akumulasi anggaran</div>
        </div>
        @endif

        <div class="lp-metric amber">
            <div class="m-icon amber"><i class="fas fa-calendar-alt"></i></div>
            <div class="m-label">Periode Filter</div>
            <div class="m-value sm">
                @if(request('start_date') && request('end_date'))
                    {{ \Carbon\Carbon::parse(request('start_date'))->format('d M Y') }}<br>
                    s/d {{ \Carbon\Carbon::parse(request('end_date'))->format('d M Y') }}
                @else
                    Semua Waktu
                @endif
            </div>
            <div class="m-sub">Rentang tanggal</div>
        </div>

        <div class="lp-metric blue">
            <div class="m-icon blue"><i class="fas fa-building"></i></div>
            <div class="m-label">Departemen</div>
            <div class="m-value sm">
                {{ request('departemen_id') ? ($departemens->find(request('departemen_id'))->nama_departemen ?? 'Semua') : 'Semua' }}
            </div>
            <div class="m-sub">Filter aktif</div>
        </div>
    </div>

    {{-- ═══ FILTER ═══ --}}
    <div class="lp-filter-card">
        <div class="lp-filter-title">
            <i class="fas fa-sliders-h" style="color:#7c3aed"></i> Filter & Ekspor
        </div>
        <form method="GET">
            <div class="lp-filter-grid">
                <div class="lp-field">
                    <label>Tanggal Mulai</label>
                    <input type="date" name="start_date" value="{{ request('start_date') }}">
                </div>
                <div class="lp-field">
                    <label>Tanggal Akhir</label>
                    <input type="date" name="end_date" value="{{ request('end_date') }}">
                </div>
                <div class="lp-field">
                    <label>Departemen</label>
                    <select name="departemen_id">
                        <option value="">Semua Departemen</option>
                        @foreach($departemens as $dept)
                            <option value="{{ $dept->id }}" {{ request('departemen_id') == $dept->id ? 'selected' : '' }}>
                                {{ $dept->nama_departemen }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="lp-filter-actions">
                <button type="submit" class="lp-btn lp-btn-primary">
                    <i class="fas fa-search"></i> Terapkan Filter
                </button>
                <a href="{{ route('admin.laporan.export.excel', request()->only('start_date','end_date','departemen_id')) }}" class="lp-btn lp-btn-excel">
                    <i class="fas fa-file-excel"></i> Export Excel
                </a>
                <a href="{{ route('admin.laporan.export.pdf', request()->only('start_date','end_date','departemen_id')) }}"
                   class="lp-btn lp-btn-pdf">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </form>
    </div>

    {{-- ═══ TABLE CARD ═══ --}}
    <div class="lp-card">
        <div class="lp-card-header">
            <div class="lp-card-title">
                <i class="fas fa-table"></i> Data Laporan
            </div>
            <div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap">
                <span class="lp-count-pill">{{ $data->count() }} Data</span>
                <span class="lp-last-update">
                    <i class="fas fa-sync-alt me-1"></i>{{ now()->format('d M Y, H:i') }}
                </span>
            </div>
        </div>

        {{-- ══ TABLE DESKTOP ══ --}}
        <div class="lp-table-wrap table-responsive">
            @if($role === 'keuangan')
            <table class="lp-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Judul Proposal</th>
                        <th style="min-width:120px">Pemohon</th>
                        <th style="min-width:130px">Departemen</th>
                        <th style="min-width:140px">Rekening</th>
                        <th style="width:140px;text-align:right">Jumlah Dana</th>
                        <th style="width:100px;text-align:center">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $i => $item)
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ $item->proposal->judul }}
                            </div>
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $item->proposal->user->name }}
                        </td>
                        <td>
                            <span class="lp-badge b-dept">
                                <i class="fas fa-building fa-sm me-1"></i>{{ $item->proposal->departemen->nama_departemen ?? '-' }}
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600;font-size:12px;color:#374151">{{ $item->rekening->nama_rekening }}</div>
                            <div style="font-size:10.5px;color:#9ca3af">{{ $item->rekening->nama_bank ?? '-' }}</div>
                        </td>
                        <td style="text-align:right;font-weight:700;color:#059669;font-size:12px">
                            Rp {{ number_format($item->jumlah_dana, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;font-size:11px;color:#6b7280">
                            {{ $item->created_at->format('d/m/Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="lp-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Tidak ada data laporan</div>
                                <div style="font-size:13px">Silakan sesuaikan filter untuk melihat hasil</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($data->count())
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;font-weight:700;font-size:12px;color:#374151;padding:10px 14px;background:#f9fafb;border-top:0.5px solid #e5e7eb">
                            Total Pencairan
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:12px;color:#059669;padding:10px 14px;background:#f9fafb;border-top:0.5px solid #e5e7eb">
                            Rp {{ number_format($data->sum('jumlah_dana'), 0, ',', '.') }}
                        </td>
                        <td style="background:#f9fafb;border-top:0.5px solid #e5e7eb"></td>
                    </tr>
                </tfoot>
                @endif
            </table>

            @else
            <table class="lp-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Judul Proposal</th>
                        <th style="min-width:120px">Pemohon</th>
                        <th style="min-width:130px">Departemen</th>
                        <th style="width:120px;text-align:center">Status</th>
                        <th style="width:150px;text-align:right">Total Anggaran</th>
                        <th style="width:100px;text-align:center">Tanggal</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($data as $i => $item)
                    @php
                        $badgeMap = [
                            'disetujui_senior_pastor'    => ['label'=>'Disetujui SP', 'cls'=>'b-success'],
                            'ditolak'                    => ['label'=>'Ditolak',      'cls'=>'b-danger'],
                            'revisi'                     => ['label'=>'Revisi',       'cls'=>'b-warning'],
                            'pending'                    => ['label'=>'Pending',      'cls'=>'b-gray'],
                            'disetujui_kepala_departemen'=> ['label'=>'Disetujui KD', 'cls'=>'b-info'],
                            'disetujui_mh'               => ['label'=>'Disetujui MH', 'cls'=>'b-info'],
                            'disetujui_gh'               => ['label'=>'Disetujui GH', 'cls'=>'b-info'],
                            'dicairkan'                  => ['label'=>'Dicairkan',    'cls'=>'b-teal'],
                            'dicairkan_sebagian'         => ['label'=>'Sebagian Cair','cls'=>'b-warning'],
                        ];
                        $st = $badgeMap[$item->status] ?? ['label'=>ucfirst(str_replace('_',' ',$item->status)),'cls'=>'b-gray'];
                    @endphp
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">{{ $item->judul }}</div>
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $item->user->name }}
                        </td>
                        <td>
                            <span class="lp-badge b-dept">
                                <i class="fas fa-building fa-sm me-1"></i>{{ $item->departemen->nama_departemen ?? '-' }}
                            </span>
                        </td>
                        <td style="text-align:center">
                            <span class="lp-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
                        </td>
                        <td style="text-align:right;font-weight:700;color:#059669;font-size:12px">
                            Rp {{ number_format($item->total_anggaran, 0, ',', '.') }}
                        </td>
                        <td style="text-align:center;font-size:11px;color:#6b7280">
                            {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="lp-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Tidak ada data laporan</div>
                                <div style="font-size:13px">Silakan sesuaikan filter untuk melihat hasil</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
                @if($data->count())
                <tfoot>
                    <tr>
                        <td colspan="5" style="text-align:right;font-weight:700;font-size:12px;color:#374151;padding:10px 14px;background:#f9fafb;border-top:0.5px solid #e5e7eb">
                            Total Anggaran
                        </td>
                        <td style="text-align:right;font-weight:700;font-size:12px;color:#059669;padding:10px 14px;background:#f9fafb;border-top:0.5px solid #e5e7eb">
                            Rp {{ number_format($data->sum('total_anggaran'), 0, ',', '.') }}
                        </td>
                        <td style="background:#f9fafb;border-top:0.5px solid #e5e7eb"></td>
                    </tr>
                </tfoot>
                @endif
            </table>
            @endif
        </div>

        {{-- ══ MOBILE CARDS ══ --}}
        <div class="lp-mob-list" style="padding:1rem">
            @if($role === 'keuangan')
                @forelse($data as $i => $item)
                <div class="lp-mob-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                        <div style="font-weight:700;font-size:13px;color:#111;line-height:1.3;flex:1">{{ $item->proposal->judul }}</div>
                        <span style="font-size:10px;font-weight:700;background:#ede9fe;color:#5b21b6;border-radius:20px;padding:2px 8px;flex-shrink:0">#{{ $i+1 }}</span>
                    </div>
                    <div class="lp-mob-meta">
                        <span class="lp-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $item->proposal->user->name }}</span>
                        <span class="lp-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $item->proposal->departemen->nama_departemen ?? '-' }}</span>
                    </div>
                    <div style="background:#f9fafb;border-radius:9px;padding:.625rem .875rem;margin:.5rem 0">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.25rem">
                            <span style="font-size:11px;color:#6b7280">Rekening</span>
                            <span style="font-weight:600;font-size:12px;color:#374151">{{ $item->rekening->nama_rekening }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;align-items:center">
                            <span style="font-size:11px;color:#6b7280">Jumlah Dana</span>
                            <span style="font-weight:700;font-size:13px;color:#059669">Rp {{ number_format($item->jumlah_dana,0,',','.') }}</span>
                        </div>
                    </div>
                    <div style="font-size:10.5px;color:#9ca3af">
                        <i class="fas fa-calendar fa-sm me-1"></i>{{ $item->created_at->format('d M Y') }}
                    </div>
                </div>
                @empty
                <div class="lp-empty">
                    <span class="ei"><i class="fas fa-inbox"></i></span>
                    <div style="font-weight:600;color:#374151">Tidak ada data laporan</div>
                </div>
                @endforelse

            @else
                @forelse($data as $i => $item)
                @php
                    $badgeMap = [
                        'disetujui_senior_pastor'    => ['label'=>'Disetujui SP', 'cls'=>'b-success'],
                        'ditolak'                    => ['label'=>'Ditolak',      'cls'=>'b-danger'],
                        'revisi'                     => ['label'=>'Revisi',       'cls'=>'b-warning'],
                        'pending'                    => ['label'=>'Pending',      'cls'=>'b-gray'],
                        'disetujui_kepala_departemen'=> ['label'=>'Disetujui KD', 'cls'=>'b-info'],
                        'disetujui_mh'               => ['label'=>'Disetujui MH', 'cls'=>'b-info'],
                        'disetujui_gh'               => ['label'=>'Disetujui GH', 'cls'=>'b-info'],
                        'dicairkan'                  => ['label'=>'Dicairkan',    'cls'=>'b-teal'],
                        'dicairkan_sebagian'         => ['label'=>'Sebagian Cair','cls'=>'b-warning'],
                    ];
                    $st = $badgeMap[$item->status] ?? ['label'=>ucfirst(str_replace('_',' ',$item->status)),'cls'=>'b-gray'];
                @endphp
                <div class="lp-mob-card">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                        <div style="font-weight:700;font-size:13px;color:#111;line-height:1.3;flex:1">{{ $item->judul }}</div>
                        <span class="lp-badge {{ $st['cls'] }}">{{ $st['label'] }}</span>
                    </div>
                    <div class="lp-mob-meta">
                        <span class="lp-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $item->user->name }}</span>
                        <span class="lp-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $item->departemen->nama_departemen ?? '-' }}</span>
                        <span class="lp-mob-chip"><i class="fas fa-calendar fa-sm"></i> {{ \Carbon\Carbon::parse($item->tanggal_pengajuan)->format('d/m/Y') }}</span>
                    </div>
                    <div style="background:#f9fafb;border-radius:9px;padding:.625rem .875rem;margin:.5rem 0;display:flex;justify-content:space-between;align-items:center">
                        <span style="font-size:11px;color:#6b7280">Total Anggaran</span>
                        <span style="font-weight:700;font-size:13px;color:#059669">Rp {{ number_format($item->total_anggaran,0,',','.') }}</span>
                    </div>
                </div>
                @empty
                <div class="lp-empty">
                    <span class="ei"><i class="fas fa-inbox"></i></span>
                    <div style="font-weight:600;color:#374151">Tidak ada data laporan</div>
                </div>
                @endforelse
            @endif
        </div>

    </div>{{-- end lp-card --}}

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection