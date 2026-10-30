<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Pencairan Proposal #{{ $proposal->id }}</title>
    <style>
        @page { margin: 28px 32px; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10px; color: #25314a; line-height: 1.5; }
        h1 { font-size: 20px; color: #52249a; margin: 0 0 4px; }
        h2 { font-size: 13px; margin: 18px 0 7px; padding-bottom: 5px; border-bottom: 2px solid #e9e0f8; page-break-after: avoid; }
        .muted { color: #64748b; }
        .note { background: #f6f3fb; padding: 9px; margin: 12px 0; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; table-layout: fixed; }
        th, td { padding: 6px; border: 1px solid #dce1e9; text-align: left; vertical-align: top; overflow-wrap: break-word; word-wrap: break-word; }
        th { background: #f1edf8; }
        thead { display: table-header-group; }
        tr { page-break-inside: avoid; }
        .label { width: 35%; color: #64748b; }
    </style>
</head>
<body>
@php
    $money = fn ($cents) => 'Rp '.number_format($cents / 100, 2, ',', '.');
    $cents = fn ($value) => \App\Services\DepartmentBudgetService::cents($value);
    $date = fn ($value) => $value ? \Illuminate\Support\Carbon::parse($value)->timezone('Asia/Jakarta')->format('d/m/Y H:i').' WIB' : '—';
    $status = ['disetujui_senior_pastor'=>'Disetujui gembala; belum dicairkan', 'dicairkan_sebagian'=>'Dicairkan sebagian', 'dicairkan'=>'Dicairkan penuh'];
    $stage = ['departemen_head'=>'Kepala Departemen (MH)', 'group_head'=>'Kepala Grup (GH)', 'gembala'=>'Gembala'];
@endphp
<h1>ESC Proposal</h1>
<div>Laporan Pencairan Proposal #{{ $proposal->id }}</div>
<div class="muted">Dicetak oleh {{ $printedBy }} · {{ $printedAt }}</div>
<h2>{{ $proposal->judul }}</h2>
<table>
    <tr><td class="label">Pemohon</td><td>{{ $proposal->user?->name ?? '—' }}</td></tr>
    <tr><td class="label">Departemen / Grup</td><td>{{ $proposal->departemen->nama_departemen }} / {{ $proposal->departemen->grup?->nama_grup ?? '—' }}</td></tr>
    <tr><td class="label">Tahun budget / Tanggal pengajuan</td><td>{{ $year }} / {{ \Illuminate\Support\Carbon::parse($proposal->tanggal_pengajuan)->format('d/m/Y') }}</td></tr>
    <tr><td class="label">Status pencairan</td><td>{{ $status[$proposal->status] ?? ucfirst(str_replace('_', ' ', $proposal->status)) }}</td></tr>
    <tr><td class="label">Rekening sumber grup</td><td>{{ $sourceAccount?->bank }} {{ $sourceAccount?->nama_rekening ?? 'Belum ditetapkan' }}</td></tr>
</table>
<div style="word-wrap:break-word">Tujuan: {{ $proposal->tujuan ?: '—' }}</div>
<h2>Ringkasan pencairan</h2>
<table>
    <tr><td class="label">Total anggaran proposal</td><td>{{ $money($cents($proposal->total_anggaran)) }}</td></tr>
    <tr><td class="label">Total sudah dicairkan (bruto)</td><td>{{ $money($paidCents) }}</td></tr>
    <tr><td class="label">Belum dicairkan</td><td>{{ $money($remainingCents) }}</td></tr>
    <tr><td class="label">Jumlah tahap pencairan</td><td>{{ $proposal->pencairan->count() }}</td></tr>
</table>
<div class="note">Rekening mengikuti grup dan tahun proposal. Total seluruh tahap dibatasi anggaran yang disetujui. Pengembalian dana tidak menambah kembali batas pencairan proposal.</div>
<h2>Riwayat pencairan per tahap</h2>
@forelse($proposal->pencairan->sortBy('id') as $payment)
    <table>
        <thead><tr><th colspan="2">Tahap {{ $loop->iteration }} · {{ $money($cents($payment->jumlah_dana)) }}</th></tr></thead>
        <tbody>
            <tr><td class="label">Waktu pencatatan</td><td>{{ $date($payment->created_at) }}</td></tr>
            <tr><td class="label">Rekening</td><td>{{ $payment->rekening?->bank }} {{ $payment->rekening?->nama_rekening ?? '—' }}</td></tr>
            <tr><td class="label">Metode</td><td>{{ $payment->metode === 'transfer' ? 'Transfer bank' : 'Tunai' }}</td></tr>
            <tr><td class="label">Catatan</td><td>{{ $payment->catatan ?: '—' }}</td></tr>
            <tr><td class="label">Bukti transfer</td><td>{{ $payment->bukti_transfer ? 'Tersimpan; dapat dilihat pada riwayat pencairan di aplikasi.' : 'Tidak ada / pencairan tunai.' }}</td></tr>
        </tbody>
    </table>
@empty
    <p>Belum ada pencairan dana.</p>
@endforelse
<h2>Persetujuan proposal</h2>
<table>
    <thead><tr><th>Tahap / Peninjau</th><th>Status / Waktu</th><th>Catatan</th></tr></thead>
    <tbody>@forelse($proposal->approval_flows as $flow)
        <tr><td>{{ $stage[$flow->tahap] ?? $flow->tahap }}<br>{{ $flow->approver?->name ?? '—' }}</td><td>{{ ['pending'=>'Menunggu', 'disetujui'=>'Disetujui', 'ditolak'=>'Ditolak'][$flow->status] ?? $flow->status }}<br>{{ $date($flow->tanggal_approve) }}</td><td>{{ $flow->catatan ?: '—' }}</td></tr>
    @empty
        <tr><td colspan="3">Belum ada riwayat persetujuan.</td></tr>
    @endforelse</tbody>
</table>
<h2>Pertanggungjawaban dana yang sudah dicairkan</h2>
<table>
    <tr><td class="label">Total nota penggunaan</td><td>{{ $money($balance['receipts']) }}</td></tr>
    <tr><td class="label">Pengembalian diterima keuangan</td><td>{{ $money($balance['refunds']) }}</td></tr>
    <tr><td class="label">Sisa setelah nota dan pengembalian diterima</td><td>{{ $money($balance['remaining']) }}</td></tr>
</table>
<div class="muted">Pengembalian yang masih menunggu verifikasi belum dihitung sebagai dana diterima. Sisa pertanggungjawaban berbeda dari anggaran yang belum dicairkan. Selesai pencairan belum berarti nota dan pengembalian sudah selesai.</div>
</body>
</html>
