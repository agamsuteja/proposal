@php
    $groupFunding = $fundings->get($groupId);
    $groupLimit = \App\Services\DepartmentBudgetService::cents($groupFunding?->plafon ?? 0);
    $selectedAccount = (int) old('rekening_id', $groupFunding?->rekening_id);
    $maximumFunding = $fundingLimits[$groupId][$selectedAccount] ?? '0.00';
    $groupAllocated = \App\Services\DepartmentBudgetService::cents($groupTotals[$groupId] ?? 0);
@endphp
<div class="budget-card-body" style="border-bottom:1px solid #eee7f6">
    @if($groupFunding)
        <div class="budget-toolbar" style="gap:1.5rem;align-items:flex-start">
            <div><div class="budget-help">Rekening sumber</div><strong>{{ $groupFunding->rekening->nama_rekening }}</strong></div>
            <div><div class="budget-help">Plafon dana grup</div><strong>Rp {{ number_format($groupLimit / 100, 2, ',', '.') }}</strong></div>
            <div><div class="budget-help">Belum dialokasikan ke departemen</div><strong class="{{ $groupLimit < $groupAllocated ? 'budget-negative' : 'budget-positive' }}">Rp {{ number_format(($groupLimit - $groupAllocated) / 100, 2, ',', '.') }}</strong></div>
        </div>
        <p class="budget-help mt-3 mb-0">Plafon adalah batas total dana yang boleh dibagikan ke seluruh departemen dalam grup ini. Alokasi tidak mengurangi saldo rekening. Pencairan dan pengembalian memakai rekening sumber grup; transfer antar-departemen tidak mengubah total grup.</p>
    @else
        <div class="budget-note">Pendanaan grup {{ $year }} belum ditetapkan. Keuangan perlu memilih rekening sumber dan plafon sebelum mengalokasikan dana atau mencairkan proposal. Data alokasi lama tetap ditampilkan untuk rekonsiliasi.</div>
    @endif
    @if($isFinance)
        <details class="budget-details mt-3"><summary>{{ $groupFunding ? 'Ubah pendanaan grup' : 'Tetapkan rekening & plafon grup' }}</summary>
            <form action="{{ route('admin.budget.funding') }}" method="post" class="mt-3" data-group-funding-form>@csrf
                <input type="hidden" name="grup_id" value="{{ $groupId }}"><input type="hidden" name="tahun" value="{{ $year }}">
                <div class="budget-two-col">
                    <div class="budget-field"><label for="fund-account-{{ $groupId }}">Rekening sumber</label><select id="fund-account-{{ $groupId }}" class="budget-input" name="rekening_id" required><option value="">Pilih rekening</option>@foreach($accounts as $account)<option value="{{ $account->id }}" data-maximum="{{ $fundingLimits[$groupId][$account->id] }}" @selected((int) old('rekening_id', $groupFunding?->rekening_id) === $account->id)>{{ $account->nama_rekening }} · Saldo Rp {{ number_format($account->saldo, 2, ',', '.') }} · Belum terikat Rp {{ number_format($account->free_cents / 100, 2, ',', '.') }}</option>@endforeach</select></div>
                    <div class="budget-field"><label for="fund-limit-{{ $groupId }}">Total dana / plafon grup (Rp)</label><input id="fund-limit-{{ $groupId }}" class="budget-input" type="number" name="plafon" min="0" step="0.01" max="{{ $maximumFunding }}" aria-describedby="fund-maximum-{{ $groupId }} fund-error-{{ $groupId }}" value="{{ old('plafon', $groupFunding?->plafon) }}" placeholder="Contoh: 20000000" required>
                        <p id="fund-maximum-{{ $groupId }}" class="budget-help" data-funding-maximum>{{ $selectedAccount ? 'Maksimal untuk grup ini: Rp '.number_format((float) $maximumFunding, 2, ',', '.') : 'Pilih rekening untuk melihat batas dana grup.' }}</p>
                        <p id="fund-error-{{ $groupId }}" class="budget-help budget-negative" data-funding-error role="alert" hidden></p>
                    </div>
                </div>
                <div class="budget-field"><label for="fund-reason-{{ $groupId }}">Alasan penetapan/perubahan</label><input id="fund-reason-{{ $groupId }}" class="budget-input" name="alasan" minlength="5" maxlength="1000" required></div>
                <p class="budget-help">Batas maksimal mengikuti rekening yang dipilih dan memperhitungkan dana terikat grup/tahun lain. Dana yang sudah ditetapkan untuk grup ini tetap boleh dipakai saat mengubah nominal; “belum terikat Rp0” bukan berarti dana grup ini hilang. Plafon grup ini menggantikan nilai sebelumnya, bukan ditambahkan lagi. Dana yang sudah dipakai tetap bagian dari plafon.</p>
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

@once
<script>
(() => {
    const format = new Intl.NumberFormat('id-ID', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.addEventListener('DOMContentLoaded', () => document.querySelectorAll('[data-group-funding-form]').forEach(form => {
        const account = form.querySelector('[name="rekening_id"]');
        const amount = form.querySelector('[name="plafon"]');
        const help = form.querySelector('[data-funding-maximum]');
        const error = form.querySelector('[data-funding-error]');
        const validate = () => {
            const maximum = account.selectedOptions[0]?.dataset.maximum;
            amount.max = maximum || '0.00';
            help.textContent = maximum ? 'Maksimal untuk grup ini: Rp ' + format.format(Number(maximum)) : 'Pilih rekening untuk melihat batas dana grup.';
            const tooMuch = maximum && amount.value !== '' && amount.valueAsNumber > Number(maximum);
            const message = tooMuch ? 'Jumlah melebihi batas rekening untuk grup ini. Maksimal Rp ' + format.format(Number(maximum)) + '.' : '';
            amount.setCustomValidity(message);
            amount.setAttribute('aria-invalid', message ? 'true' : 'false');
            error.textContent = message;
            error.hidden = !message;
        };
        account.addEventListener('change', validate);
        amount.addEventListener('input', validate);
        validate();
    }));
})();
</script>
@endonce
