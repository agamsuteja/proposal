@extends('backend.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <h5>Edit Grup</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.grup.update', $grup->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label>Nama Grup</label>
                    <input type="text" name="nama_grup" class="form-control" value="{{ $grup->nama_grup }}" required>
                </div>
                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control">{{ $grup->deskripsi }}</textarea>
                </div>
                <button class="btn btn-success">Update</button>
                <a href="{{ route('admin.grup.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection
