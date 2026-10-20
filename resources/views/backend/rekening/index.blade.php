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
        color: #3b82f6;
    }
    
    .table-container {
        background: white;
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
        overflow: hidden;
        border: 1px solid #e2e8f0;
    }
    
    .table-header {
        padding: 1.25rem 1.5rem;
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .table-actions {
        display: flex;
        gap: 0.75rem;
    }
    
    .btn-add {
        background: #1e40af;
        border-color: #1e40af;
        color: white;
        padding: 0.5rem 1.25rem;
        font-weight: 500;
        border-radius: 8px;
        transition: all 0.2s;
    }
    
    .btn-add:hover {
        background: #1d4ed8;
        border-color: #1d4ed8;
        transform: translateY(-1px);
        box-shadow: 0 2px 4px rgba(30, 64, 175, 0.2);
    }
    
    .btn-add:focus {
        box-shadow: 0 0 0 3px rgba(30, 64, 175, 0.15);
    }
    
    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.9375rem;
    }
    
    thead {
        background-color: #f1f5f9;
    }
    
    thead th {
        padding: 1rem 1.25rem;
        text-align: left;
        font-weight: 600;
        color: #334155;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-size: 0.8125rem;
        border-bottom: 2px solid #cbd5e1;
    }
    
    tbody tr {
        border-bottom: 1px solid #f1f5f9;
        transition: background-color 0.15s;
    }
    
    tbody tr:hover {
        background-color: #f8fafc;
    }
    
    tbody td {
        padding: 1rem 1.25rem;
        color: #475569;
        vertical-align: middle;
    }
    
    .bank-badge {
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        padding: 0.25rem 0.75rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.8125rem;
        background: #dbeafe;
        color: #1e40af;
    }
    
    .bank-badge.bca { background: #dbeafe; color: #1e40af; }
    .bank-badge.bni { background: #fee2e2; color: #b91c1c; }
    .bank-badge.bri { background: #ffedd5; color: #c2410c; }
    .bank-badge.mandiri { background: #fef3c7; color: #92400e; }
    
    .account-number {
        font-family: 'Courier New', monospace;
        font-weight: 600;
        letter-spacing: 1px;
        color: #1e293b;
    }
    
    .balance-positive {
        font-weight: 700;
        color: #047857;
        font-size: 1.0625rem;
    }
    
    .branch-info {
        color: #64748b;
        font-size: 0.875rem;
        display: flex;
        align-items: center;
        gap: 0.25rem;
    }
    
    .action-cell {
        display: flex;
        gap: 0.5rem;
        justify-content: flex-end;
    }
    
    .btn-action {
        padding: 0.4375rem 0.875rem;
        border-radius: 6px;
        font-weight: 500;
        font-size: 0.875rem;
        display: inline-flex;
        align-items: center;
        gap: 0.375rem;
        transition: all 0.15s;
        border: 1px solid transparent;
    }
    
    .btn-edit {
        background: #dbeafe;
        color: #1e40af;
    }
    
    .btn-edit:hover {
        background: #bfdbfe;
        border-color: #93c5fd;
    }
    
    .btn-delete {
        background: #fee2e2;
        color: #b91c1c;
    }
    
    .btn-delete:hover {
        background: #fecaca;
        border-color: #fca5a5;
    }
    
    .empty-state {
        text-align: center;
        padding: 3.5rem 1.5rem;
    }
    
    .empty-icon {
        font-size: 3.5rem;
        color: #94a3b8;
        margin-bottom: 1rem;
    }
    
    .empty-title {
        font-weight: 700;
        font-size: 1.375rem;
        color: #334155;
        margin-bottom: 0.5rem;
    }
    
    .empty-text {
        color: #64748b;
        max-width: 42rem;
        margin: 0 auto 1.5rem;
        line-height: 1.6;
    }
    
    .alert-custom {
        border-radius: 8px;
        border-left: 3px solid #3b82f6;
        background: #eff6ff;
        color: #1e40af;
        padding: 0.875rem 1.25rem;
        margin-bottom: 1.5rem;
    }
    
    .alert-custom i {
        margin-right: 0.5rem;
        font-size: 1.125rem;
    }
    
    @media (max-width: 768px) {
        .table-container {
            overflow-x: auto;
        }
        
        table {
            min-width: 650px;
        }
        
        .page-header {
            flex-direction: column;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .table-actions {
            width: 100%;
            justify-content: flex-end;
        }
        
        thead th,
        tbody td {
            padding: 0.875rem 1rem;
        }
    }
</style>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="page-header">
                <h1 class="page-title">
                    <i class="bi bi-bank"></i>
                    Manajemen Rekening Bank
                </h1>
            </div>

            @if(session('success'))
                <div class="alert-custom">
                    <i class="bi bi-check-circle-fill"></i>
                    {{ session('success') }}
                </div>
            @endif

            <div class="table-container">
                <div class="table-header">
                    <h2 class="h5 fw-semibold m-0">Daftar Rekening Aktif</h2>
                    <div class="table-actions">
                        <a href="{{ route('admin.rekening.create') }}" class="btn btn-add">
                            <i class="bi bi-plus-lg"></i> Tambah Rekening
                        </a>
                    </div>
                </div>
                
                @if($rekenings->count() > 0)
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 5%">No</th>
                                <th>Nama Rekening</th>
                                <th>Bank</th>
                                <th>Nomor Rekening</th>
                                <th class="text-end">Saldo</th>
                                <th class="text-end" style="width: 15%">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rekenings as $index => $r)
                            <tr>
                                <td class="fw-medium">{{ $index + 1 }}</td>
                                <td class="fw-semibold">{{ $r->nama_rekening }}</td>
                                <td>
                                    @php
                                        $bankCode = strtolower(str_replace(' ', '', $r->bank ?? ''));
                                        $bankClass = in_array($bankCode, ['bca', 'bni', 'bri', 'mandiri']) ? $bankCode : '';
                                    @endphp
                                    <span class="bank-badge {{ $bankClass }}">
                                        <i class="bi bi-bank"></i>
                                        {{ $r->bank ?? 'Bank' }}
                                    </span>
                                </td>
                                <td class="account-number">{{ $r->nomor_rekening ?? '-' }}</td>
                                <td class="text-end balance-positive">
                                    Rp {{ number_format($r->saldo, 0, ',', '.') }}
                                </td>
                                <td class="action-cell">
                                    <a href="{{ route('admin.rekening.edit', $r->id) }}" 
                                       class="btn-action btn-edit"
                                       title="Edit rekening">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <form action="{{ route('admin.rekening.destroy', $r->id) }}" 
                                          method="POST" 
                                          class="d-inline"
                                          onsubmit="return confirm('Yakin menghapus rekening ini? Pastikan tidak ada transaksi aktif.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="btn-action btn-delete"
                                                title="Hapus rekening">
                                            <i class="bi bi-trash"></i> Hapus
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if($rekenings->hasPages())
                <div class="p-4 border-top d-flex justify-content-center">
                    {{ $rekenings->links('pagination::bootstrap-5') }}
                </div>
                @endif
                @else
                <div class="empty-state">
                    <div class="empty-icon">
                        <i class="bi bi-credit-card-2-front"></i>
                    </div>
                    <h3 class="empty-title">Belum Ada Data Rekening</h3>
                    <p class="empty-text">
                        Sistem belum memiliki data rekening bank. Tambahkan rekening pertama untuk memulai pengelolaan keuangan proposal. 
                        Rekening ini akan digunakan sebagai sumber dana untuk proses pencairan ke departemen.
                    </p>
                    <a href="{{ route('admin.rekening.create') }}" class="btn btn-add px-4 py-2">
                        <i class="bi bi-plus-circle"></i> Buat Rekening Pertama
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection