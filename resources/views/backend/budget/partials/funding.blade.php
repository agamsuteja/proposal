@php
    $groupFunding = $fundings->get($groupId);
    $groupLimit = \App\Services\DepartmentBudgetService::cents($groupFunding?->plafon ?? 0);
    $groupAllocated = \App\Services\DepartmentBudgetService::cents($groupTotals[$groupId] ?? 0);
@endphp
<div class="budget-card-body" style="border-bottom:1px solid #eee7f6">
    @if($groupFunding)
        <div class="budget-toolbar" style="gap:1.5rem;align-items:flex-start">
            <div><div class="budget-help">Rekening sumber</div><strong>{{ $groupFunding->rekening->nama_rekening }}</strong></div>
            <div><div class="budget-help">Plafon dana grup</div><strong>Rp {{ number_format($groupLimit / 100, 2, ',', '.') }}</strong></div>
            <div><div class="budget-help">Belum dialokasikan ke departemen</div><strong class="{{ $groupLimit < $groupAllocated ? 'budget-negative' : 'budget-positive' }}">Rp {{ number_format(($groupLimit - $groupAllocated) / 100, 2, ',', '.') }}</strong></div>
        </div>
        <p class="budget-help mt-3 mb-0">Alokasi tidak mengurangi saldo rekening. Pencairan dan pengembalian memakai rekening sumber grup; transfer antar-departemen tidak mengubah total grup.</p>
    @else
        <div class="budget-note">Pendanaan grup {{ $year }} belum ditetapkan. Keuangan perlu memilih rekening sumber dan plafon sebelum mengalokasikan dana atau mencairkan proposal. Data alokasi lama tetap ditampilkan untuk rekonsiliasi.</div>
    @endif
    @if($isFinance)
        <details class="budget-details mt-3"><summary>{{ $groupFunding ? 'Ubah pendanaan grup' : 'Tetapkan rekening & plafon grup' }}</summary>
            <form action="{{ route('admin.budget.funding') }}" method="post" class="mt-3">@csrf
                <input type="hidden" name="grup_id" value="{{ $groupId }}"><input type="hidden" name="tahun" value="{{ $year }}">
                <div class="budget-two-col">
                    <div class="budget-field"><label for="fund-account-{{ $groupId }}">Rekening sumber</label><select id="fund-account-{{ $groupId }}" class="budget-input" name="rekening_id" required><option value="">Pilih rekening</option>@foreach($accounts as $account)<option value="{{ $account->id }}" @selected((int) old('rekening_id', $groupFunding?->rekening_id) === $account->id)>{{ $account->nama_rekening }} · Saldo Rp {{ number_format($account->saldo, 2, ',', '.') }} · Bebas Rp {{ number_format($account->free_cents / 100, 2, ',', '.') }}</option>@endforeach</select></div>
                    <div class="budget-field"><label for="fund-limit-{{ $groupId }}">Plafon grup (Rp)</label><input id="fund-limit-{{ $groupId }}" class="budget-input" type="number" name="plafon" min="0" step="0.01" max="9999999999999.99" value="{{ old('plafon', $groupFunding?->plafon) }}" placeholder="Contoh: 20000000" required></div>
                </div>
                <div class="budget-field"><label for="fund-reason-{{ $groupId }}">Alasan penetapan/perubahan</label><input id="fund-reason-{{ $groupId }}" class="budget-input" name="alasan" minlength="5" maxlength="1000" required></div>
                <p class="budget-help">Saldo bebas sudah memperhitungkan dana terikat untuk seluruh grup dan tahun terbuka. Plafon grup ini menggantikan nilai sebelumnya, bukan ditambahkan lagi. Dana yang sudah dipakai tetap bagian dari plafon.</p>
                <button class="budget-button primary" @disabled($periodStatus !== 'open' || $accounts->isEmpty())>Simpan pendanaan grup</button>
            </form>
        </details>
        @if($groupFunding)
            <details class="budget-details mt-3"><summary>Bagi rata ke seluruh departemen dalam grup</summary>
                <form action="{{ route('admin.budget.distribute') }}" method="post" class="mt-3" onsubmit="return confirm('Ganti alokasi seluruh departemen grup ini dengan pembagian rata? Riwayat dan pemakaian tetap disimpan.')">@csrf
                    <input type="hidden" name="grup_id" value="{{ $groupId }}"><input type="hidden" name="tahun" value="{{ $year }}">
                    <p class="budget-help">Membagi seluruh plafon Rp {{ number_format($groupLimit / 100, 2, ',', '.') }}. Selisih sen dibagikan menurut urutan ID departemen. Ditolak jika jatah baru kurang dari pemakaian dan cadangan departemen.</p>
                    <div class="budget-field"><label for="split-reason-{{ $groupId }}">Alasan pembagian rata</label><input id="split-reason-{{ $groupId }}" name="alasan" class="budget-input" required minlength="5" maxlength="1000"></div>
                    <button class="budget-button secondary" @disabled($periodStatus !== 'open')>Bagi rata sekarang</button>
                </form>
            </details>
        @endif
    @endif
</div>
