@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   PROPOSAL CREATE — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.pc-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pc-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pc-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pc-back-btn { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:9px; padding:7px 16px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:all .15s }
.pc-back-btn:hover { background:rgba(255,255,255,.25); color:#fff; text-decoration:none }

/* ── Error Alert ── */
.pc-error-alert { background:#fef2f2; border:0.5px solid #fecaca; border-left:3px solid #ef4444; border-radius:10px; padding:.875rem 1.1rem; margin-bottom:1rem }
.pc-error-alert strong { font-size:12.5px; color:#991b1b; display:block; margin-bottom:.4rem }
.pc-error-alert ul { margin:0; padding-left:1.25rem }
.pc-error-alert li { font-size:12px; color:#b91c1c; margin-bottom:.2rem; line-height:1.5 }

/* ── Form Card ── */
.pc-form-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); margin-bottom:1rem }

/* ── Section ── */
.pc-section { padding:1.25rem 1.5rem; border-bottom:0.5px solid #f3f4f6 }
.pc-section:last-child { border-bottom:none }
.pc-section-title { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:#6b7280; margin-bottom:1rem; display:flex; align-items:center; gap:6px }
.pc-section-title i { color:#7c3aed; font-size:11px }
.pc-section-title::after { content:''; flex:1; height:0.5px; background:#e5e7eb }

/* ── Fields ── */
.pc-field-grid { display:grid; gap:.875rem }
.pc-field-grid.cols-2 { grid-template-columns:1fr 1fr }
.pc-field-grid.cols-3 { grid-template-columns:1fr 1fr 1fr }
.pc-field-grid.cols-2-1 { grid-template-columns:2fr 1fr 1fr }
@media(max-width:768px){
    .pc-field-grid.cols-2,
    .pc-field-grid.cols-3,
    .pc-field-grid.cols-2-1 { grid-template-columns:1fr }
}

.pc-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:5px }
.pc-field .req { color:#ef4444; margin-left:2px }
.pc-field input,
.pc-field select,
.pc-field textarea { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:8px; padding:8px 11px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:13px; outline:none; transition:border-color .15s,box-shadow .15s; appearance:none; -webkit-appearance:none }
.pc-field input[readonly] { background:#f3f4f6; color:#9ca3af; cursor:not-allowed }
.pc-field select { background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .75rem center; background-color:#f9fafb; padding-right:2.25rem }
.pc-field input:focus,
.pc-field select:focus,
.pc-field textarea:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.pc-field input::placeholder,
.pc-field textarea::placeholder { color:#9ca3af }
.pc-field input[type="date"]::-webkit-calendar-picker-indicator { opacity:.5 }
.pc-field small { font-size:10.5px; color:#9ca3af; margin-top:3px; display:block }
.pc-field textarea { resize:vertical; min-height:80px }

/* ── Jenis badge ── */
.pc-jenis-row { display:flex; align-items:center; gap:.6rem; margin-top:.5rem }
.pc-jenis-badge { font-size:10.5px; font-weight:700; border-radius:20px; padding:3px 10px; display:inline-block }
.jenis-barang      { background:#dbeafe; color:#1e40af }
.jenis-acara       { background:#d1fae5; color:#065f46 }
.jenis-maintenance { background:#ffedd5; color:#c2410c }
.jenis-empty       { background:#e5e7eb; color:#6b7280 }

/* ── Item rows ── */
.pc-item-row { background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:10px; padding:1rem 1.1rem; margin-bottom:.75rem; transition:border-color .15s }
.pc-item-row:hover { border-color:#c4b5fd }
.pc-item-num { font-size:10px; font-weight:700; background:#ede9fe; color:#5b21b6; border-radius:20px; padding:2px 8px; display:inline-block; margin-bottom:.6rem }
.pc-item-grid { display:grid; gap:.75rem; align-items:start }
.pc-item-grid.barang { grid-template-columns:2fr 1.5fr 0.8fr 2fr 1.5fr auto }
.pc-item-grid.maintenance { grid-template-columns:2fr 2fr 1.5fr 1.5fr auto }
@media(max-width:768px){
    .pc-item-grid.barang,
    .pc-item-grid.maintenance { grid-template-columns:1fr }
}

/* ── Remove button ── */
.pc-btn-remove { width:32px; height:32px; border-radius:7px; background:#fee2e2; border:0.5px solid #fecaca; color:#b91c1c; display:flex; align-items:center; justify-content:center; cursor:pointer; font-size:13px; transition:all .15s; flex-shrink:0; align-self:flex-end }
.pc-btn-remove:hover { background:#fecaca; border-color:#f87171}

/* ── Add item button ── */
.pc-btn-add { display:inline-flex; align-items:center; gap:5px; padding:7px 14px; border-radius:8px; font-family:'Nunito',system-ui,sans-serif; font-size:12.5px; font-weight:700; cursor:pointer; border:0.5px solid #93c5fd; background:#dbeafe; color:#1e40af; transition:all .15s }
.pc-btn-add:hover { background:#bfdbfe; border-color:#60a5fa }

/* ── Total summary ── */
.pc-total-box { background:linear-gradient(135deg,#7c3aed,#4c1d95); border-radius:12px; padding:1.1rem 1.5rem; display:flex; align-items:center; justify-content:space-between; margin-top:1rem; flex-wrap:wrap; gap:.5rem }
.pc-total-label { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.08em; color:rgba(255,255,255,.75) }
.pc-total-amount { font-family:'Nunito',system-ui,sans-serif; font-size:1.4rem; font-weight:800; color:#fff; letter-spacing:-0.02em }

/* ── Action buttons ── */
.pc-action-section { background:#f9fafb; padding:1.1rem 1.5rem; border-top:0.5px solid #e5e7eb; border-radius:0 0 14px 14px; display:flex; gap:.75rem; flex-wrap:wrap }
.pc-btn-submit { display:inline-flex; align-items:center; gap:6px; padding:9px 22px; border-radius:9px; font-family:'Nunito',system-ui,sans-serif; font-size:13px; font-weight:700; cursor:pointer; border:none; background:linear-gradient(135deg,#7c3aed,#4c1d95); color:#fff; transition:opacity .15s,transform .1s }
.pc-btn-submit:hover { opacity:.9; transform:translateY(-1px) }
.pc-btn-cancel { display:inline-flex; align-items:center; gap:6px; padding:9px 20px; border-radius:9px; font-family:'Nunito',system-ui,sans-serif; font-size:13px; font-weight:700; cursor:pointer; border:0.5px solid #e5e7eb; background:#fff; color:#374151; text-decoration:none; transition:all .15s }
.pc-btn-cancel:hover { background:#f3f4f6; border-color:#cbd5e1; color:#374151 }
</style>

<div class="pc-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pc-topbar">
        <h1><i class="fas fa-file-plus"></i> Buat Proposal Baru</h1>
        <a href="{{ route('admin.proposal.index') }}" class="pc-back-btn">
            <i class="fas fa-arrow-left"></i> Kembali ke Daftar
        </a>
    </div>

    {{-- ═══ ERROR ALERT ═══ --}}
    @if($errors->any())
    <div class="pc-error-alert" role="alert" tabindex="-1">
        <strong>Mohon perbaiki kesalahan berikut:</strong>
        <ul>
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    {{-- ═══ FORM CARD ═══ --}}
    <div class="pc-form-card">
        <form action="{{ route('admin.proposal.store') }}" method="POST" enctype="multipart/form-data" id="proposalForm">
            @csrf

            {{-- ── JENIS PROPOSAL ── --}}
            <div class="pc-section">
                <div class="pc-section-title">
                    <i class="fas fa-th-large"></i> Jenis Proposal
                </div>
                <div class="pc-field-grid cols-2">
                    <div class="pc-field">
                        <label>Pilih Jenis Proposal <span class="req">*</span></label>
                        <select name="jenis" id="jenisProposal" required>
                            <option value="">— Pilih Jenis Proposal —</option>
                            <option value="barang" @selected(old('jenis',request('jenis')) === 'barang')>Pengadaan Barang</option>
                            <option value="acara" @selected(old('jenis',request('jenis')) === 'acara')>Kegiatan / Acara</option>
                            <option value="maintenance" @selected(old('jenis',request('jenis')) === 'maintenance')>Maintenance Alat</option>
                        </select>
                        <div class="pc-jenis-row">
                            <span style="font-size:11px;color:#6b7280;font-weight:600">Terpilih:</span>
                            <span id="jenisBadge" class="pc-jenis-badge jenis-empty">Belum Dipilih</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── INFORMASI UMUM ── --}}
            <div class="pc-section">
                <div class="pc-section-title">
                    <i class="fas fa-info-circle"></i> Informasi Umum
                </div>
                <div class="pc-field-grid cols-2" style="margin-bottom:.875rem">
                    <div class="pc-field">
                        <label>Judul Proposal <span class="req">*</span></label>
                        <input type="text" name="judul" placeholder="Contoh: Pengadaan Laptop untuk Staff" required value="{{ old('judul') }}">
                    </div>
                    <div class="pc-field">
                        <label>Departemen <span class="req">*</span></label>
                        @php $user = Auth::user(); @endphp
                        @if($user->role === 'karyawan')
                            <input type="hidden" name="departemen_id" value="{{ $departemen->id }}">
                            <input type="text" value="{{ $departemen->nama_departemen }}" readonly>
                        @else
                            <select name="departemen_id" required>
                                <option value="">— Pilih Departemen —</option>
                                @foreach($departemens as $d)
                                    <option value="{{ $d->id }}" {{ old('departemen_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_departemen }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>
                </div>
                <div class="pc-field-grid cols-2">
                    <div class="pc-field">
                        <label>Tanggal Pengajuan <span class="req">*</span></label>
                        <input type="date" name="tanggal_pengajuan" required value="{{ old('tanggal_pengajuan', date('Y-m-d')) }}">
                    </div>
                    <div class="pc-field">
                        <label>Lampiran <small style="text-transform:none;letter-spacing:0;font-weight:400">(Opsional)</small></label>
                        <input type="file" name="lampiran" accept=".pdf,.jpg,.jpeg,.png">
                        <small>Format: PDF, JPG, PNG (maks. 2MB)</small>
                    </div>
                    <div class="pc-field" style="grid-column:1/-1">
                        <label>Tujuan / Deskripsi Proposal</label>
                        <textarea name="tujuan" placeholder="Jelaskan secara singkat tujuan dan latar belakang proposal">{{ old('tujuan') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- ── FORM ACARA ── --}}
            <div id="formAcara" class="pc-section" style="display:none">
                <div class="pc-section-title">
                    <i class="fas fa-calendar-alt"></i> Rincian Kegiatan / Acara
                </div>
                <div class="pc-field-grid cols-2-1" style="margin-bottom:.875rem">
                    <div class="pc-field">
                        <label>Nama Acara</label>
                        <input type="text" name="judul_acara" maxlength="150" placeholder="Contoh: Team Night DC" value="{{ old('judul_acara') }}">
                    </div>
                    <div class="pc-field">
                        <label>Tanggal Acara</label>
                        <input type="date" name="tanggal_acara" value="{{ old('tanggal_acara') }}">
                    </div>
                    <div class="pc-field">
                        <label>Lokasi</label>
                        <input type="text" name="lokasi_acara" placeholder="Contoh: Gereja El Shaddai" value="{{ old('lokasi_acara') }}">
                    </div>
                </div>
                <div class="pc-field-grid" style="margin-bottom:.875rem">
                    <div class="pc-field">
                        <label>Rincian Kebutuhan Dana</label>
                        <textarea name="keterangan" placeholder="Contoh: Konsumsi (Rp 500.000), Dekorasi (Rp 300.000), Honor Pembicara (Rp 1.000.000), dll.">{{ old('keterangan') }}</textarea>
                    </div>
                </div>
                <div class="pc-field-grid cols-2">
                    <div class="pc-field">
                        <label>Total Anggaran (Rp) <span class="req">*</span></label>
                        <input type="text" name="total_anggaran_manual" id="totalManual" class="anggaran-input" style="max-width:320px;text-align:left" placeholder="Rp 0" value="{{ old('total_anggaran_manual') }}">
                    </div>
                </div>
            </div>

            {{-- ── FORM BARANG ── --}}
            <div id="formBarang" class="pc-section" style="display:none">
                <div class="pc-section-title">
                    <i class="fas fa-box"></i> Daftar Barang yang Diajukan
                </div>

                <div id="items-container">
                    <div class="pc-item-row">
                        <div class="pc-item-num">#1</div>
                        <div class="pc-item-grid barang">
                            <div class="pc-field">
                                <label>Nama Barang <span class="req">*</span></label>
                                <input type="text" name="items[0][nama_barang]" placeholder="Contoh: Laptop Dell">
                            </div>
                            <div class="pc-field">
                                <label>Merk / Tipe</label>
                                <input type="text" name="items[0][merk_tipe]" placeholder="Contoh: Dell Vostro 3500">
                            </div>
                            <div class="pc-field">
                                <label>Jumlah</label>
                                <input type="number" name="items[0][jumlah]" min="1" value="1">
                            </div>
                            <div class="pc-field">
                                <label>Tujuan Pembelian</label>
                                <input type="text" name="items[0][tujuan_pembelian]" placeholder="Contoh: Untuk staff administrasi">
                            </div>
                            <div class="pc-field">
                                <label>Anggaran (Rp) <span class="req">*</span></label>
                                <input type="text" name="items[0][anggaran]" class="anggaran-input" placeholder="Rp 0">
                            </div>
                            <button type="button" class="pc-btn-remove" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>

                <button type="button" id="addItem" class="pc-btn-add" style="margin-top:.5rem">
                    <i class="fas fa-plus"></i> Tambah Barang
                </button>

                <div class="pc-total-box">
                    <div>
                        <div class="pc-total-label">Total Anggaran</div>
                        <div class="pc-total-amount">Rp <span id="totalBarang">0</span></div>
                    </div>
                    <i class="fas fa-calculator" style="color:rgba(255,255,255,.4);font-size:2rem"></i>
                </div>
                <input type="hidden" name="total_anggaran" id="totalBarangInput">
            </div>

            {{-- ── FORM MAINTENANCE ── --}}
            <div id="formMaintenance" class="pc-section" style="display:none">
                <div class="pc-section-title">
                    <i class="fas fa-tools"></i> Rincian Maintenance Alat
                </div>

                <div id="maintenance-container">
                    <div class="pc-item-row">
                        <div class="pc-item-num">#1</div>
                        <div class="pc-item-grid maintenance">
                            <div class="pc-field">
                                <label>Nama Alat <span class="req">*</span></label>
                                <input type="text" name="items[0][nama_barang]" placeholder="Contoh: Printer HP">
                            </div>
                            <div class="pc-field">
                                <label>Jenis Perawatan</label>
                                <input type="text" name="items[0][tujuan_pembelian]" placeholder="Contoh: Ganti cartridge">
                            </div>
                            <div class="pc-field">
                                <label>Vendor / Teknisi</label>
                                <input type="text" name="items[0][merk_tipe]" placeholder="Contoh: CV Teknisi">
                            </div>
                            <div class="pc-field">
                                <label>Anggaran (Rp) <span class="req">*</span></label>
                                <input type="text" name="items[0][anggaran]" class="anggaran-input" placeholder="Rp 0">
                            </div>
                            <button type="button" class="pc-btn-remove" title="Hapus"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>

                <button type="button" id="addMaintenance" class="pc-btn-add" style="margin-top:.5rem">
                    <i class="fas fa-plus"></i> Tambah Alat
                </button>

                <div class="pc-total-box">
                    <div>
                        <div class="pc-total-label">Total Anggaran</div>
                        <div class="pc-total-amount">Rp <span id="totalMaintenance">0</span></div>
                    </div>
                    <i class="fas fa-wrench" style="color:rgba(255,255,255,.4);font-size:2rem"></i>
                </div>
                <input type="hidden" name="total_anggaran" id="totalMaintenanceInput">
            </div>

            {{-- ── ACTION BUTTONS ── --}}
            <div class="pc-action-section">
                <button type="submit" class="pc-btn-submit">
                    <i class="fas fa-save"></i> Simpan Proposal
                </button>
                <a href="{{ route('admin.proposal.index') }}" class="pc-btn-cancel">
                    <i class="fas fa-times"></i> Batal
                </a>
            </div>

        </form>
    </div>

</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* ── FORMAT RUPIAH ── */
    function formatRupiah(input) {
        let val = input.value.replace(/[^0-9]/g, "");
        input.value = val ? "Rp " + val.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
    }

    /* ── HITUNG TOTAL ── */
    function hitungTotal() {
        const activeForm = document.querySelector("#formBarang:not([style*='display:none']):not([style*='display: none']), #formMaintenance:not([style*='display:none']):not([style*='display: none'])");
        if (!activeForm) return;

        let total = 0;
        activeForm.querySelectorAll(".anggaran-input").forEach(el => {
            total += Number(el.value.replace(/[^0-9]/g, "") || 0);
        });

        if (activeForm.id === "formBarang") {
            document.getElementById("totalBarang").textContent = total.toLocaleString("id-ID");
            document.getElementById("totalBarangInput").value = total;
        }
        if (activeForm.id === "formMaintenance") {
            document.getElementById("totalMaintenance").textContent = total.toLocaleString("id-ID");
            document.getElementById("totalMaintenanceInput").value = total;
        }
    }

    /* ── TOGGLE FORM ── */
    const jenisSelect = document.getElementById("jenisProposal");
    const formBarang = document.getElementById("formBarang");
    const formAcara = document.getElementById("formAcara");
    const formMaintenance = document.getElementById("formMaintenance");
    const jenisBadge = document.getElementById("jenisBadge");

    function hideAll() {
        [formBarang, formAcara, formMaintenance].forEach(f => f.style.display = "none");
        document.querySelectorAll('[name^="items"]').forEach(el => { el.required = false; el.disabled = true; });
        document.querySelectorAll('[name="total_anggaran_manual"]').forEach(el => { el.required = false; el.disabled = true; });
    }

    function toggleForm() {
        const jenis = jenisSelect.value;

        const badgeMap = {
            barang:      { cls: "pc-jenis-badge jenis-barang",      txt: "Pengadaan Barang" },
            acara:       { cls: "pc-jenis-badge jenis-acara",       txt: "Kegiatan / Acara" },
            maintenance: { cls: "pc-jenis-badge jenis-maintenance",  txt: "Maintenance Alat" },
        };
        const b = badgeMap[jenis] || { cls: "pc-jenis-badge jenis-empty", txt: "Belum Dipilih" };
        jenisBadge.className = b.cls;
        jenisBadge.textContent = b.txt;

        hideAll();

        if (jenis === "barang") {
            formBarang.style.display = "block";
            formBarang.querySelectorAll('input[name^="items"]').forEach(el => {
                el.disabled = false;
                if (el.name.includes("nama_barang") || el.name.includes("anggaran")) el.required = true;
            });
        } else if (jenis === "acara") {
            formAcara.style.display = "block";
            const tm = document.querySelector('[name="total_anggaran_manual"]');
            if (tm) { tm.disabled = false; tm.required = true; }
        } else if (jenis === "maintenance") {
            formMaintenance.style.display = "block";
            formMaintenance.querySelectorAll('input[name^="items"]').forEach(el => {
                el.disabled = false;
                if (el.name.includes("nama_barang") || el.name.includes("anggaran")) el.required = true;
            });
        }

        hitungTotal();
    }

    jenisSelect.addEventListener("change", toggleForm);
    toggleForm();

    /* ── TAMBAH BARANG ── */
    let barangIndex = 1;
    document.getElementById("addItem")?.addEventListener("click", () => {
        const idx = barangIndex++;
        const row = document.createElement("div");
        row.className = "pc-item-row";
        row.innerHTML = `
            <div class="pc-item-num">#${idx + 1}</div>
            <div class="pc-item-grid barang">
                <div class="pc-field"><label>Nama Barang <span class="req">*</span></label>
                    <input type="text" name="items[${idx}][nama_barang]" placeholder="Nama Barang" required>
                </div>
                <div class="pc-field"><label>Merk / Tipe</label>
                    <input type="text" name="items[${idx}][merk_tipe]" placeholder="Merk / Tipe">
                </div>
                <div class="pc-field"><label>Jumlah</label>
                    <input type="number" name="items[${idx}][jumlah]" min="1" value="1">
                </div>
                <div class="pc-field"><label>Tujuan Pembelian</label>
                    <input type="text" name="items[${idx}][tujuan_pembelian]" placeholder="Tujuan Pembelian">
                </div>
                <div class="pc-field"><label>Anggaran (Rp) <span class="req">*</span></label>
                    <input type="text" name="items[${idx}][anggaran]" class="anggaran-input" placeholder="Rp 0" required>
                </div>
                <button type="button" class="pc-btn-remove" title="Hapus"><i class="fas fa-trash"></i></button>
            </div>`;
        document.getElementById("items-container").appendChild(row);
    });

    /* ── TAMBAH MAINTENANCE ── */
    let maintenanceIndex = 1;
    document.getElementById("addMaintenance")?.addEventListener("click", () => {
        const idx = maintenanceIndex++;
        const row = document.createElement("div");
        row.className = "pc-item-row";
        row.innerHTML = `
            <div class="pc-item-num">#${idx + 1}</div>
            <div class="pc-item-grid maintenance">
                <div class="pc-field"><label>Nama Alat <span class="req">*</span></label>
                    <input type="text" name="items[${idx}][nama_barang]" placeholder="Nama Alat" required>
                </div>
                <div class="pc-field"><label>Jenis Perawatan</label>
                    <input type="text" name="items[${idx}][tujuan_pembelian]" placeholder="Jenis Perawatan">
                </div>
                <div class="pc-field"><label>Vendor / Teknisi</label>
                    <input type="text" name="items[${idx}][merk_tipe]" placeholder="Vendor / Teknisi">
                </div>
                <div class="pc-field"><label>Anggaran (Rp) <span class="req">*</span></label>
                    <input type="text" name="items[${idx}][anggaran]" class="anggaran-input" placeholder="Rp 0" required>
                </div>
                <button type="button" class="pc-btn-remove" title="Hapus"><i class="fas fa-trash"></i></button>
            </div>`;
        document.getElementById("maintenance-container").appendChild(row);
    });

    /* ── HAPUS ITEM ── */
    document.addEventListener("click", function (e) {
        const btn = e.target.closest(".pc-btn-remove");
        if (!btn) return;
        const row = btn.closest(".pc-item-row");
        if (!row) return;
        if (row.parentElement.querySelectorAll(".pc-item-row").length > 1) {
            row.remove();
            hitungTotal();
            // Renomor badge
            row.parentElement?.querySelectorAll(".pc-item-num").forEach((el, i) => el.textContent = `#${i + 1}`);
        } else {
            alert("Minimal harus ada 1 item!");
        }
    });

    /* ── FORMAT RUPIAH OTOMATIS ── */
    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("anggaran-input")) {
            formatRupiah(e.target);
            hitungTotal();
        }
    });

    /* ── SUBMIT HANDLER ── */
    document.getElementById("proposalForm").addEventListener("submit", function (e) {
        e.preventDefault();

        const jenis = jenisSelect.value;
        const errors = [];

        const judul = document.querySelector('input[name="judul"]').value.trim();
        const deptInput = document.querySelector('select[name="departemen_id"]') || document.querySelector('input[name="departemen_id"]');
        const dept = deptInput?.value || "";
        const tgl = document.querySelector('input[name="tanggal_pengajuan"]').value;

        if (!judul)  errors.push("Judul proposal wajib diisi!");
        if (!dept)   errors.push("Departemen wajib dipilih!");
        if (!tgl)    errors.push("Tanggal pengajuan wajib diisi!");
        if (!jenis)  errors.push("Jenis proposal wajib dipilih!");

        if (jenis === "barang" || jenis === "maintenance") {
            const activeForm = jenis === "barang" ? formBarang : formMaintenance;
            const namaField = activeForm.querySelector('input[name="items[0][nama_barang]"]');
            const angField  = activeForm.querySelector('input[name="items[0][anggaran]"]');
            if (!namaField?.value.trim()) errors.push("Nama barang/alat wajib diisi!");
            const angka = angField?.value.replace(/[^0-9]/g, "") || "0";
            if (angka === "0") errors.push("Anggaran wajib diisi minimal Rp 1!");
        }

        if (jenis === "acara") {
            const totalManual = document.getElementById("totalManual");
            const val = totalManual?.value.replace(/[^0-9]/g, "") || "0";
            if (val === "0") errors.push("Total anggaran acara wajib diisi!");
        }

        if (errors.length > 0) {
            alert("Mohon lengkapi data berikut:\n\n" + errors.map(e => "• " + e).join("\n"));
            return;
        }

        // Bersihkan format Rupiah sebelum submit
        document.querySelectorAll(".anggaran-input").forEach(el => {
            el.value = el.value.replace(/[^0-9]/g, "") || "0";
        });
        const tm = document.getElementById("totalManual");
        if (tm) tm.value = tm.value.replace(/[^0-9]/g, "") || "0";

        this.submit();
    });
});
</script>

@endsection