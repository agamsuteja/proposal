@extends('backend.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <h5>Edit Departemen</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.departemen.update', $departemen->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="mb-3">
                    <label>Grup</label>
                    <select name="grup_id" class="form-select" required>
                        <option value="">-- Pilih Grup --</option>
                        @foreach($grup as $g)
                            <option value="{{ $g->id }}" {{ $departemen->grup_id == $g->id ? 'selected' : '' }}>
                                {{ $g->nama_grup }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label>Nama Departemen</label>
                    <input type="text" name="nama_departemen" class="form-control" value="{{ $departemen->nama_departemen }}" required>
                </div>

                <div class="mb-3">
                    <label>Deskripsi</label>
                    <textarea name="deskripsi" class="form-control">{{ $departemen->deskripsi }}</textarea>
                </div>

                <button class="btn btn-success">Update</button>
                <a href="{{ route('admin.departemen.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>
@endsection
