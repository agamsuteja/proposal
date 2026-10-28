@extends('backend.layouts.app')
@section('title','Status Notifikasi')
@section('content')
@include('backend.budget.partials.styles')
<div class="budget-page">
<header class="budget-topbar"><div><h1><i class="bi bi-bell"></i>Status Notifikasi</h1><p>Pantau pengiriman WhatsApp dan coba ulang pesan yang belum terkirim.</p></div></header>
@include('backend.budget.partials.alerts')
@if(session('error'))<div class="budget-note danger" role="alert">{{ session('error') }}</div>@endif
<section class="budget-card"><div class="budget-card-body"><form class="budget-toolbar"><div class="budget-field"><label for="status">Status pengiriman</label><select id="status" name="status" class="budget-input"><option value="">Semua status</option>@foreach(['pending'=>'Menunggu','processing'=>'Diproses','sent'=>'Diterima layanan','failed'=>'Gagal','expired'=>'Kedaluwarsa'] as $value=>$label)<option value="{{ $value }}" @selected(request('status')===$value)>{{ $label }}</option>@endforeach</select></div><button class="budget-button primary">Tampilkan</button></form></div></section>
<section class="budget-card"><div class="budget-table-wrap"><table class="budget-table"><thead><tr><th>Waktu</th><th>Pesan</th><th>Tujuan</th><th>Status</th><th>Tindakan</th></tr></thead><tbody>
@forelse($messages as $message)
<tr><td>{{ $message->created_at->format('d M Y H:i') }}</td><td>{{ $message->subject }}<div class="budget-help">Percobaan {{ $message->attempts }}/5</div></td><td>{{ $message->target ? str_repeat('•',max(0,strlen($message->target)-4)).substr($message->target,-4) : 'Nomor belum valid' }}</td><td><span class="budget-badge {{ $message->status==='sent'?'green':($message->status==='failed'?'red':'gray') }}">{{ ['pending'=>'Menunggu','processing'=>'Diproses','sent'=>'Diterima layanan','failed'=>'Gagal','expired'=>'Kedaluwarsa'][$message->status] ?? $message->status }}</span>@if($message->last_error)<p class="budget-help">{{ $message->last_error }}</p>@endif</td><td>
@if(in_array($message->status,['pending','failed']) && $message->attempts<5 && (!$message->next_attempt_at || $message->next_attempt_at->isPast()) && (!$message->expires_at || !$message->expires_at->isPast()))<form method="post" action="{{ route('admin.notifications.retry',$message->id) }}">@csrf<button class="budget-button secondary">Coba ulang</button></form>@else<span class="budget-help">{{ $message->next_attempt_at && $message->status==='failed' ? 'Berikutnya '.$message->next_attempt_at->format('H:i') : '—' }}</span>@endif
</td></tr>
@empty<tr><td colspan="5"><div class="budget-empty">Belum ada pesan sesuai filter.</div></td></tr>@endforelse
</tbody></table></div><div class="budget-pagination">{{ $messages->links('pagination::bootstrap-5') }}</div></section>
<div class="budget-note">Status diterima layanan berarti API menerima pesan untuk dikirim, bukan bukti pesan sudah dibaca penerima. Isi pesan dan OTP tidak ditampilkan pada halaman ini.</div>
</div>
@endsection
