@extends('backend.layouts.app')
@section('title', 'Log Aktivitas')
@section('content')
@include('backend.budget.partials.styles')
@php
    $actionLabels = ['account.adjustment'=>['Penyesuaian saldo','amber','bi-bank'], 'proposal.revision_requested'=>['Permintaan revisi','amber','bi-pencil-square'], 'audit.archived'=>['Arsip audit','gray','bi-archive'], 'created' => ['Data ditambahkan', 'green', 'bi-plus-circle'], 'updated' => ['Data diperbarui', 'blue', 'bi-pencil-square'], 'deleted' => ['Data dihapus', 'red', 'bi-trash'], 'request' => ['Akses halaman', 'gray', 'bi-window'], 'auth.login' => ['Login berhasil', 'green', 'bi-box-arrow-in-right'], 'auth.logout' => ['Logout', 'purple', 'bi-box-arrow-right'], 'auth.failed' => ['Login gagal', 'red', 'bi-shield-exclamation']];
    $subjectLabels = ['auth_challenges'=>'Verifikasi akun', 'notification_outbox'=>'Notifikasi', 'budget_request_versions'=>'Riwayat revisi', 'budget_adjustments'=>'Penyesuaian budget', 'budget_periods'=>'Periode anggaran', 'budget_reversals'=>'Koreksi transfer', 'budget_departemen' => 'Budget departemen', 'budget_requests' => 'Pengajuan tambahan', 'budget_approvals' => 'Persetujuan budget', 'budget_transfers' => 'Transfer budget', 'users' => 'Pengguna', 'departemen' => 'Departemen', 'grup' => 'Grup', 'proposal' => 'Proposal', 'proposal_items' => 'Item proposal', 'approval_proposal' => 'Persetujuan proposal', 'pencairan_dana' => 'Pencairan dana', 'pengembalian_dana' => 'Pengembalian dana', 'rekening' => 'Rekening', 'nota_penggunaan_proposal' => 'Nota penggunaan', 'log_proposal' => 'Riwayat proposal'];
    $routeLabels = ['admin.budget.index' => 'Budget Departemen', 'admin.budget.requests' => 'Tambahan Budget', 'admin.budget.save' => 'Simpan alokasi budget', 'admin.budget.store' => 'Ajukan tambahan budget', 'admin.budget.decide' => 'Persetujuan tambahan budget', 'admin.budget.fund' => 'Transfer budget', 'admin.activity.index' => 'Log Aktivitas', 'admin.dashboard' => 'Dashboard', 'admin.login' => 'Halaman login', 'admin.login.submit' => 'Proses login', 'admin.logout' => 'Logout'];
