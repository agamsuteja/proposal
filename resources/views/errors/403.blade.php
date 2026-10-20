@extends('backend.layouts.app')

@section('title', 'Akses Ditolak')

@section('content')
<div class="container text-center mt-5">
    <h1 class="display-1 text-danger fw-bold">403</h1>
    <p class="lead mt-3">Akses ditolak atau sesi Anda telah berakhir.</p>
    <a href="{{ route('admin.login') }}" class="btn btn-primary mt-3">
        Login Kembali
    </a>
</div>
@endsection
