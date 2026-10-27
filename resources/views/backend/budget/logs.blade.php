@extends('backend.layouts.app')
@section('title', 'Log Aktivitas')
@section('content')
<h3>Log Aktivitas</h3><p>Riwayat akses, login/logout, dan perubahan data aplikasi sejak fitur diaktifkan. Log hanya dapat dibaca; password, token, dan isi request tidak disimpan.</p>
<form method="get" class="row mb-3"><div class="col"><label>Aksi<input class="form-control border px-2" name="action" value="{{ request('action') }}" placeholder="created / updated / request / auth.login"></label></div><div class="col"><label>ID pengguna<input class="form-control border px-2" type="number" name="user_id" value="{{ request('user_id') }}"></label></div><div class="col"><label>Dari<input class="form-control border px-2" type="date" name="tanggal_awal" value="{{ request('tanggal_awal') }}"></label></div><div class="col"><label>Sampai<input class="form-control border px-2" type="date" name="tanggal_akhir" value="{{ request('tanggal_akhir') }}"></label></div><div class="col"><button class="btn btn-primary">Filter</button></div></form>
@if($errors->any())<p>{{ $errors->first() }}</p>@endif
<div class="card p-3 table-responsive"><table class="table"><thead><tr><th>Waktu</th><th>Pelaku</th><th>Aksi</th><th>Objek / Halaman</th><th>Detail</th></tr></thead><tbody>
@forelse($logs as $log)<tr><td>{{ $log->created_at }}</td><td>{{ $log->user?->name ?? 'Sistem / pengguna tidak tersedia' }} @if($log->user_id)#{{ $log->user_id }}@endif</td><td>{{ $log->action }}</td><td>{{ $log->subject_type }} {{ $log->subject_id }} {{ $log->route }}</td><td>
@if($log->method){{ $log->method }} · HTTP {{ $log->response_status }}@endif
@if($log->before || $log->after)<details><summary>Perubahan data</summary><div>Sebelum</div><pre class="text-wrap">{{ json_encode($log->before, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre><div>Sesudah</div><pre class="text-wrap">{{ json_encode($log->after, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></details>@endif</td></tr>
@empty<tr><td colspan="5">Belum ada aktivitas sesuai filter.</td></tr>@endforelse
</tbody></table>{{ $logs->links() }}</div>
@endsection
