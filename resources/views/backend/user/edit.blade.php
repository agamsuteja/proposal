@extends('backend.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card">
        <div class="card-header">
            <h5>Edit Pengguna</h5>
        </div>
        <div class="card-body">
            @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.user.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

                {{-- Nama --}}
                <div class="mb-3">
                    <label>Nama</label>
                    <input type="text" name="name" value="{{ $user->name }}" class="form-control" required>
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label>Email</label>
                    <input type="email" name="email" value="{{ $user->email }}" class="form-control" required>
                </div>

                {{-- Email --}}
                <div class="mb-3">
                    <label>No Hp</label>
                    <input type="nohp" name="nohp" value="{{ $user->nohp }}" class="form-control" required>
                </div>

                {{-- Password --}}
                <div class="mb-3">
                    <label>Password (isi jika ingin ubah)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                {{-- Role --}}
                <div class="mb-3">
                    <label>Role</label>
                    <select name="role" id="role" class="form-select" required>
                        @foreach($roles as $r)
                            <option value="{{ $r }}" {{ $user->role == $r ? 'selected' : '' }}>
                                {{ ucfirst($r) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Grup (muncul jika role = group_head) --}}
                <div class="mb-3" id="grupField" style="display: none;">
                    <label>Grup</label>
                    <select name="grup_id" class="form-select">
                        <option value="">-- Pilih Grup --</option>
                        @foreach($grup as $g)
                            <option value="{{ $g->id }}" {{ $user->grup_id == $g->id ? 'selected' : '' }}>
                                {{ $g->nama_grup }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Departemen (muncul jika role = departemen_head atau karyawan) --}}
                <div class="mb-3" id="departemenField" style="display: none;">
                    <label>Departemen</label>
                    <select name="departemen_id" class="form-select">
                        <option value="">-- Pilih Departemen --</option>
                        @foreach($departemen as $d)
                            <option value="{{ $d->id }}" {{ $user->departemen_id == $d->id ? 'selected' : '' }}>
                                {{ $d->nama_departemen }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status aktif --}}
                <div class="form-check mb-3">
                    <input type="hidden" name="is_active" value="0"><input class="form-check-input" type="checkbox" name="is_active" value="1" {{ $user->is_active ? 'checked' : '' }}>
                    <label class="form-check-label">Aktif</label>
                </div>

                <button class="btn btn-success">Update</button>
                <a href="{{ route('admin.user.index') }}" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

{{-- Script dinamis untuk menampilkan field sesuai role --}}
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const roleSelect = document.getElementById('role');
        const grupField = document.getElementById('grupField');
        const departemenField = document.getElementById('departemenField');

        function toggleFields() {
            const role = roleSelect.value;

            grupField.style.display = 'none';
            departemenField.style.display = 'none';

            if (role === 'group_head') {
                grupField.style.display = 'block';
            } else if (role === 'departemen_head' || role === 'karyawan') {
                departemenField.style.display = 'block';
            }
        }

        // Jalankan pertama kali (saat halaman edit dibuka)
        toggleFields();

        // Jalankan setiap kali role diubah
        roleSelect.addEventListener('change', toggleFields);
    });
</script>
@endsection
