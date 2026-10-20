@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   RIWAYAT PERSETUJUAN — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.ah-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.ah-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.75rem }
.ah-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }

/* ── Search / Filter Card ── */
.ah-filter-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; padding:1rem 1.25rem; margin-bottom:1rem; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.ah-filter-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#6b7280; margin-bottom:.75rem; display:flex; align-items:center; gap:6px }
.ah-filter-row { display:flex; gap:.75rem; flex-wrap:wrap; align-items:flex-end }
.ah-filter-row .ah-field { flex:1; min-width:180px }
.ah-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:4px }
.ah-field input,
.ah-field select { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:8px; padding:7px 10px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; outline:none; transition:border-color .15s,box-shadow .15s; appearance:none; -webkit-appearance:none }
.ah-field select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.ah-field input:focus,
.ah-field select:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.ah-btn { display:inline-flex; align-items:center; gap:5px; padding:7px 16px; border-radius:8px; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; border:none; text-decoration:none; transition:opacity .15s,transform .1s; white-space:nowrap; align-self:flex-end }
.ah-btn:hover { opacity:.88; transform:translateY(-1px) }
.ah-btn-primary { background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff }

/* ── Main Card ── */
.ah-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.ah-card-header { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; flex-wrap:wrap; gap:.5rem }
.ah-card-title { font-size:13px; font-weight:700; color:#111; display:flex; align-items:center; gap:6px }
.ah-card-title i { color:#7c3aed }
.ah-count-pill { font-size:11px; font-weight:700; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 12px }

/* ── Table ── */
.ah-table { width:100%; border-collapse:collapse; font-size:12.5px }
.ah-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.ah-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.ah-table tbody tr:last-child { border-bottom:none }
.ah-table tbody tr:hover td { background:#faf8ff }
.ah-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }

/* ── Badges ── */
.ah-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-success  { background:#d1fae5; color:#065f46 }
.b-danger   { background:#fee2e2; color:#991b1b }
.b-warning  { background:#fef3c7; color:#92400e }
.b-gray     { background:#e5e7eb; color:#374151 }
.b-teal     { background:#ccfbf1; color:#0f766e }
.b-purple   { background:#ede9fe; color:#5b21b6 }

/* ── Action button ── */
.ah-acts { display:flex; gap:4px; align-items:center; justify-content:center }
.ah-act  { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:opacity .15s; text-decoration:none }
.ah-act:hover { opacity:.8 }
.ah-act.view { background:#ede9fe; color:#5b21b6 }

/* ── Empty ── */
.ah-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.ah-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Pagination ── */
.ah-pagination { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-top:0.5px solid #f3f4f6; font-size:12px; color:#6b7280; flex-wrap:wrap; gap:.5rem }

/* ── Mobile Cards ── */
.ah-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:box-shadow .2s; border-left:3px solid transparent }
.ah-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.ah-mob-card.approved { border-left-color:#10b981 }
.ah-mob-card.rejected { border-left-color:#ef4444 }
.ah-mob-card.process  { border-left-color:#9ca3af }
.ah-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.ah-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .ah-card > .table-responsive { display:none }
    .ah-pagination-desktop { display:none }
}
@media(min-width:768px){ .ah-mob-list { display:none } }
</style>

<div class="ah-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="ah-topbar">
        <h1>
            <i class="fas fa-history"></i>
            Riwayat Persetujuan Proposal
        </h1>
    </div>

    {{-- ═══ FILTER ═══ --}}
    <div class="ah-filter-card">
        <div class="ah-filter-title">
            <i class="fas fa-search" style="color:#7c3aed"></i> Pencarian & Filter
        </div>
        <form method="GET" action="{{ route('admin.approval.history') }}">
            <div class="ah-filter-row">
                <div class="ah-field">
                    <label>Cari Proposal</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           placeholder="Nama atau judul proposal...">
                </div>
                <div class="ah-field" style="max-width:180px">
                    <label>Status</label>
                    <select name="status">
                        <option value="">Semua Status</option>
                        <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                        <option value="ditolak"   {{ request('status') == 'ditolak'   ? 'selected' : '' }}>Ditolak</option>
                    </select>
                </div>
                <button type="submit" class="ah-btn ah-btn-primary">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </form>
    </div>

    {{-- ═══ TABLE CARD ═══ --}}
    <div class="ah-card">
        <div class="ah-card-header">
            <div class="ah-card-title">
                <i class="fas fa-list-ul"></i> Riwayat Persetujuan
            </div>
            <span class="ah-count-pill">{{ $historyApprovals->total() }} Data</span>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-responsive">
            <table class="ah-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Judul Proposal</th>
                        <th style="min-width:120px">Departemen</th>
                        <th style="min-width:110px">Pemohon</th>
                        <th style="width:110px;text-align:center">Status</th>
                        <th style="width:110px;text-align:center">Pencairan</th>
                        <th style="width:110px;text-align:center">Tahap</th>
                        <th style="width:110px;text-align:center">Tanggal</th>
                        <th style="width:70px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($historyApprovals as $i => $flow)
                    @php
                        $isApproved = in_array($flow->status, ['disetujui_senior_pastor','dicairkan']);
                        $isRejected = $flow->status === 'ditolak';
                        $lastFlow = $flow->approval_flows
                            ->whereIn('status', ['disetujui','ditolak'])
                            ->sortByDesc('tanggal_approve')
                            ->first();
                        $tanggal = $lastFlow?->tanggal_approve ?? $flow->updated_at;
                        $tahap   = $flow->tahap_terakhir ?? ($lastFlow?->tahap ? ucfirst(str_replace('_',' ',$lastFlow->tahap)) : '-');
                    @endphp
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">
                            {{ $loop->iteration + ($historyApprovals->firstItem() - 1) }}
                        </td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ Str::limit($flow->judul, 40) }}
                            </div>
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $flow->departemen->nama_departemen ?? '-' }}
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $flow->user->name ?? '-' }}
                        </td>
                        <td style="text-align:center">
                            @if($isApproved)
                                <span class="ah-badge b-success">✓ Disetujui</span>
                            @elseif($isRejected)
                                <span class="ah-badge b-danger">✕ Ditolak</span>
                            @else
                                <span class="ah-badge b-gray">⏳ Proses</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            @if($flow->status === 'dicairkan')
                                <span class="ah-badge b-teal">✓ Dicairkan</span>
                            @else
                                <span class="ah-badge b-warning">Belum Cair</span>
                            @endif
                        </td>
                        <td style="text-align:center">
                            <span class="ah-badge b-purple" style="font-size:9.5px">{{ $tahap }}</span>
                        </td>
                        <td style="text-align:center;font-size:11px;color:#6b7280;white-space:nowrap">
                            {{ \Carbon\Carbon::parse($tanggal)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                        </td>
                        <td>
                            <div class="ah-acts">
                                <a href="{{ route('admin.proposal.show', $flow->id) }}"
                                   class="ah-act view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9">
                            <div class="ah-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Belum ada riwayat persetujuan</div>
                                <div style="font-size:13px">Data akan muncul setelah ada proposal yang diproses</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="ah-pagination ah-pagination-desktop">
            <span>
                @if($historyApprovals->total())
                    Menampilkan {{ $historyApprovals->firstItem() }}–{{ $historyApprovals->lastItem() }}
                    dari {{ $historyApprovals->total() }} data
                @else
                    Tidak ada data
                @endif
            </span>
            <div>{{ $historyApprovals->appends(request()->query())->links() }}</div>
        </div>
    </div>

    {{-- ═══ MOBILE CARDS ═══ --}}
    <div class="ah-mob-list">
        @forelse($historyApprovals as $flow)
        @php
            $isApproved = in_array($flow->status, ['disetujui_senior_pastor','dicairkan']);
            $isRejected = $flow->status === 'ditolak';
            $lastFlow = $flow->approval_flows
                ->whereIn('status', ['disetujui','ditolak'])
                ->sortByDesc('tanggal_approve')
                ->first();
            $tanggal = $lastFlow?->tanggal_approve ?? $flow->updated_at;
            $tahap   = $flow->tahap_terakhir ?? ($lastFlow?->tahap ? ucfirst(str_replace('_',' ',$lastFlow->tahap)) : '-');
            $mobClass = $isApproved ? 'approved' : ($isRejected ? 'rejected' : 'process');
        @endphp
        <div class="ah-mob-card {{ $mobClass }}">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                <div style="font-weight:700;font-size:13.5px;color:#111;line-height:1.3;flex:1">
                    {{ Str::limit($flow->judul, 45) }}
                </div>
                <div style="display:flex;flex-direction:column;gap:.25rem;align-items:flex-end;flex-shrink:0">
                    @if($isApproved)
                        <span class="ah-badge b-success">✓ Disetujui</span>
                    @elseif($isRejected)
                        <span class="ah-badge b-danger">✕ Ditolak</span>
                    @else
                        <span class="ah-badge b-gray">⏳ Proses</span>
                    @endif
                    @if($flow->status === 'dicairkan')
                        <span class="ah-badge b-teal">Dicairkan</span>
                    @else
                        <span class="ah-badge b-warning">Belum Cair</span>
                    @endif
                </div>
            </div>

            <div class="ah-mob-meta">
                <span class="ah-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $flow->departemen->nama_departemen ?? '-' }}</span>
                <span class="ah-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $flow->user->name ?? '-' }}</span>
                <span class="ah-badge b-purple" style="font-size:9.5px">{{ $tahap }}</span>
            </div>

            <div style="background:#f9fafb;border-radius:9px;padding:.5rem .875rem;margin:.5rem 0;display:flex;justify-content:space-between;align-items:center">
                <span style="font-size:11px;color:#6b7280"><i class="fas fa-calendar fa-sm me-1"></i>Tanggal</span>
                <span style="font-weight:600;font-size:11.5px;color:#374151">
                    {{ \Carbon\Carbon::parse($tanggal)->timezone('Asia/Jakarta')->format('d/m/Y H:i') }}
                </span>
            </div>

            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <a href="{{ route('admin.proposal.show', $flow->id) }}"
                   style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;text-decoration:none">
                    <i class="fas fa-eye me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        @empty
        <div class="ah-empty">
            <span class="ei"><i class="fas fa-inbox"></i></span>
            <div style="font-weight:600;color:#374151">Belum ada riwayat persetujuan</div>
        </div>
        @endforelse

        <div style="margin-top:1rem">
            {{ $historyApprovals->appends(request()->query())->links() }}
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection