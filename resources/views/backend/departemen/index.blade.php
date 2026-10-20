@extends('backend.layouts.app')

@section('content')

<style>
    /* ===== DESKTOP MODE (tabel) ===== */
    @media (min-width: 576px) {
        .departemen-table { display: table; }
        .departemen-mobile { display: none; }
    }

    /* ===== MOBILE MODE (card) ===== */
    @media (max-width: 575px) {
        .departemen-table { display: none; }

        .departemen-mobile {
            display: block;
        }

        .dep-card {
            background: #ffffff;
            border-radius: 14px;
            padding: 16px;
            margin-bottom: 12px;
            box-shadow: 0px 3px 10px rgba(0,0,0,0.07);
            border: 1px solid #f1f1f1;
        }

        .dep-title {
            font-size: 1rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 4px;
        }

        .dep-sub {
            font-size: 0.85rem;
            color: #555;
            margin-bottom: 6px;
        }

        .dep-desc {
            font-size: 0.8rem;
            color: #777;
            margin-bottom: 10px;
        }

        .dep-actions a,
        .dep-actions button {
            font-size: 0.75rem !important;
            padding: 4px 8px !important;
            border-radius: 6px;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="card border-0 shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center bg-white">
            <h5 class="fw-bold">Daftar Departemen</h5>
            <a href="{{ route('admin.departemen.create') }}" class="btn btn-primary btn-sm">+ Tambah</a>
        </div>

        <div class="card-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- ======= DESKTOP TABLE ======= --}}
            <div class="table-responsive departemen-table">
                <table class="table table-bordered table-striped align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%">#</th>
                            <th>Nama Departemen</th>
                            <th>Grup</th>
                            <th>Deskripsi</th>
                            <th width="15%">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($departemen as $item)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $item->nama_departemen }}</td>
                            <td>{{ $item->grup->nama_grup ?? '-' }}</td>
                            <td>{{ $item->deskripsi ?? '-' }}</td>
                            <td>
                                <a href="{{ route('admin.departemen.edit', $item->id) }}" class="btn btn-warning btn-sm">Edit</a>
                                <form action="{{ route('admin.departemen.destroy', $item->id) }}" class="d-inline" method="POST">
                                    @csrf @method('DELETE')
                                    <button onclick="return confirm('Yakin hapus?')" class="btn btn-danger btn-sm">Hapus</button>
                                </form>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center text-muted">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- ======= MOBILE CARD LIST ======= --}}
            <div class="departemen-mobile">
                @forelse($departemen as $item)
                    <div class="dep-card">
                        <div class="dep-title">{{ $item->nama_departemen }}</div>
                        <div class="dep-sub">Grup: <strong>{{ $item->grup->nama_grup ?? '-' }}</strong></div>
                        <div class="dep-desc">{{ $item->deskripsi ?? 'Tidak ada deskripsi.' }}</div>

                        <div class="dep-actions d-flex gap-2">
                            <a href="{{ route('admin.departemen.edit', $item->id) }}" class="btn btn-warning btn-sm">Edit</a>

                            <form action="{{ route('admin.departemen.destroy', $item->id) }}" method="POST">
                                @csrf @method('DELETE')
                                <button onclick="return confirm('Hapus departemen ini?')" class="btn btn-danger btn-sm">
                                    Hapus
                                </button>
                            </form>
                        </div>
                    </div>
                @empty
                    <p class="text-center text-muted mt-3">Belum ada departemen.</p>
                @endforelse
            </div>

        </div>
    </div>
</div>

@endsection