@endphp
<div class="budget-page">
    <header class="budget-topbar">
        <div><h1><i class="bi bi-clock-history" aria-hidden="true"></i>Log Aktivitas</h1><p>Pantau aktivitas pengguna dan telusuri perubahan data aplikasi.</p></div>
        <a class="budget-button light" href="{{ route('admin.activity.export', request()->only('action','user_id','tanggal_awal','tanggal_akhir')) }}">Ekspor log</a><span class="budget-badge" style="background:#ffffff20;color:#fff"><i class="bi bi-shield-check" aria-hidden="true"></i>Riwayat hanya baca</span>
    </header>
    @include('backend.budget.partials.alerts')
    <section class="budget-card" aria-labelledby="filter-heading">
        <div class="budget-card-header"><h2 id="filter-heading"><i class="bi bi-funnel me-2" aria-hidden="true"></i>Filter aktivitas</h2>@if(request()->hasAny(['action', 'user_id', 'tanggal_awal', 'tanggal_akhir']))<a class="budget-button secondary" href="{{ route('admin.activity.index') }}"><i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i>Reset filter</a>@endif</div>
        <div class="budget-card-body">
            <form method="get" class="budget-toolbar">
                <div class="budget-field"><label for="action">Jenis aktivitas</label><select class="budget-input" id="action" name="action"><option value="">Semua aktivitas</option>@foreach($actionLabels as $value => $label)<option value="{{ $value }}" @selected(request('action') === $value)>{{ $label[0] }}</option>@endforeach @if(request('action') && !isset($actionLabels[request('action')]))<option value="{{ request('action') }}" selected>{{ request('action') }}</option>@endif</select></div>
                <div class="budget-field"><label for="user_id">ID pengguna</label><input class="budget-input" id="user_id" type="number" name="user_id" min="1" value="{{ request('user_id') }}" placeholder="Semua pengguna"></div>
                <div class="budget-field"><label for="tanggal_awal">Dari tanggal</label><input class="budget-input" id="tanggal_awal" type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}"></div>
                <div class="budget-field"><label for="tanggal_akhir">Sampai tanggal</label><input class="budget-input" id="tanggal_akhir" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></div>
                <button class="budget-button primary"><i class="bi bi-search" aria-hidden="true"></i>Tampilkan</button>
            </form>
        </div>
    </section>
    <section class="budget-card" aria-labelledby="history-heading">
        <div class="budget-card-header"><div><h2 id="history-heading">Riwayat aktivitas</h2><p class="budget-subtitle">Aktivitas terbaru ditampilkan lebih dahulu.</p></div><span class="budget-badge purple">{{ number_format($logs->total(), 0, ',', '.') }} aktivitas</span></div>
        <div class="budget-table-wrap">
            <table class="budget-table">
                <thead><tr><th scope="col">Waktu</th><th scope="col">Pengguna</th><th scope="col">Aktivitas</th><th scope="col">Halaman / Data</th><th scope="col">Detail</th></tr></thead>
                <tbody>
                @forelse($logs as $log)
                    @php
                        [$actionLabel, $actionColor, $actionIcon] = $actionLabels[$log->action] ?? [$log->action, 'gray', 'bi-activity'];
                        $subjectLabel = $subjectLabels[$log->subject_type] ?? \Illuminate\Support\Str::headline($log->subject_type ?? '');
                        $routeLabel = $routeLabels[$log->route] ?? ($log->route ? \Illuminate\Support\Str::headline(str_replace(['admin.', '.'], ['', ' '], $log->route)) : '');
                    @endphp
                    <tr>
                        <td class="budget-time">{{ $log->created_at?->format('d M Y') }}<small>{{ $log->created_at?->format('H:i:s') }}</small></td>
                        <td><div class="budget-department"><span class="budget-icon"><i class="bi bi-person" aria-hidden="true"></i></span><div>{{ $log->user?->name ?? ($log->user_id ? 'Pengguna tidak tersedia' : 'Sistem / tamu') }}@if($log->user_id)<div class="budget-help">ID #{{ $log->user_id }}</div>@endif</div></div></td>
                        <td><span class="budget-badge {{ $actionColor }}"><i class="bi {{ $actionIcon }}" aria-hidden="true"></i>{{ $actionLabel }}</span></td>
                        <td><strong style="font-size:12px">{{ $subjectLabel ?: ($routeLabel ?: 'Autentikasi') }}</strong>@if($log->subject_id)<div class="budget-help">Data #{{ $log->subject_id }}</div>@endif @if($subjectLabel && $routeLabel)<div class="budget-help">{{ $routeLabel }}</div>@endif</td>
                        <td>
                            @if($log->method)<span class="budget-badge {{ $log->response_status >= 400 ? 'red' : 'gray' }}">{{ $log->method }} · {{ $log->response_status }}</span>@endif
                            @if($log->before || $log->after)
                                <details class="budget-details"><summary>Lihat perubahan</summary>
                                    <div class="budget-details-panel"><div class="budget-change">
                                        @foreach(['before' => 'Sebelum', 'after' => 'Sesudah'] as $field => $heading)
                                            <div><strong>{{ $heading }}</strong><dl style="margin:0">@forelse($log->{$field} ?? [] as $key => $value)<dt>{{ \Illuminate\Support\Str::headline($key) }}</dt><dd>{{ is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : (is_null($value) ? '—' : (is_bool($value) ? ($value ? 'Ya' : 'Tidak') : $value)) }}</dd>@empty<dd style="margin-top:10px;color:#9ca3af">Tidak ada data</dd>@endforelse</dl></div>
                                        @endforeach
                                    </div></div>
                                </details>
                            @elseif(!$log->method)<span class="budget-help">Tercatat otomatis</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5"><div class="budget-empty"><span class="budget-icon"><i class="bi bi-journal-text" aria-hidden="true"></i></span><h3>Belum ada aktivitas yang sesuai</h3><p>Coba ubah filter atau rentang tanggal untuk melihat aktivitas lainnya.</p></div></td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())<div class="budget-pagination">{{ $logs->links('pagination::bootstrap-5') }}</div>@endif
    </section>
    <div class="budget-note"><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Riwayat dicatat sejak fitur diaktifkan. Password, token, dan kredensial tidak dicatat. Log aktivitas tidak dapat diedit atau dihapus melalui halaman ini.</span></div>
</div>
@endsection
