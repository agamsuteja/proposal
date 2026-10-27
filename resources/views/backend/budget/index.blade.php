@extends('backend.layouts.app')
@section('title', 'Budget Departemen')
@section('content')
<h3>{{ request()->routeIs('admin.budget.requests') ? 'Pengajuan Tambahan Budget' : 'Budget Departemen' }}</h3>
@if(session('success'))<div class="alert alert-success text-white">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger text-white"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form method="get" class="d-flex gap-2 mb-4"><label for="tahun">Tahun</label><input class="form-control border px-2 w-auto" id="tahun" type="number" name="tahun" min="2000" max="2100" value="{{ $year }}"><button class="btn btn-primary mb-0">Tampilkan</button></form>
<p>Budget grup adalah jumlah budget departemen di dalamnya. Terpakai dihitung dari pencairan dikurangi pengembalian yang diterima, mengikuti tahun pengajuan proposal. Transfer hanya memindahkan alokasi budget, tanpa memindahkan uang antar-rekening.</p>
@if(!request()->routeIs('admin.budget.requests'))
@foreach($departments->groupBy('grup_id') as $groupId => $groupDepartments)
<div class="card p-3 mb-4">
<h5>{{ $groupDepartments->first()->grup?->nama_grup }} — Total budget Rp {{ number_format($groupTotals[$groupId] ?? 0, 2, ',', '.') }}</h5>
<div class="table-responsive"><table class="table"><thead><tr><th>Departemen</th><th>Budget</th><th>Terpakai</th><th>Tersedia</th>@if(auth()->user()->role === 'keuangan')<th>Atur budget</th>@endif</tr></thead><tbody>
@foreach($groupDepartments as $department)
<tr><td>{{ $department->nama_departemen }}</td><td>Rp {{ number_format($department->budget_cents / 100, 2, ',', '.') }}</td><td>Rp {{ number_format($department->used_cents / 100, 2, ',', '.') }}</td><td>Rp {{ number_format(($department->budget_cents - $department->used_cents) / 100, 2, ',', '.') }}</td>
@if(auth()->user()->role === 'keuangan')<td><form action="{{ route('admin.budget.save') }}" method="post" class="d-flex gap-2">@csrf @method('PUT')<input type="hidden" name="departemen_id" value="{{ $department->id }}"><input type="hidden" name="tahun" value="{{ $year }}"><input aria-label="Budget {{ $department->nama_departemen }}" class="form-control border px-2" type="number" name="jumlah" min="0" step="0.01" required value="{{ \App\Services\DepartmentBudgetService::amount($department->budget_cents) }}"><button class="btn btn-sm btn-primary mb-0">Simpan</button></form></td>@endif</tr>
@endforeach
</tbody></table></div></div>
@endforeach
<a class="btn btn-primary" href="{{ route('admin.budget.requests', ['tahun' => $year]) }}">Ajukan tambahan budget</a>
@else
@if($departments->isEmpty())<p>Belum ada departemen yang dapat Anda akses.</p>@else
<div class="card p-3 mb-4"><h5>Ajukan tambahan budget</h5><p>Pengajuan dapat dilakukan berulang kali tanpa batas jumlah pengajuan. Setelah disetujui Kepala Departemen → Kepala Grup → Gembala, keuangan memilih departemen sumber dalam grup yang sama.</p>
<form method="post" action="{{ route('admin.budget.store') }}">@csrf
<input type="hidden" name="tahun" value="{{ $year }}">
<label for="departemen_id">Departemen penerima</label><select class="form-select border px-2 mb-3" id="departemen_id" name="departemen_id" required>@foreach($departments as $department)<option value="{{ $department->id }}">{{ $department->grup?->nama_grup }} / {{ $department->nama_departemen }}</option>@endforeach</select>
<label for="jumlah">Nominal tambahan (Rp)</label><input class="form-control border px-2 mb-3" id="jumlah" type="number" name="jumlah" min="0.01" step="0.01" required value="{{ old('jumlah') }}">
<label for="alasan">Alasan kebutuhan tambahan</label><textarea class="form-control border px-2 mb-3" id="alasan" name="alasan" required maxlength="5000">{{ old('alasan') }}</textarea><button class="btn btn-primary">Ajukan tambahan</button></form></div>
@endif
<h4>Pengajuan tambahan budget — {{ $year }}</h4>
@forelse($requests as $item)
<div class="card p-3 mb-4"><h5>#{{ $item->id }} · {{ $item->departemen->nama_departemen }} · Rp {{ number_format($item->jumlah, 2, ',', '.') }}</h5>
<p>{{ $item->user?->name }} · {{ $item->created_at }} · <strong>{{ $item->status }}</strong></p><p class="text-break">{{ $item->alasan }}</p>
<ol>@foreach($item->approvals as $approval)<li>{{ $approval->tahap }} — {{ $approval->approver?->name }}: {{ $approval->status }} @if($approval->catatan) · {{ $approval->catatan }} @endif</li>@endforeach</ol>
@php($next = $item->approvals->firstWhere('status', 'pending'))
@if($item->status === 'pending' && $next?->approver_id === auth()->id())
<form method="post" action="{{ route('admin.budget.decide', $item->id) }}">@csrf<label for="catatan-{{ $item->id }}">Catatan approval</label><textarea class="form-control border px-2 mb-2" id="catatan-{{ $item->id }}" name="catatan" maxlength="1000"></textarea><button name="status" value="disetujui" class="btn btn-success">Setujui</button><button name="status" value="ditolak" class="btn btn-danger">Tolak</button></form>
@endif
@if($item->status === 'disetujui' && auth()->user()->role === 'keuangan')
<form method="post" action="{{ route('admin.budget.fund', $item->id) }}">@csrf<h6>Pilih sumber transfer</h6><p>Isi satu atau beberapa sumber. Biarkan nominal kosong untuk sumber yang tidak dipakai. Total harus sama dengan nominal yang disetujui.</p>
@foreach($departments->where('grup_id', $item->departemen->grup_id)->where('id', '!=', $item->departemen_id)->values() as $index => $source)
<label for="sumber-{{ $item->id }}-{{ $source->id }}">{{ $source->nama_departemen }} — tersedia Rp {{ number_format(($source->budget_cents - $source->used_cents)/100, 2, ',', '.') }}</label><input type="hidden" name="sumber[{{ $index }}][departemen_id]" value="{{ $source->id }}"><input class="form-control border px-2 mb-2" id="sumber-{{ $item->id }}-{{ $source->id }}" type="number" name="sumber[{{ $index }}][jumlah]" min="0.01" step="0.01">
@endforeach
<button class="btn btn-primary">Transfer budget</button></form>
@endif
@if($item->transfers->isNotEmpty())<h6>Riwayat transfer</h6><ul>@foreach($item->transfers as $transfer)<li>{{ $transfer->created_at }} · {{ $transfer->sumber?->nama_departemen }} → {{ $item->departemen->nama_departemen }} · Rp {{ number_format($transfer->jumlah, 2, ',', '.') }}</li>@endforeach</ul>@endif
</div>
@empty<p>Belum ada pengajuan tambahan budget untuk tahun ini.</p>@endforelse
{{ $requests->links() }}
@endif
@endsection
