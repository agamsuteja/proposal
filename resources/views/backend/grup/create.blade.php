@extends('backend.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <h5>Tambah Grup</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.grup.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label>Nama Grup</label>
                    <input type="text" name="nama_grup" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control"></textarea>
                </div>
                <button class="btn btn-success">Simpan</button>
                <a href="{{ route('admin.grup.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection
