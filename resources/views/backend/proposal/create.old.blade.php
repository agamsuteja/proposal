@extends('backend.layouts.app')

@section('content')
<style>
    /* ===== DESAIN PROFESIONAL - MINIMALIS & CLEAN ===== */
    .page-header {
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 1.25rem;
        margin-bottom: 1.75rem;
    }
    
    .page-title {
        font-weight: 700;
        font-size: 1.625rem;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }
    
    .page-title i {
        font-size: 1.75rem;
        color: #1e40af;
    }
    
    .card-custom {
        border-radius: 16px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #e2e8f0;
        transition: all 0.3s ease;
    }
    
    .card-custom:hover {
        box-shadow: 0 6px 25px rgba(30, 64, 175, 0.12);
        border-color: #cbd5e1;
    }
    
    .form-section {
        padding: 1.75rem;
        border-bottom: 1px solid #f1f5f9;
    }
    
    .form-section:last-child {
        border-bottom: none;
    }
    
    .section-title {
        font-weight: 700;
        font-size: 1.25rem;
        color: #1e293b;
        margin-bottom: 1.5rem;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding-bottom: 0.75rem;
        border-bottom: 2px solid #f1f5f9;
    }
    
    .section-title i {
        color: #3b82f6;
        font-size: 1.375rem;
    }
    
    .form-label {
        font-weight: 600;
        color: #334155;
        margin-bottom: 0.5rem;
        display: block;
    }
    
    .text-required {
        color: #dc2626;
        margin-left: 0.25rem;
    }
    
    .form-control, .form-select {
        border: 1px solid #cbd5e1;
        padding: 0.625rem 1rem;
        border-radius: 8px;
        transition: all 0.2s;
        font-size: 0.9375rem;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #3b82f6;
        box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.15);
        outline: none;
    }
    
    .form-control::placeholder {
        color: #94a3b8;
    }
    
    .jenis-badge {
        display: inline-block;
        padding: 0.25rem 0.875rem;
        border-radius: 9999px;
        font-weight: 600;
        font-size: 0.8125rem;
        margin-left: 0.5rem;
    }
    
    .jenis-barang { background: #dbeafe; color: #1e40af; }
    .jenis-acara { background: #dcfce7; color: #166534; }
    .jenis-maintenance { background: #ffedd5; color: #c2410c; }
    
    .item-row {
        background: #f8fafc;
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1rem;
        border: 1px solid #e2e8f0;
        transition: all 0.2s;
    }
    
    .item-row:hover {
        border-color: #cbd5e1;
        background: #f1f5f9;
    }
    
    .btn-action {
        padding: 0.5rem 1rem;
        border-radius: 8px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        transition: all 0.2s;
    }
    
    .btn-add-item {
        background: #dbeafe;
        color: #1e40af;
        border: 1px solid #93c5fd;
    }
    
    .btn-add-item:hover {
        background: #bfdbfe;
        border-color: #60a5fa;
    }
    
    .btn-remove-item {
        background: #fee2e2;
        color: #b91c1c;
        border: 1px solid #fca5a5;
        padding: 0.375rem 0.75rem;
    }
    
    .btn-remove-item:hover {
        background: #fecaca;
        border-color: #f87171;
    }
    
    .total-summary {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
        color: white;
        border-radius: 16px;
        padding: 1.5rem;
        text-align: center;
        margin: 1.75rem 0;
        box-shadow: 0 4px 15px rgba(30, 64, 175, 0.25);
    }
    
    .total-label {
        font-size: 1.125rem;
        opacity: 0.9;
        margin-bottom: 0.5rem;
    }
    
    .total-amount {
        font-size: 2rem;
        font-weight: 800;
        letter-spacing: -0.025em;
    }
    
    .btn-submit {
        background: linear-gradient(135deg, #1e40af 0%, #1d4ed8 100%);
        border: none;
        padding: 0.875rem 2rem;
        font-weight: 700;
        font-size: 1.0625rem;
        border-radius: 12px;
        box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
        transition: all 0.3s ease;
    }
    
    .btn-submit:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
    }
    
    .btn-cancel {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 0.875rem 2rem;
        font-weight: 600;
        border-radius: 12px;
        transition: all 0.2s;
    }
    
    .btn-cancel:hover {
        background: #e2e8f0;
        border-color: #94a3b8;
    }
    
    .alert-custom {
        border-radius: 12px;
        border-left: 4px solid #dc2626;
        background: #fef2f2;
        color: #b91c1c;
        padding: 1.25rem 1.5rem;
        margin-bottom: 1.75rem;
    }
    
    .alert-custom ul {
        margin-bottom: 0;
        padding-left: 1.25rem;
    }
    
    .alert-custom li {
        margin-bottom: 0.25rem;
        line-height: 1.5;
    }
    
    @media (max-width: 768px) {
        .form-section {
            padding: 1.25rem;
        }
        
        .section-title {
            font-size: 1.125rem;
        }
        
        .btn-submit, .btn-cancel {
            width: 100%;
            justify-content: center;
        }
        
        .d-md-flex {
            flex-direction: column;
            gap: 1rem;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-file-earmark-plus"></i>
                    Buat Proposal Baru
                </h1>
            </div>

            @if ($errors->any())
                <div class="alert-custom">
                    <strong class="d-block mb-2">Mohon perbaiki kesalahan berikut:</strong>
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card card-custom">
                <div class="card-body">
                    <form action="{{ route('admin.proposal.store') }}" method="POST" enctype="multipart/form-data" id="proposalForm">
                        @csrf

                        {{-- 🧩 Jenis Proposal --}}
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="bi bi-ui-checks-grid"></i>
                                Jenis Proposal
                            </h2>
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label">Pilih Jenis Proposal <span class="text-required">*</span></label>
                                    <select name="jenis" id="jenisProposal" class="form-select" required>
                                        <option value="">-- Pilih Jenis Proposal --</option>
                                        <option value="barang">Pengadaan Barang</option>
                                        <option value="acara">Kegiatan / Acara</option>
                                        <option value="maintenance">Maintenance Alat</option>
                                    </select>
                                    <div class="mt-2 d-flex align-items-center">
                                        <span class="fw-medium me-2">Jenis Terpilih:</span>
                                        <span id="jenisBadge" class="jenis-badge jenis-barang">Pengadaan Barang</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- 🧾 Informasi Umum --}}
                        <div class="form-section">
                            <h2 class="section-title">
                                <i class="bi bi-info-circle"></i>
                                Informasi Umum
                            </h2>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Judul Proposal <span class="text-required">*</span></label>
                                    <input type="text" name="judul" class="form-control" placeholder="Contoh: Pengadaan Laptop untuk Staff" required>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Departemen <span class="text-required">*</span></label>
                                    @php $user = Auth::user(); @endphp
                                    @if($user->role === 'karyawan')
                                        <input type="hidden" name="departemen_id" value="{{ $departemen->id }}">
                                        <input type="text" class="form-control" value="{{ $departemen->nama_departemen }}" readonly>
                                    @else
                                        <select name="departemen_id" class="form-select" required>
                                            <option value="">-- Pilih Departemen --</option>
                                            @foreach($departemens as $d)
                                                <option value="{{ $d->id }}">{{ $d->nama_departemen }}</option>
                                            @endforeach
                                        </select>
                                    @endif
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tanggal Pengajuan <span class="text-required">*</span></label>
                                    <input type="date" name="tanggal_pengajuan" class="form-control" required value="{{ date('Y-m-d') }}">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Tujuan / Deskripsi Proposal</label>
                                    <textarea name="tujuan" class="form-control" rows="3" placeholder="Jelaskan secara singkat tujuan dan latar belakang proposal"></textarea>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Lampiran (Opsional)</label>
                                    <input type="file" name="lampiran" class="form-control" accept=".pdf,.jpg,.jpeg,.png">
                                    <small class="text-muted mt-1">Format: PDF, JPG, PNG (max 2MB)</small>
                                </div>
                            </div>
                        </div>

                        {{-- 🎉 Form Acara --}}
                        <div id="formAcara" class="form-section d-none">
                            <h2 class="section-title">
                                <i class="bi bi-calendar-event"></i>
                                Rincian Kegiatan / Acara
                            </h2>
                            <div class="row g-4">
                                <div class="col-md-6">
                                    <label class="form-label">Nama Acara</label>
                                    <input type="text" name="judul_acara" class="form-control" placeholder="Contoh: Team Night DC">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Tanggal Acara</label>
                                    <input type="date" name="tanggal_acara" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Lokasi</label>
                                    <input type="text" name="lokasi_acara" class="form-control" placeholder="Contoh: Gereja El Shaddai">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Rincian Kebutuhan Dana</label>
                                    <textarea name="keterangan" class="form-control" rows="3" placeholder="Contoh: Konsumsi (Rp 500.000), Dekorasi (Rp 300.000), Honor Pembicara (Rp 1.000.000), dll."></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Total Anggaran (Rp) <span class="text-required">*</span></label>
                                    <input type="text" name="total_anggaran_manual" id="totalManual" class="form-control anggaran-input" placeholder="Rp 0">
                                </div>
                            </div>
                        </div>

                        {{-- 📦 Form Barang --}}
                        <div id="formBarang" class="form-section d-none">
                            <h2 class="section-title">
                                <i class="bi bi-box-seam"></i>
                                Daftar Barang yang Diajukan
                            </h2>
                            
                            <div id="items-container">
                                <div class="item-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Nama Barang <span class="text-required">*</span></label>
                                            <input type="text" name="items[0][nama_barang]" class="form-control" placeholder="Contoh: Laptop Dell" required>
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Merk / Tipe</label>
                                            <input type="text" name="items[0][merk_tipe]" class="form-control" placeholder="Contoh: Dell Vostro 3500">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Jumlah</label>
                                            <input type="number" name="items[0][jumlah]" class="form-control" min="1" value="1">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tujuan Pembelian</label>
                                            <input type="text" name="items[0][tujuan_pembelian]" class="form-control" placeholder="Contoh: Untuk staff administrasi">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Anggaran (Rp) <span class="text-required">*</span></label>
                                            <div class="input-group">
                                                <input type="text" name="items[0][anggaran]" class="form-control anggaran-input" placeholder="Rp 0" required>
                                                <button type="button" class="btn btn-remove-item ms-2">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="button" id="addItem" class="btn btn-add-item">
                                    <i class="bi bi-plus-lg"></i> Tambah Barang
                                </button>
                            </div>

                            <div class="total-summary mt-4">
                                <div class="total-label">TOTAL ANGGARAN</div>
                                <div class="total-amount">Rp <span id="totalBarang">0</span></div>
                                <input type="hidden" name="total_anggaran" id="totalBarangInput">
                            </div>
                        </div>

                        {{-- 🔧 Form Maintenance --}}
                        <div id="formMaintenance" class="form-section d-none">
                            <h2 class="section-title">
                                <i class="bi bi-tools"></i>
                                Rincian Maintenance Alat
                            </h2>
                            
                            <div id="maintenance-container">
                                <div class="item-row">
                                    <div class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Nama Alat <span class="text-required">*</span></label>
                                            <input type="text" name="items[0][nama_barang]" class="form-control" placeholder="Contoh: Printer HP" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Jenis Perawatan</label>
                                            <input type="text" name="items[0][tujuan_pembelian]" class="form-control" placeholder="Contoh: Ganti cartridge">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Vendor / Teknisi</label>
                                            <input type="text" name="items[0][merk_tipe]" class="form-control" placeholder="Contoh: CV Teknisi">
                                        </div>
                                        <div class="col-md-2">
                                            <label class="form-label">Anggaran (Rp) <span class="text-required">*</span></label>
                                            <input type="text" name="items[0][anggaran]" class="form-control anggaran-input" placeholder="Rp 0" required>
                                        </div>
                                        <div class="col-md-2 d-flex align-items-end">
                                            <button type="button" class="btn btn-remove-item w-100">
                                                <i class="bi bi-trash"></i> Hapus
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="button" id="addMaintenance" class="btn btn-add-item">
                                    <i class="bi bi-plus-lg"></i> Tambah Alat
                                </button>
                            </div>

                            <div class="total-summary mt-4">
                                <div class="total-label">TOTAL ANGGARAN</div>
                                <div class="total-amount">Rp <span id="totalMaintenance">0</span></div>
                                <input type="hidden" name="total_anggaran" id="totalMaintenanceInput">
                            </div>
                        </div>

                        {{-- ACTIONS --}}
                        <div class="form-section bg-gray-50">
                            <div class="d-flex gap-3 flex-wrap">
                                <button type="submit" class="btn btn-submit">
                                    <i class="bi bi-save me-2"></i>Simpan Proposal
                                </button>
                                <a href="{{ route('admin.proposal.index') }}" class="btn btn-cancel">
                                    <i class="bi bi-arrow-left me-2"></i>Kembali ke Daftar
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {

    /* ===============================
       FORMAT RUPIAH
    ================================ */
    function formatRupiah(value) {
        value = value.value.replace(/[^0-9]/g, "");
        if (!value) return "";
        return "Rp " + value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    /* ===============================
       HITUNG TOTAL
    ================================ */
    function hitungTotal() {
        let total = 0;
        const activeForm = document.querySelector("#formBarang:not(.d-none), #formMaintenance:not(.d-none)");
        if (!activeForm) return;

        activeForm.querySelectorAll(".anggaran-input").forEach(el => {
            const angka = el.value.replace(/[^0-9]/g, "");
            total += Number(angka || 0);
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

    /* ===============================
       TOGGLE FORM BERDASARKAN JENIS
    ================================ */
    const jenisSelect = document.getElementById("jenisProposal");
    const formBarang = document.getElementById("formBarang");
    const formAcara = document.getElementById("formAcara");
    const formMaintenance = document.getElementById("formMaintenance");
    const jenisBadge = document.getElementById("jenisBadge");

    function toggleForm() {
        const jenis = jenisSelect.value;

        // Update badge
        if (jenis === "barang") {
            jenisBadge.className = "jenis-badge jenis-barang";
            jenisBadge.textContent = "Pengadaan Barang";
        } else if (jenis === "acara") {
            jenisBadge.className = "jenis-badge jenis-acara";
            jenisBadge.textContent = "Kegiatan / Acara";
        } else if (jenis === "maintenance") {
            jenisBadge.className = "jenis-badge jenis-maintenance";
            jenisBadge.textContent = "Maintenance Alat";
        } else {
            jenisBadge.className = "jenis-badge";
            jenisBadge.textContent = "Belum Dipilih";
        }

        // Sembunyikan semua form
        formBarang.classList.add("d-none");
        formAcara.classList.add("d-none");
        formMaintenance.classList.add("d-none");

        // Reset required attributes
        document.querySelectorAll('[name^="items"]').forEach(el => {
            el.required = false;
            el.disabled = true;
        });
        document.querySelectorAll('[name="total_anggaran_manual"]').forEach(el => {
            el.required = false;
            el.disabled = true;
        });

        // Tampilkan & aktifkan form yang dipilih
        if (jenis === "barang") {
            formBarang.classList.remove("d-none");
            document.querySelectorAll('#formBarang [name^="items[0]"]').forEach(el => {
                el.disabled = false;
                if (el.name.includes('nama_barang') || el.name.includes('anggaran')) {
                    el.required = true;
                }
            });
        }

        if (jenis === "acara") {
            formAcara.classList.remove("d-none");
            document.querySelector('[name="total_anggaran_manual"]').disabled = false;
            document.querySelector('[name="total_anggaran_manual"]').required = true;
        }

        if (jenis === "maintenance") {
            formMaintenance.classList.remove("d-none");
            document.querySelectorAll('#formMaintenance [name^="items[0]"]').forEach(el => {
                el.disabled = false;
                if (el.name.includes('nama_barang') || el.name.includes('anggaran')) {
                    el.required = true;
                }
            });
        }

        hitungTotal();
    }

    jenisSelect.addEventListener("change", toggleForm);
    toggleForm();

    /* ===============================
       TAMBAH BARANG
    ================================ */
    const barangContainer = document.getElementById("items-container");
    const addBarangBtn = document.getElementById("addItem");
    let barangIndex = 1;

    addBarangBtn?.addEventListener("click", () => {
        const row = document.createElement("div");
        row.className = "item-row";
        row.innerHTML = `
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="items[${barangIndex}][nama_barang]" class="form-control" placeholder="Nama Barang" required>
                </div>
                <div class="col-md-2">
                    <input type="text" name="items[${barangIndex}][merk_tipe]" class="form-control" placeholder="Merk / Tipe">
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[${barangIndex}][jumlah]" class="form-control" min="1" value="1">
                </div>
                <div class="col-md-3">
                    <input type="text" name="items[${barangIndex}][tujuan_pembelian]" class="form-control" placeholder="Tujuan Pembelian">
                </div>
                <div class="col-md-2">
                    <div class="input-group">
                        <input type="text" name="items[${barangIndex}][anggaran]" class="form-control anggaran-input" placeholder="Rp 0" required>
                        <button type="button" class="btn btn-remove-item ms-2">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
        barangContainer.appendChild(row);
        barangIndex++;
    });

    /* ===============================
       TAMBAH MAINTENANCE
    ================================ */
    const maintenanceContainer = document.getElementById("maintenance-container");
    const addMaintenanceBtn = document.getElementById("addMaintenance");
    let maintenanceIndex = 1;

    addMaintenanceBtn?.addEventListener("click", () => {
        const row = document.createElement("div");
        row.className = "item-row";
        row.innerHTML = `
            <div class="row g-3">
                <div class="col-md-3">
                    <input type="text" name="items[${maintenanceIndex}][nama_barang]" class="form-control" placeholder="Nama Alat" required>
                </div>
                <div class="col-md-3">
                    <input type="text" name="items[${maintenanceIndex}][tujuan_pembelian]" class="form-control" placeholder="Jenis Perawatan">
                </div>
                <div class="col-md-2">
                    <input type="text" name="items[${maintenanceIndex}][merk_tipe]" class="form-control" placeholder="Vendor / Teknisi">
                </div>
                <div class="col-md-2">
                    <input type="text" name="items[${maintenanceIndex}][anggaran]" class="form-control anggaran-input" placeholder="Rp 0" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="button" class="btn btn-remove-item w-100">
                        <i class="bi bi-trash"></i> Hapus
                    </button>
                </div>
            </div>
        `;
        maintenanceContainer.appendChild(row);
        maintenanceIndex++;
    });

    /* ===============================
       HAPUS ITEM
    ================================ */
    document.addEventListener("click", function (e) {
        if (e.target.closest(".btn-remove-item")) {
            const row = e.target.closest(".item-row");
            if (row && row.parentElement.children.length > 1) {
                row.remove();
                hitungTotal();
            } else if (row) {
                alert("Minimal harus ada 1 item!");
            }
        }
    });

    /* ===============================
       FORMAT RUPIAH OTOMATIS (DELEGATED)
    ================================ */
    document.addEventListener("input", function (e) {
        if (e.target.classList.contains("anggaran-input")) {
            let value = e.target.value.replace(/[^0-9]/g, "");
            e.target.value = value ? "Rp " + value.replace(/\B(?=(\d{3})+(?!\d))/g, ".") : "";
            hitungTotal();
        }
    });

    /* ===============================
       SUBMIT HANDLER - PERBAIKAN KRUSIAL
    ================================ */
    document.getElementById("proposalForm").addEventListener("submit", function (e) {
        e.preventDefault();
        
        const jenis = document.getElementById("jenisProposal").value;
        let isValid = true;
        let errorMessage = '';

        // ✅ VALIDASI UMUM
        const judul = document.querySelector('input[name="judul"]').value.trim();
        const departemen = document.querySelector('select[name="departemen_id"], input[name="departemen_id"]').value;
        const tanggal = document.querySelector('input[name="tanggal_pengajuan"]').value;
        
        if (!judul) {
            isValid = false;
            errorMessage += '❌ Judul proposal wajib diisi!\n';
        }
        if (!departemen) {
            isValid = false;
            errorMessage += '❌ Departemen wajib dipilih!\n';
        }
        if (!tanggal) {
            isValid = false;
            errorMessage += '❌ Tanggal pengajuan wajib diisi!\n';
        }

        // ✅ VALIDASI BARANG / MAINTENANCE - PERBAIKAN KRUSIAL!
        if (jenis === 'barang' || jenis === 'maintenance') {
            // Cari field yang VISIBLE dan ENABLED
            const formActive = jenis === 'barang' ? formBarang : formMaintenance;
            const namaField = formActive.querySelector('input[name="items[0][nama_barang]"]');
            const anggaranField = formActive.querySelector('input[name="items[0][anggaran]"]');
            
            console.log('=== DEBUG VALIDASI ===');
            console.log('Jenis:', jenis);
            console.log('Form visible:', !formActive.classList.contains('d-none'));
            console.log('Nama field:', namaField?.value);
            console.log('Anggaran field:', anggaranField?.value);
            
            if (!namaField || !namaField.value.trim()) {
                isValid = false;
                errorMessage += '❌ Nama barang/alat wajib diisi!\n';
            }
            
            const anggaranClean = anggaranField?.value.replace(/[^0-9]/g, '') || '0';
            if (anggaranClean === '0') {
                isValid = false;
                errorMessage += '❌ Anggaran wajib diisi dan minimal Rp 1!\n';
            }
        }

        // ✅ VALIDASI ACARA
        if (jenis === 'acara') {
            const total = document.getElementById("totalManual");
            const totalClean = total?.value.replace(/[^0-9]/g, '') || '0';
            if (totalClean === '0') {
                isValid = false;
                errorMessage += '❌ Total anggaran wajib diisi!\n';
            }
        }

        // ✅ JIKA VALID, BERSIHKAN DATA DAN SUBMIT
        if (isValid) {
            // Bersihkan SEMUA anggaran
            document.querySelectorAll(".anggaran-input").forEach(el => {
                el.value = el.value.replace(/[^0-9]/g, '') || '0';
            });

            // Bersihkan total manual
            const totalManual = document.getElementById("totalManual");
            if (totalManual) {
                totalManual.value = totalManual.value.replace(/[^0-9]/g, '') || '0';
            }
            
            console.log('✅ Form valid, submitting...');
            this.submit();
        } else {
            // Tampilkan error
            alert('Mohon lengkapi data berikut:\n\n' + errorMessage);
            return false;
        }
    });

});
</script>
@endsection