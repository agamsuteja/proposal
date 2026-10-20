@extends('backend.layouts.app')

@section('content')
<div class="container-fluid py-3">
    <div class="card shadow-sm border-0">
        <div class="card-header bg-white">
            <h5 class="fw-bold mb-0">Verifikasi User Baru</h5>
        </div>

        <div class="card-body">

            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            @if ($users->count() == 0)
                <p class="text-muted">Tidak ada user menunggu persetujuan.</p>
            @else
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No HP</th>
                            <th>Role</th>
                            <th>Departemen</th>
                            <th>Grup</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($users as $u)
                            <tr>
                                <td>{{ $u->name }}</td>
                                <td>{{ $u->email }}</td>
                                <td>{{ $u->nohp }}</td>
                                <td>{{ $u->role }}</td>
                                <td>{{ $u->departemen->nama_departemen ?? '-' }}</td>
                                <td>{{ $u->grup->nama_grup ?? '-' }}</td>
                                <td>
                                    <form action="{{ route('admin.user.approve', $u->id) }}" method="POST">
                                        @csrf
                                        <button class="btn btn-success btn-sm">Setujui</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif

        </div>
    </div>
</div>
@endsection
