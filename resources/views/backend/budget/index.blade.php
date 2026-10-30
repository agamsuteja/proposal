@extends('backend.layouts.app')
@section('title', request()->routeIs('admin.budget.requests') ? 'Tambahan Budget' : 'Budget Departemen')
@section('content')
@include('backend.budget.partials.styles')
@php
    $isRequestPage = request()->routeIs('admin.budget.requests');
    $isFinance = auth()->user()->role === 'keuangan';
    $allocated = $departments->sum('budget_cents');
    $used = $departments->sum('used_cents');
    $reserved = $departments->sum('reserved_cents');
    $statusLabels = ['pending' => ['Menunggu approval', 'amber'], 'disetujui' => ['Siap ditransfer', 'blue'], 'ditolak' => ['Ditolak', 'red'], 'ditransfer' => ['Sudah ditransfer', 'green'], 'dibatalkan'=>['Dibatalkan','gray'], 'dibalik'=>['Transfer dikembalikan','purple']];
    $stageLabels = ['departemen_head' => 'Kepala Departemen', 'group_head' => 'Kepala Grup', 'gembala' => 'Gembala'];
@endphp
<div class="budget-page">
    <header class="budget-topbar">
        <div>
            <h1><i class="bi {{ $isRequestPage ? 'bi-plus-circle' : 'bi-wallet2' }}" aria-hidden="true"></i>{{ $isRequestPage ? 'Tambahan Budget' : 'Budget Departemen' }}</h1>
            <p>{{ $isRequestPage ? 'Ajukan kebutuhan tambahan dan pantau persetujuan hingga transfer budget.' : 'Kelola alokasi tahunan dan pantau pemakaian dana setiap departemen.' }}</p>
        </div>
        <a class="budget-button light" href="{{ route($isRequestPage ? 'admin.budget.index' : 'admin.budget.requests', ['tahun' => $year]) }}">
            <i class="bi {{ $isRequestPage ? 'bi-arrow-left' : 'bi-plus-lg' }}" aria-hidden="true"></i>{{ $isRequestPage ? 'Lihat budget' : 'Ajukan tambahan budget' }}
        </a>
    </header>
    @include('backend.budget.partials.alerts')
    @if($periodStatus==='closed')<div class="budget-note">Periode {{ $year }} ditutup. Perubahan dana memerlukan pembukaan kembali oleh keuangan.</div>@endif

    @if(!$isRequestPage)
        <div class="budget-stats">
            <div class="budget-stat"><div class="budget-stat-label"><i class="bi bi-wallet2" aria-hidden="true"></i>Total alokasi</div><div class="budget-stat-value">Rp {{ number_format($allocated / 100, 2, ',', '.') }}</div><div class="budget-stat-sub">Departemen yang dapat Anda akses</div></div>
            <div class="budget-stat amber"><div class="budget-stat-label"><i class="bi bi-arrow-up-right" aria-hidden="true"></i>Dana terpakai</div><div class="budget-stat-value">Rp {{ number_format($used / 100, 2, ',', '.') }}</div><div class="budget-stat-sub">Pencairan dikurangi pengembalian diterima</div></div>
            <div class="budget-stat green"><div class="budget-stat-label"><i class="bi bi-check-circle" aria-hidden="true"></i>Saldo tersedia</div><div class="budget-stat-value">Rp {{ number_format(($allocated - $used - $reserved) / 100, 2, ',', '.') }}</div><div class="budget-stat-sub">Sisa alokasi pada tahun {{ $year }}</div></div>
            <div class="budget-stat"><div class="budget-stat-label">Dana dicadangkan</div><div class="budget-stat-value">Rp {{ number_format($reserved / 100, 2, ',', '.') }}</div><div class="budget-stat-sub">Untuk proposal disetujui yang belum dicairkan</div></div>
            <div class="budget-stat blue"><div class="budget-stat-label"><i class="bi bi-building" aria-hidden="true"></i>Departemen</div><div class="budget-stat-value">{{ $departments->count() }}</div><div class="budget-stat-sub">Dalam {{ $departments->pluck('grup_id')->unique()->count() }} grup yang dapat Anda akses</div></div>
        </div>
    @endif

    <div class="budget-card">
        <div class="budget-card-body">
            <form method="get" class="budget-toolbar">
                <div class="budget-field" style="flex:0 1 180px"><label for="tahun">Tahun anggaran</label><input class="budget-input" id="tahun" type="number" name="tahun" min="2000" max="2100" value="{{ $year }}" required></div>
                <button class="budget-button primary"><i class="bi bi-funnel" aria-hidden="true"></i>Tampilkan</button>
                <span class="budget-help">{{ $isRequestPage ? 'Menampilkan pengajuan untuk periode yang dipilih.' : 'Tetapkan rekening dan plafon grup, lalu bagi alokasi ke departemen. Alokasi tidak boleh melampaui plafon.' }}</span>
            </form>
        </div>
    </div>

    @include('backend.budget.partials.management')
    <div class="budget-actions mb-3"><a class="budget-button secondary" href="{{ route('admin.budget.export',['tahun'=>$year]) }}"><i class="bi bi-download"></i>Ekspor budget & realisasi</a><a class="budget-button secondary" href="{{ route('admin.budget.requests.pdf',['tahun'=>$year]) }}"><i class="bi bi-file-earmark-pdf" aria-hidden="true"></i>Ekspor tambahan budget PDF</a></div>
    @if(!$isRequestPage)
        @forelse($departments->groupBy('grup_id') as $groupId => $groupDepartments)
            <section class="budget-card" aria-labelledby="group-{{ $groupId }}">
                <div class="budget-card-header">
                    <div class="budget-department"><span class="budget-icon"><i class="bi bi-diagram-3" aria-hidden="true"></i></span><div><h2 id="group-{{ $groupId }}">{{ $groupDepartments->first()->grup?->nama_grup ?? 'Grup' }}</h2><p class="budget-subtitle">{{ $groupDepartments->count() }} departemen ditampilkan · Tahun {{ $year }}</p></div></div>
                    <span class="budget-badge purple">Total grup · Rp {{ number_format($groupTotals[$groupId] ?? 0, 2, ',', '.') }}</span>
                </div>
                @include('backend.budget.partials.funding')
                <div class="budget-table-wrap">
                    <table class="budget-table">
                        <thead><tr><th scope="col">Departemen</th><th scope="col">Alokasi budget</th><th scope="col">Terpakai</th><th scope="col">Dicadangkan</th><th scope="col">Tersedia</th>@if($isFinance)<th scope="col">Atur alokasi</th>@endif</tr></thead>
                        <tbody>
                        @foreach($groupDepartments as $department)
                            @php $remaining = $department->available_cents; @endphp
                            <tr>
                                <td><span class="budget-department">{{ $department->nama_departemen }}</span></td>
                                <td class="budget-money">Rp {{ number_format($department->budget_cents / 100, 2, ',', '.') }}</td>
                                <td><span class="budget-money">Rp {{ number_format($department->used_cents / 100, 2, ',', '.') }}</span><div class="budget-progress" role="img" aria-label="Pemakaian {{ $department->budget_cents > 0 ? round($department->used_cents / $department->budget_cents * 100) : 0 }} persen"><span style="width:{{ $department->budget_cents > 0 ? min(100, max(0, $department->used_cents / $department->budget_cents * 100)) : 0 }}%"></span></div></td>
                                <td class="budget-money">Rp {{ number_format($department->reserved_cents / 100, 2, ',', '.') }}</td>
                                <td class="budget-money {{ $remaining < 0 ? 'budget-negative' : 'budget-positive' }}">Rp {{ number_format($remaining / 100, 2, ',', '.') }}@if($department->budget_cents === 0)<div class="budget-help">Belum dialokasikan</div>@elseif($remaining <= 0)<div class="budget-help">Perlu tambahan budget</div>@endif</td>
                                @if($isFinance)
                                    <td><details class="budget-details"><summary>Ubah alokasi</summary><form action="{{ route('admin.budget.save') }}" method="post" class="budget-edit">@csrf @method('PUT')<input type="hidden" name="departemen_id" value="{{ $department->id }}"><input type="hidden" name="tahun" value="{{ $year }}"><input aria-label="Budget {{ $department->nama_departemen }}" class="budget-input" type="number" name="jumlah" min="0" step="0.01" max="{{ \App\Services\DepartmentBudgetService::amount(max(0, \App\Services\DepartmentBudgetService::cents($fundings->get($groupId)?->plafon ?? 0) - \App\Services\DepartmentBudgetService::cents($groupTotals[$groupId] ?? 0) + $department->budget_cents)) }}" required value="{{ \App\Services\DepartmentBudgetService::amount($department->budget_cents) }}"><input class="budget-input" name="alasan" aria-label="Alasan perubahan alokasi" placeholder="Alasan perubahan" minlength="5" maxlength="1000" required><button class="budget-button secondary" @disabled($periodStatus!=='open' || !$fundings->has($groupId)) aria-label="Simpan budget {{ $department->nama_departemen }}"><i class="bi bi-check2" aria-hidden="true"></i>Simpan</button></form></details></td>
                                @endif
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @empty
            <div class="budget-card budget-empty"><span class="budget-icon"><i class="bi bi-building" aria-hidden="true"></i></span><h3>Belum ada departemen</h3><p>Belum ada departemen yang dapat Anda akses. Hubungi admin untuk memeriksa penempatan departemen Anda.</p></div>
        @endforelse
        <div class="budget-note"><i class="bi bi-info-circle" aria-hidden="true"></i><span>Pemakaian dana mengikuti tahun pengajuan proposal. Transfer tambahan budget hanya memindahkan alokasi dalam grup yang sama, tanpa memindahkan saldo rekening bank.</span></div>
    @else
        @if($departments->isNotEmpty() && $periodStatus==='open')
            <div class="budget-two-col">
                <section class="budget-card">
                    <div class="budget-card-header"><h2><i class="bi bi-pencil-square me-2" aria-hidden="true"></i>Ajukan tambahan budget</h2><span class="budget-badge purple">Periode {{ $year }}</span></div>
                    <div class="budget-card-body">
                        <form method="post" action="{{ route('admin.budget.store') }}">@csrf
                            <input type="hidden" name="tahun" value="{{ $year }}">
                            <div class="budget-field"><label for="departemen_id">Departemen penerima</label><select class="budget-input" id="departemen_id" name="departemen_id" required>@foreach($departments as $department)<option value="{{ $department->id }}" @selected((string) old('departemen_id') === (string) $department->id)>{{ $department->grup?->nama_grup }} / {{ $department->nama_departemen }}</option>@endforeach</select></div>
                            <div class="budget-field"><label for="jumlah">Nominal tambahan (Rp)</label><input class="budget-input" id="jumlah" type="number" name="jumlah" min="0.01" step="0.01" required value="{{ old('jumlah') }}" placeholder="Contoh: 1000000"><p class="budget-help">Masukkan nominal tanpa pemisah ribuan.</p></div>
                            <div class="budget-field"><label for="alasan">Alasan kebutuhan tambahan</label><textarea class="budget-input" id="alasan" name="alasan" required maxlength="5000" rows="4" placeholder="Jelaskan kebutuhan dan alasan penambahan budget…">{{ old('alasan') }}</textarea></div>
                            <button class="budget-button primary"><i class="bi bi-send" aria-hidden="true"></i>Kirim pengajuan</button>
                        </form>
                    </div>
                </section>
                <aside class="budget-card" style="align-self:start">
                    <div class="budget-card-header"><h2>Alur persetujuan</h2></div>
                    <div class="budget-card-body">
                        <ol class="budget-guide">
                            <li><span class="budget-step-number">1</span><div><strong>Kepala Departemen</strong><p>Meninjau kebutuhan tambahan departemen.</p></div></li>
                            <li><span class="budget-step-number">2</span><div><strong>Kepala Grup</strong><p>Memeriksa kebutuhan dalam lingkup grup.</p></div></li>
                            <li><span class="budget-step-number">3</span><div><strong>Gembala</strong><p>Memberikan persetujuan akhir pengajuan.</p></div></li>
                            <li><span class="budget-step-number"><i class="bi bi-arrow-left-right" aria-hidden="true"></i></span><div><strong>Transfer oleh keuangan</strong><p>Memilih sumber dari departemen lain dalam grup yang sama sesuai saldo tersedia.</p></div></li>
                        </ol>
                        <hr class="budget-divider"><p class="budget-help">Pengajuan dapat dilakukan berulang kali tanpa batas jumlah pengajuan.</p>
                    </div>
                </aside>
            </div>
        @endif
        <div class="d-flex justify-content-between align-items-center gap-2 mb-3"><h2 class="budget-section-title mb-0">Riwayat pengajuan</h2><span class="budget-badge gray">{{ $requests->total() }} pengajuan · {{ $year }}</span></div>
        @forelse($requests as $item)
            @php
                [$statusLabel, $statusColor] = $statusLabels[$item->status] ?? [$item->status, 'gray'];
                $next = $item->approvals->firstWhere('status', 'pending');
            @endphp
            <article class="budget-card">
                <div class="budget-card-header">
                    <div><div class="budget-request-title"><h3>{{ $item->departemen->nama_departemen }}</h3><span class="budget-badge gray">#{{ $item->id }}</span></div><p class="budget-subtitle">{{ $item->user?->name }} · {{ $item->created_at?->format('d M Y, H:i') }} · {{ $item->departemen->grup?->nama_grup }}</p></div>
                    <span class="budget-badge {{ $statusColor }}">{{ $statusLabel }}</span>
                </div>
                <div class="budget-card-body">
                    <div class="budget-request-amount">Rp {{ number_format($item->jumlah, 2, ',', '.') }}</div>
                    <p class="budget-reason">{{ $item->alasan }}</p>
                    <ol class="budget-approval-track" aria-label="Tahapan persetujuan">
                        @foreach($item->approvals as $approval)
                            <li class="budget-approval-step {{ $approval->status === 'disetujui' ? 'approved' : ($approval->status === 'ditolak' ? 'rejected' : ($next?->id === $approval->id && $item->status === 'pending' ? 'current' : '')) }}">
                                <span class="budget-badge {{ $approval->status === 'disetujui' ? 'green' : ($approval->status === 'ditolak' ? 'red' : 'gray') }}">{{ $approval->status === 'disetujui' ? 'Disetujui' : ($approval->status === 'ditolak' ? 'Ditolak' : 'Menunggu') }}</span>
                                <strong>{{ $stageLabels[$approval->tahap] ?? $approval->tahap }}</strong><p>{{ $approval->approver?->name ?? 'Belum ditetapkan' }}</p>
                                @if($approval->catatan)<p style="margin-top:8px">{{ $approval->catatan }}</p>@endif
                            </li>
                        @endforeach
                    </ol>
                    @if($periodStatus==='open' && $item->status === 'pending' && $next?->approver_id === auth()->id())
                        <hr class="budget-divider"><h4 class="budget-section-title">Keputusan Anda</h4>
                        <form method="post" action="{{ route('admin.budget.decide', $item->id) }}">@csrf<div class="budget-field"><label for="catatan-{{ $item->id }}">Catatan persetujuan</label><textarea class="budget-input" id="catatan-{{ $item->id }}" name="catatan" maxlength="1000" rows="2" placeholder="Tambahkan catatan bila diperlukan"></textarea></div><div class="budget-actions"><button name="status" value="disetujui" class="budget-button success"><i class="bi bi-check-lg" aria-hidden="true"></i>Setujui</button><button name="status" value="ditolak" class="budget-button danger"><i class="bi bi-x-lg" aria-hidden="true"></i>Tolak</button></div></form>
                    @endif
                    @if($periodStatus==='open' && $item->status === 'disetujui' && $isFinance)
                        <hr class="budget-divider"><h4 class="budget-section-title">Pilih sumber transfer</h4><p class="budget-help mb-3">Gunakan satu atau beberapa sumber dalam grup yang sama. Kosongkan sumber yang tidak dipakai. Total transfer harus Rp {{ number_format($item->jumlah, 2, ',', '.') }}.</p>
                        <form method="post" action="{{ route('admin.budget.fund', $item->id) }}">@csrf
                            @foreach($departments->where('grup_id', $item->departemen->grup_id)->where('id', '!=', $item->departemen_id)->values() as $index => $source)
                                <div class="budget-field"><label for="sumber-{{ $item->id }}-{{ $source->id }}">{{ $source->nama_departemen }} <span class="budget-help">· Tersedia Rp {{ number_format($source->available_cents / 100, 2, ',', '.') }}</span></label><input type="hidden" name="sumber[{{ $index }}][departemen_id]" value="{{ $source->id }}"><input class="budget-input" id="sumber-{{ $item->id }}-{{ $source->id }}" type="number" name="sumber[{{ $index }}][jumlah]" min="0.01" step="0.01" placeholder="Nominal transfer (Rp)"></div>
                            @endforeach
                            <button class="budget-button primary"><i class="bi bi-arrow-left-right" aria-hidden="true"></i>Transfer budget</button>
                        </form>
                    @endif
                    @include('backend.budget.partials.request-actions')
                    @if($item->transfers->isNotEmpty())
                        <details class="budget-details"><summary>Riwayat transfer · {{ $item->transfers->count() }} sumber</summary><div class="budget-table-wrap"><table class="budget-table"><thead><tr><th scope="col">Tanggal</th><th scope="col">Sumber</th><th scope="col">Nominal</th></tr></thead><tbody>@foreach($item->transfers as $transfer)<tr><td>{{ $transfer->created_at?->format('d M Y, H:i') }}</td><td>{{ $transfer->sumber?->nama_departemen }}</td><td class="budget-money">Rp {{ number_format($transfer->jumlah, 2, ',', '.') }}</td></tr>@endforeach</tbody></table></div></details>
                    @endif
                </div>
            </article>
        @empty
            <div class="budget-card budget-empty"><span class="budget-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span><h3>Belum ada pengajuan tambahan</h3><p>Pengajuan tambahan budget untuk tahun {{ $year }} akan muncul di sini.</p></div>
        @endforelse
        @if($requests->hasPages())<div class="budget-card"><div class="budget-pagination">{{ $requests->links('pagination::bootstrap-5') }}</div></div>@endif
    @endif
</div>
@endsection
