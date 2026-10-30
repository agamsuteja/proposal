<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Tambahan Budget {{ $year }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #25314a; line-height: 1.5; }
        h1 { font-size: 20px; color: #52249a; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 8px; padding-bottom: 5px; border-bottom: 2px solid #e9e0f8; }
        h3 { font-size: 10px; margin: 10px 0 4px; }
        .muted { color: #64748b; }
        .note { background: #f6f3fb; padding: 9px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed; }
        th, td { padding: 6px; border: 1px solid #dce1e9; text-align: left; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        th { background: #f1edf8; font-weight: bold; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .label { width: 26%; color: #64748b; }
        .reason { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
@php
    $money = fn ($value) => 'Rp '.number_format(\App\Services\DepartmentBudgetService::cents($value) / 100, 2, ',', '.');
    $statuses = ['pending'=>'Menunggu persetujuan', 'disetujui'=>'Disetujui; menunggu transfer', 'ditolak'=>'Ditolak; perlu revisi', 'ditransfer'=>'Sudah ditransfer', 'dibatalkan'=>'Dibatalkan', 'dibalik'=>'Transfer dibalik'];
    $stages = ['departemen_head'=>'Kepala Departemen (MH)', 'group_head'=>'Kepala Grup (GH)', 'gembala'=>'Gembala'];
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : '—';
@endphp
<h1>ESC Proposal</h1>
<div>Laporan Pengajuan Tambahan Budget · Tahun {{ $year }}</div>
<div class="muted">Dicetak oleh {{ $printedBy }} · {{ $printedAt }}</div>
<div class="note">
    {{ $requests->count() }} pengajuan · {{ $requests->where('status', 'pending')->count() }} menunggu persetujuan ·
    {{ $requests->where('status', 'disetujui')->count() }} menunggu transfer · {{ $requests->where('status', 'ditransfer')->count() }} sudah ditransfer.<br>
    Data mencakup seluruh pengajuan pada tahun terpilih dalam lingkup akses pengguna, termasuk halaman daftar berikutnya.
    Transfer budget memindahkan alokasi antar-departemen dalam grup yang sama; bukan pencairan rekening.
</div>
@forelse($requests as $item)
    <h2>Pengajuan #{{ $item->id }} · {{ $statuses[$item->status] ?? $item->status }}</h2>
    <table>
        <tr><td class="label">Pemohon</td><td>{{ $item->user?->name ?? '—' }}</td></tr>
        <tr><td class="label">Departemen / Grup</td><td>{{ $item->departemen?->nama_departemen ?? '—' }} / {{ $item->departemen?->grup?->nama_grup ?? '—' }}</td></tr>
        <tr><td class="label">Tahun / Nominal</td><td>{{ $item->tahun }} / {{ $money($item->jumlah) }}</td></tr>
        <tr><td class="label">Tanggal pengajuan</td><td>{{ $date($item->created_at) }}</td></tr>
    </table>
    <h3>Alasan pengajuan</h3>
    <div class="reason">{{ $item->alasan }}</div>
    <h3>Riwayat persetujuan saat ini</h3>
    <table>
        <thead><tr><th>Tahap / Peninjau</th><th>Status / Waktu</th><th>Catatan</th></tr></thead>
        <tbody>
        @forelse($item->approvals as $approval)
            <tr><td>{{ $stages[$approval->tahap] ?? $approval->tahap }}<br>{{ $approval->approver?->name ?? '—' }}</td><td>{{ ['pending'=>'Menunggu', 'disetujui'=>'Disetujui', 'ditolak'=>'Ditolak'][$approval->status] ?? $approval->status }}<br>{{ $date($approval->decided_at) }}</td><td>{{ $approval->catatan ?: '—' }}</td></tr>
        @empty
            <tr><td colspan="3">Belum ada riwayat persetujuan.</td></tr>
        @endforelse
        </tbody>
    </table>
    <h3>Riwayat transfer alokasi</h3>
    @if($item->transfers->isNotEmpty())
        <table>
            <thead><tr><th>Departemen sumber</th><th>Nominal</th><th>Keuangan / Waktu</th></tr></thead>
            <tbody>@foreach($item->transfers as $transfer)
                <tr><td>{{ $transfer->sumber?->nama_departemen ?? '—' }}</td><td>{{ $money($transfer->jumlah) }}</td><td>{{ $transfer->user?->name ?? '—' }}<br>{{ $date($transfer->created_at) }}</td></tr>
            @endforeach</tbody>
        </table>
        @if($item->reversal)
            <div class="note">Transfer telah dibalik oleh {{ $item->reversal->user?->name ?? '—' }} pada {{ $date($item->reversal->created_at) }}.<br>Alasan: {{ $item->reversal->alasan }}</div>
        @endif
    @else
        <div class="muted">Belum ada transfer alokasi.</div>
    @endif
@empty
    <p>Belum ada pengajuan tambahan budget sesuai tahun dan lingkup akses Anda.</p>
@endforelse
</body>
</html>
