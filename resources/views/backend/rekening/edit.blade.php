@extends('backend.layouts.app')

@section('content')
<div class="container py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white">
            <h5 class="fw-bold mb-0">✏️ Edit Rekening</h5>
        </div>
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.rekening.update', $rekening->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Rekening *</label>
                        <input type="text" name="nama_rekening" class="form-control" value="{{ old('nama_rekening', $rekening->nama_rekening) }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Bank</label>
                        <input type="text" name="bank" class="form-control" value="{{ old('bank', $rekening->bank) }}">
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Nomor Rekening</label>
                        <input type="text" name="nomor_rekening" class="form-control" value="{{ old('nomor_rekening', $rekening->nomor_rekening) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Saldo Awal (Rp)</label>
                        <input type="number" name="saldo" class="form-control" value="{{ old('saldo', $rekening->saldo) }}" required min="0">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2">{{ old('keterangan', $rekening->keterangan) }}</textarea>
                </div>
                <div class="mb-3"><label for="reconcile-reason">Alasan perubahan saldo (wajib jika saldo berubah)</label><input id="reconcile-reason" name="alasan" class="form-control" minlength="5" maxlength="1000" value="{{ old('alasan') }}"></div>
<button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('admin.rekening.index') }}" class="btn btn-secondary">Batal</a>
            </form>
        </div>
    </div>
</div>
@endsection
