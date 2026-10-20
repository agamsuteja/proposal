@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   PENDING APPROVAL — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.pa-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pa-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pa-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pa-count-badge { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:20px; padding:4px 14px; font-size:12px; font-weight:700 }

/* ── Alerts ── */
.pa-alert { border-radius:10px; padding:.75rem 1rem; font-size:13px; margin-bottom:1rem; display:flex; align-items:flex-start; gap:9px; border:0.5px solid }
.pa-alert.success { background:#ecfdf5; color:#065f46; border-color:#6ee7b7 }
.pa-alert.info    { background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe }
.pa-alert-close   { margin-left:auto; background:none; border:none; font-size:16px; cursor:pointer; opacity:.6; line-height:1 }
.pa-alert-close:hover { opacity:1 }

/* ── Section label ── */
.pa-section-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; margin-bottom:.75rem; display:flex; align-items:center; gap:6px }
.pa-section-label::after { content:''; flex:1; height:0.5px; background:#e5e7eb }

/* ── Main Card ── */
.pa-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.pa-card-header { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; flex-wrap:wrap; gap:.5rem }
.pa-card-title  { font-size:13px; font-weight:700; color:#111; display:flex; align-items:center; gap:6px }
.pa-card-title i { color:#7c3aed }
.pa-count-pill  { font-size:11px; font-weight:700; background:#fef3c7; color:#92400e; border-radius:20px; padding:3px 12px }

/* ── Table ── */
.pa-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pa-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.pa-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pa-table tbody tr:last-child { border-bottom:none }
.pa-table tbody tr:hover td { background:#faf8ff }
.pa-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }

/* ── Badges ── */
.pa-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-amber  { background:#fef3c7; color:#92400e }
.b-purple { background:#ede9fe; color:#5b21b6 }

/* ── Action buttons ── */
.pa-acts { display:flex; gap:4px; align-items:center; justify-content:center }
.pa-act  { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:opacity .15s; text-decoration:none }
.pa-act:hover { opacity:.8 }
.pa-act.view { background:#ede9fe; color:#5b21b6 }

/* ── Empty ── */
.pa-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.pa-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Mobile Cards ── */
.pa-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-left:3px solid #f59e0b; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:box-shadow .2s }
.pa-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.pa-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.pa-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .pa-card > .table-responsive { display:none }
}
@media(min-width:768px){ .pa-mob-list { display:none } }
</style>

<div class="pa-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pa-topbar">
        <h1>
            <i class="fas fa-clock"></i>
            Proposal Menunggu Persetujuan
        </h1>
        <span class="pa-count-badge">
            <i class="fas fa-list me-1"></i>{{ $pendingApprovals->count() }} Pending
        </span>
    </div>

    {{-- ═══ ALERTS ═══ --}}
    @if(session('success'))
        <div class="pa-alert success">
            <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('success') }}</div>
            <button class="pa-alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    @if(in_array(Auth::user()->role, ['karyawan','departemen_head']))
        <div class="pa-alert info">
            <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>Anda hanya melihat proposal dari departemen:
                <strong>{{ Auth::user()->departemen->nama_departemen ?? '-' }}</strong>
            </div>
        </div>
    @elseif(Auth::user()->role === 'group_head')
        <div class="pa-alert info">
            <i class="fas fa-info-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>Anda melihat proposal dari semua departemen di grup:
                <strong>{{ Auth::user()->grup->nama_grup ?? '-' }}</strong>
            </div>
        </div>
    @endif

    {{-- ═══ TABLE CARD ═══ --}}
    <div class="pa-card">
        <div class="pa-card-header">
            <div class="pa-card-title">
                <i class="fas fa-hourglass-half"></i> Menunggu Persetujuan
            </div>
            <span class="pa-count-pill">{{ $pendingApprovals->count() }} Proposal</span>
        </div>

        {{-- DESKTOP TABLE --}}
        <div class="table-responsive">
            <table class="pa-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Judul Proposal</th>
                        <th style="min-width:130px">Departemen</th>
                        <th style="min-width:110px">Pemohon</th>
                        <th style="width:120px;text-align:center">Status</th>
                        <th style="width:130px;text-align:center">Tahap</th>
                        <th style="width:70px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingApprovals as $i => $flow)
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">{{ $i + 1 }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ Str::limit($flow->proposal->judul, 45) }}
                            </div>
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $flow->proposal->departemen->nama_departemen ?? '-' }}
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $flow->proposal->user->name ?? '-' }}
                        </td>
                        <td style="text-align:center">
                            <span class="pa-badge b-amber">⏳ {{ strtoupper($flow->status) }}</span>
                        </td>
                        <td style="text-align:center">
                            <span class="pa-badge b-purple" style="font-size:9.5px">
                                {{ ucfirst(str_replace('_', ' ', $flow->tahap)) }}
                            </span>
                        </td>
                        <td>
                            <div class="pa-acts">
                                <a href="{{ route('admin.approval.show', $flow->proposal->id) }}"
                                   class="pa-act view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7">
                            <div class="pa-empty">
                                <span class="ei"><i class="fas fa-check-circle"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Tidak ada proposal menunggu persetujuan</div>
                                <div style="font-size:13px">Semua proposal sudah diproses</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ MOBILE CARDS ═══ --}}
    <div class="pa-mob-list">
        @forelse($pendingApprovals as $i => $flow)
        <div class="pa-mob-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                <div style="font-weight:700;font-size:13.5px;color:#111;line-height:1.3;flex:1">
                    {{ Str::limit($flow->proposal->judul, 45) }}
                </div>
                <span class="pa-badge b-amber" style="flex-shrink:0">⏳ Pending</span>
            </div>

            <div class="pa-mob-meta">
                <span class="pa-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $flow->proposal->departemen->nama_departemen ?? '-' }}</span>
                <span class="pa-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $flow->proposal->user->name ?? '-' }}</span>
                <span class="pa-badge b-purple" style="font-size:9.5px">
                    {{ ucfirst(str_replace('_', ' ', $flow->tahap)) }}
                </span>
            </div>

            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <a href="{{ route('admin.approval.show', $flow->proposal->id) }}"
                   style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;text-decoration:none">
                    <i class="fas fa-eye me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        @empty
        <div class="pa-empty">
            <span class="ei"><i class="fas fa-check-circle"></i></span>
            <div style="font-weight:600;color:#374151">Tidak ada proposal menunggu persetujuan</div>
            <div style="font-size:13px">Semua proposal sudah diproses</div>
        </div>
        @endforelse
    </div>

</div>

@endsection