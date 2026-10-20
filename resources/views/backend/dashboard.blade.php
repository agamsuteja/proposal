@extends('backend.layouts.app')

@section('title', 'Dashboard')

@section('content')

<style>
    /* ====== STYLE TAMBAHAN AGAR MIRIP APLIKASI MOBILE ====== */
    .dashboard-header {
        background: linear-gradient(135deg, #8c8c8c, #6aa8ff);
        border-radius: 18px;
        padding: 35px 20px;
        color: #fff;
        text-align: center;
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }

    .stat-card {
        border-radius: 18px;
        padding: 20px;
        background: #ffffff;
        box-shadow: 0 3px 10px rgba(0,0,0,0.05);
        transition: .2s ease-in-out;
    }

    .stat-card:hover {
        transform: translateY(-3px);
    }

    @media (max-width: 576px) {
        .stat-card h5 {
            font-size: 0.9rem;
        }
        .stat-card h3 {
            font-size: 1.4rem;
        }
    }
</style>

<div class="row">

    {{-- HEADER / WELCOME --}}
    <div class="col-12 mb-4">
        <div class="dashboard-header">
            <h3 class="fw-bold mb-1">Selamat Datang</h3>
            <h4 class="fw-bold">ESC Proposal</h4>

            <h6 id="current-date" class="mt-3 text-light-50"></h6>
            <h2 id="current-time" class="fw-bold"></h2>
        </div>
    </div>

    {{-- DASHBOARD KEUANGAN — Hanya tampil untuk role: keuangan --}}
@if(auth()->user()->role === 'keuangan')

<div class="row">

    

    {{-- STATISTIK --}}
    <div class="col-12">

        <div class="row g-3">

            {{-- PROPOSAL DISETUJUI --}}
            <div class="col-12 col-md-6">
                <div class="stat-card text-center">
                    <h5 class="fw-semibold text-secondary">Total Proposal Disetujui</h5>
                    <h3 class="fw-bold text-success">{{ $totalProposalDisetujui }}</h3>
                </div>
            </div>

            {{-- TOTAL DANA DICAIRKAN --}}
            <div class="col-12 col-md-6">
                <div class="stat-card text-center">
                    <h5 class="fw-semibold text-secondary">Total Dana Keseluruhan Pencairan</h5>
                    <h3 class="fw-bold text-primary">
                        Rp {{ number_format($totalDanaSemua, 0, ',', '.') }}
                    </h3>
                </div>
            </div>

        </div>

    </div>


    {{-- TOTAL DANA PER DEPARTEMEN --}}
    <div class="col-12 mt-4">
        <div class="stat-card">
            <h5 class="fw-bold text-secondary mb-3 text-center">Total Dana Dicairkan Per Departemen</h5>

            @foreach($danaPerDepartemen as $item)
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="fw-semibold">{{ $item->nama_departemen }}</span>
                    <span class="fw-bold text-primary" style="font-size: 14px">
                        Rp {{ number_format($item->total_dana, 0, ',', '.') }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>


</div>

@endif


</div>

<script>
    // Jam dan tanggal real-time
    function updateDateTime() {
        const now = new Date();
        const optionsDate = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        document.getElementById('current-date').textContent = now.toLocaleDateString('id-ID', optionsDate);
        document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID');
    }
    setInterval(updateDateTime, 1000);
    updateDateTime();
</script>

@endsection
