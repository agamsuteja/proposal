@extends('backend.layouts.app')

@section('content')
<div class="container py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="fw-bold mb-0">➕ Tambah Rekening</h5>
        </div>
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.rekening.store') }}" method="POST">
                @csrf
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Rekening *</label>
                        <input type="text" name="nama_rekening" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank</label>
                        <input type="text" name="bank" class="form-control">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="nomor_rekening" class="form-control">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Saldo Awal (Rp)</label>
                        <input type="text" id="saldo" name="saldo" class="form-control" required>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Simpan</button>
                <a href="{{ route('admin.rekening.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const saldoInput = document.getElementById("saldo");

    saldoInput.addEventListener("input", function (e) {
        let value = this.value.replace(/[^0-9]/g, "");

        if (value) {
            this.value = "Rp " + new Intl.NumberFormat("id-ID").format(value);
        } else {
            this.value = "";
        }
    });

    // sebelum submit, hilangkan format supaya masuk ke database sebagai angka
    saldoInput.closest("form").addEventListener("submit", function () {
        saldoInput.value = saldoInput.value.replace(/[^0-9]/g, "");
    });
});
</script>

@endsection
