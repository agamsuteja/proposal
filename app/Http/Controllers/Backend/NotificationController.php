<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\NotificationMessage;
use App\Services\NotificationService;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['status' => 'nullable|in:pending,processing,sent,failed,expired']);

        return view('backend.budget.notifications', ['messages' => NotificationMessage::when($request->filled('status'), fn ($q) => $q->where('status', $request->status))->latest()->paginate(20)->withQueryString()]);
    }

    public function retry(Request $request, int $id, NotificationService $service)
    {
        $row = NotificationMessage::findOrFail($id);
        abort_unless(in_array($row->status, ['pending', 'failed']) && $row->attempts < 5 && (! $row->next_attempt_at || $row->next_attempt_at->isPast()), 409, 'Pesan sudah terkirim, kedaluwarsa, atau belum waktunya mencoba ulang.');
        $sent = $service->deliver($id);

        return back()->with($sent ? 'success' : 'error', $sent ? 'Pesan diterima layanan WhatsApp.' : 'Pesan belum berhasil dikirim; status kegagalan tersimpan.');
    }
}
