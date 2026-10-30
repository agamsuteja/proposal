@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   PENCAIRAN DANA INDEX — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.pc-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pc-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pc-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pc-count-badge { background:rgba(255,255,255,.2); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:20px; padding:4px 14px; font-size:12px; font-weight:700 }

/* ── Alerts ── */
.pc-alert { border-radius:10px; padding:.75rem 1rem; font-size:13px; margin-bottom:1rem; display:flex; align-items:flex-start; gap:9px; border:0.5px solid }
.pc-alert.success { background:#ecfdf5; color:#065f46; border-color:#6ee7b7 }
.pc-alert.danger  { background:#fef2f2; color:#991b1b; border-color:#fecaca }
.pc-alert-close { margin-left:auto; background:none; border:none; font-size:16px; cursor:pointer; opacity:.6; line-height:1 }
.pc-alert-close:hover { opacity:1 }

/* ── Main Card ── */
.pc-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.pc-card-header { display:flex; justify-content:space-between; align-items:center; padding:.875rem 1.25rem; border-bottom:0.5px solid #f3f4f6; flex-wrap:wrap; gap:.5rem }
.pc-card-title { font-size:13px; font-weight:700; color:#111; display:flex; align-items:center; gap:6px }
.pc-card-title i { color:#7c3aed }
.pc-count-pill { font-size:11px; font-weight:700; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:3px 12px }

/* ── Table ── */
.pc-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pc-table thead th { background:#f9fafb; color:#6b7280; font-weight:600; font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; padding:10px 14px; text-align:left; border-bottom:0.5px solid #e5e7eb; white-space:nowrap }
.pc-table tbody tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pc-table tbody tr:last-child { border-bottom:none }
.pc-table tbody tr:hover td { background:#faf8ff }
.pc-table tbody td { padding:11px 14px; color:#374151; vertical-align:middle }
.pc-table tfoot td { padding:10px 14px; background:#f9fafb; font-size:12px; color:#6b7280; border-top:0.5px solid #e5e7eb }

/* ── Badges ── */
.pc-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; white-space:nowrap; display:inline-block }
.b-green  { background:#d1fae5; color:#065f46 }
.b-amber  { background:#fef3c7; color:#92400e }
.b-teal   { background:#ccfbf1; color:#0f766e }

/* ── Progress bar ── */
.pc-prog-track { height:5px; background:#f3f4f6; border-radius:10px; overflow:hidden; border:0.5px solid #e5e7eb; margin-top:3px }
.pc-prog-fill  { height:100%; border-radius:10px }
.pc-prog-label { font-size:10px; color:#6b7280; display:flex; justify-content:space-between; margin-bottom:2px }

/* ── Actions ── */
.pc-acts { display:flex; gap:4px; align-items:center; justify-content:center }
.pc-act  { width:28px; height:28px; border-radius:7px; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px; transition:opacity .15s; text-decoration:none }
.pc-act:hover { opacity:.8 }
.pc-act.view  { background:#ede9fe; color:#5b21b6 }
.pc-act.cair  { background:#d1fae5; color:#065f46 }

/* ── Empty ── */
.pc-empty { text-align:center; padding:3rem 1rem; color:#9ca3af }
.pc-empty .ei { font-size:3rem; margin-bottom:.75rem; display:block; opacity:.35 }

/* ── Mobile Cards ── */
.pc-mob-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:12px; padding:1rem; margin-bottom:.75rem; box-shadow:0 1px 3px rgba(0,0,0,.04); transition:box-shadow .2s }
.pc-mob-card:hover { box-shadow:0 4px 14px rgba(0,0,0,.1) }
.pc-mob-meta { display:flex; flex-wrap:wrap; gap:.35rem; margin:.5rem 0 }
.pc-mob-chip { font-size:10.5px; background:#f3f4f6; color:#374151; border-radius:6px; padding:2px 8px; display:inline-flex; align-items:center; gap:4px }

@media(max-width:767.98px){
    .pc-card > .table-responsive { display:none }
}
@media(min-width:768px){ .pc-mob-list { display:none } }

/* ── Modal ── */
.pc-modal .modal-content { border:none; border-radius:14px; box-shadow:0 20px 60px rgba(0,0,0,.18) }
.pc-modal .modal-header { background:linear-gradient(135deg,#7c3aed,#4c1d95); border-radius:14px 14px 0 0; padding:1rem 1.25rem }
.pc-modal .modal-title { color:#fff; font-size:14px; font-weight:700; display:flex; align-items:center; gap:7px }
.pc-modal .modal-body { padding:1.25rem }
.pc-modal .modal-footer { background:#f9fafb; border-radius:0 0 14px 14px; padding:.875rem 1.25rem; border-top:0.5px solid #e5e7eb; gap:.5rem }

/* Modal info box */
.pc-modal-info { background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:10px; padding:.875rem 1rem; margin-bottom:1rem }
.pc-modal-info-title { font-size:13px; font-weight:700; color:#111; margin-bottom:.375rem }
.pc-modal-info-row { display:flex; justify-content:space-between; font-size:11.5px; color:#6b7280; margin-bottom:.2rem }
.pc-modal-info-row .val { font-weight:600; color:#374151 }
.pc-modal-info-row .val.success { color:#059669 }
.pc-modal-info-row .val.danger { color:#dc2626 }

/* Modal fields */
.pc-modal .pf-label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:5px }
.pc-modal select,
.pc-modal input[type="number"],
.pc-modal input[type="text"],
.pc-modal input[type="file"],
.pc-modal textarea { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:8px; padding:8px 11px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:13px; outline:none; transition:border-color .15s,box-shadow .15s; appearance:none; -webkit-appearance:none; margin-bottom:.125rem }
.pc-modal select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.pc-modal select:focus,
.pc-modal input:focus,
.pc-modal textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.pc-modal .pf-hint { font-size:11px; color:#9ca3af; margin-bottom:.875rem }

/* Radio metode */
.pc-modal .metode-options { display:flex; gap:1rem; flex-wrap:wrap; margin-bottom:.875rem }
.pc-modal .metode-opt { display:flex; align-items:center; gap:.4rem; cursor:pointer }
.pc-modal .metode-opt input { width:auto; margin:0; cursor:pointer }
.pc-modal .metode-opt span { font-size:13px; color:#374151; font-weight:600 }

/* Modal buttons */
.pc-modal-btn { display:flex; align-items:center; justify-content:center; gap:6px; padding:9px 20px; border-radius:9px; font-family:'Nunito',system-ui,sans-serif; font-size:13px; font-weight:700; cursor:pointer; border:none; transition:opacity .15s,transform .1s; text-decoration:none }
.pc-modal-btn:hover { opacity:.88; transform:translateY(-1px) }
.pc-modal-btn-confirm { background:linear-gradient(135deg,#10b981,#059669); color:#fff }
.pc-modal-btn-cancel  { background:#f3f4f6; color:#374151; border:0.5px solid #e5e7eb }
</style>

<div class="pc-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pc-topbar">
        <h1>
            <i class="fas fa-money-bill-wave"></i>
            Modul Pencairan Dana
        </h1>
        <span class="pc-count-badge">
            <i class="fas fa-list me-1"></i>{{ $proposals->count() }} Proposal
        </span>
    </div>

    {{-- ═══ ALERTS ═══ --}}
    @if(session('success'))
        <div class="pc-alert success">
            <i class="fas fa-check-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('success') }}</div>
            <button class="pc-alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @elseif(session('error'))
        <div class="pc-alert danger">
            <i class="fas fa-exclamation-circle" style="flex-shrink:0;margin-top:1px"></i>
            <div>{{ session('error') }}</div>
            <button class="pc-alert-close" onclick="this.parentElement.remove()">×</button>
        </div>
    @endif

    {{-- ═══ TABLE (DESKTOP) ═══ --}}
    <div class="pc-card">
        <div class="pc-card-header">
            <div class="pc-card-title">
                <i class="fas fa-list-ul"></i> Daftar Proposal Siap Cairkan
            </div>
            <span class="pc-count-pill">{{ $proposals->count() }} Proposal</span>
        </div>

        <div class="table-responsive">
            <table class="pc-table">
                <thead>
                    <tr>
                        <th style="width:40px">#</th>
                        <th style="min-width:200px">Judul Proposal</th>
                        <th style="min-width:120px">Departemen</th>
                        <th style="min-width:100px">Pemohon</th>
                        <th style="width:140px;text-align:right">Anggaran</th>
                        <th style="min-width:120px">Progress</th>
                        <th style="width:110px;text-align:center">Status</th>
                        <th style="width:80px;text-align:center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($proposals as $p)
                    @php
                        $totalCair = $p->pencairan->sum('jumlah_dana');
                        $sisa = $p->total_anggaran - $totalCair;
                        $persen = $p->total_anggaran > 0 ? round(($totalCair / $p->total_anggaran) * 100, 1) : 0;
                        $progColor = $persen >= 100 ? '#10b981' : ($persen > 0 ? '#f59e0b' : '#9ca3af');
                    @endphp
                    <tr>
                        <td style="color:#9ca3af;font-weight:700;font-size:12px">{{ $loop->iteration }}</td>
                        <td>
                            <div style="font-weight:700;font-size:12.5px;color:#111;line-height:1.3">
                                {{ $p->judul }}
                            </div>
                            @if($p->jenis === 'acara' && ($p->tanggal_acara || $p->lokasi_acara))
                                <div style="font-size:10.5px;color:#9ca3af;margin-top:2px">
                                    @if($p->tanggal_acara)
                                        <i class="fas fa-calendar-day fa-sm" style="color:#c4b5fd"></i>
                                        {{ \Carbon\Carbon::parse($p->tanggal_acara)->format('d M Y') }}
                                    @endif
                                    @if($p->lokasi_acara)
                                        · <i class="fas fa-map-marker-alt fa-sm" style="color:#c4b5fd"></i> {{ $p->lokasi_acara }}
                                    @endif
                                </div>
                            @endif
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $p->departemen->nama_departemen ?? '-' }}
                        </td>
                        <td style="font-size:11.5px;color:#6b7280">
                            <i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:3px"></i>
                            {{ $p->user->name ?? '-' }}
                        </td>
                        <td style="text-align:right">
                            <div style="font-weight:700;color:#059669;font-size:12px">
                                Rp {{ number_format($p->total_anggaran, 2, ',', '.') }}
                            </div>
                            <div style="font-size:10.5px;color:#dc2626">
                                Sisa: Rp {{ number_format($sisa, 2, ',', '.') }}
                            </div>
                        </td>
                        <td>
                            <div class="pc-prog-label">
                                <span>{{ $persen }}%</span>
                                @if($persen >= 100)<span style="color:#10b981">✓</span>@endif
                            </div>
                            <div class="pc-prog-track">
                                <div class="pc-prog-fill" style="width:{{ min($persen,100) }}%;background:{{ $progColor }}"></div>
                            </div>
                        </td>
                        <td style="text-align:center">
                            @if($p->status === 'dicairkan')
                                <span class="pc-badge b-teal">✓ Sudah Cair</span>
                            @elseif($p->status === 'dicairkan_sebagian')
                                <span class="pc-badge b-amber">⚡ Sebagian</span>
                            @else
                                <span class="pc-badge b-green">● Siap Cair</span>
                            @endif
                        </td>
                        <td>
                            <div class="pc-acts">
                                <a href="{{ route('admin.pencairan.show', $p->id) }}"
                                   class="pc-act view" title="Lihat Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </td>
                    </tr>

                    @empty
                    <tr>
                        <td colspan="8">
                            <div class="pc-empty">
                                <span class="ei"><i class="fas fa-inbox"></i></span>
                                <div style="font-weight:600;margin-bottom:.25rem;color:#374151">Tidak ada proposal yang siap dicairkan</div>
                                <div style="font-size:13px">Silakan cek kembali daftar proposal Anda</div>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ═══ MOBILE CARDS ═══ --}}
    <div class="pc-mob-list">
        @forelse($proposals as $p)
        @php
            $totalCair = $p->pencairan->sum('jumlah_dana');
            $sisa = $p->total_anggaran - $totalCair;
            $persen = $p->total_anggaran > 0 ? round(($totalCair / $p->total_anggaran) * 100, 1) : 0;
            $progColor = $persen >= 100 ? '#10b981' : ($persen > 0 ? '#f59e0b' : '#9ca3af');
        @endphp
        <div class="pc-mob-card">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:.5rem;margin-bottom:.375rem">
                <div style="font-weight:700;font-size:13.5px;color:#111;line-height:1.3;flex:1">{{ $p->judul }}</div>
                @if($p->status === 'dicairkan')
                    <span class="pc-badge b-teal">Sudah Cair</span>
                @elseif($p->status === 'dicairkan_sebagian')
                    <span class="pc-badge b-amber">Sebagian</span>
                @else
                    <span class="pc-badge b-green">Siap Cair</span>
                @endif
            </div>

            <div class="pc-mob-meta">
                <span class="pc-mob-chip"><i class="fas fa-building fa-sm"></i> {{ $p->departemen->nama_departemen ?? '-' }}</span>
                <span class="pc-mob-chip"><i class="fas fa-user fa-sm"></i> {{ $p->user->name ?? '-' }}</span>
            </div>

            <div style="background:#f9fafb;border-radius:9px;padding:.625rem .875rem;margin:.5rem 0">
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.375rem">
                    <span style="font-size:11px;color:#6b7280">Total Anggaran</span>
                    <span style="font-weight:700;font-size:13px;color:#059669">Rp {{ number_format($p->total_anggaran,2,',','.') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:.375rem">
                    <span style="font-size:11px;color:#6b7280">Sisa Dana</span>
                    <span style="font-weight:700;font-size:12px;color:#dc2626">Rp {{ number_format($sisa,2,',','.') }}</span>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:10.5px;color:#6b7280;margin-bottom:3px">
                    <span>Progress Pencairan</span><span>{{ $persen }}%</span>
                </div>
                <div class="pc-prog-track">
                    <div class="pc-prog-fill" style="width:{{ min($persen,100) }}%;background:{{ $progColor }}"></div>
                </div>
            </div>

            <div style="display:flex;gap:.5rem;margin-top:.5rem">
                <a href="{{ route('admin.pencairan.show', $p->id) }}"
                   style="flex:1;text-align:center;padding:7px;border-radius:8px;background:#ede9fe;color:#5b21b6;font-size:12px;font-weight:700;text-decoration:none">
                    <i class="fas fa-eye me-1"></i> Lihat Detail
                </a>
            </div>
        </div>
        @empty
        <div class="pc-empty">
            <span class="ei"><i class="fas fa-inbox"></i></span>
            <div style="font-weight:600;color:#374151">Tidak ada proposal yang siap dicairkan</div>
        </div>
        @endforelse
    </div>

</div>



@endsection