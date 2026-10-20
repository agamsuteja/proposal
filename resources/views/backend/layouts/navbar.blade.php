<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-0 rounded-3 px-3 py-2" id="navbarBlur">
  <div class="container-fluid d-flex justify-content-between align-items-center">

    {{-- Tombol Hamburger untuk Mobile --}}
    <button class="btn btn-link d-xl-none d-block text-dark p-2 me-2" id="toggleSidebar">
      <i class="bi bi-list fs-3"></i>
    </button>

    {{-- LEFT: Breadcrumb (opsional, masih bisa kamu aktifkan lagi) --}}
    {{-- <div>
      <nav aria-label="breadcrumb">
        <ol class="breadcrumb bg-transparent mb-0 ps-0">
          <li class="breadcrumb-item text-sm">
            @if(Auth::check() && auth()->user()->role === 'admin')
              <a class="text-secondary text-decoration-none" href="#">Admin</a>
            @elseif(Auth::check() && auth()->user()->role === 'pengusaha')
              <a class="text-secondary text-decoration-none" href="#">Pengusaha</a>
            @elseif(Auth::check())
              <a class="text-secondary text-decoration-none" href="#">User</a>
            @endif
          </li>
          <li class="breadcrumb-item text-sm text-dark active fw-semibold" aria-current="page">
            @yield('title')
          </li>
        </ol>
      </nav>
    </div> --}}

    {{-- RIGHT: Notification + User Info --}}
    <div class="d-flex align-items-center gap-3">
      @if(Auth::check())
        <div class="d-flex align-items-center gap-2">
          <div class="text-end me-2">
            <div class="fw-semibold text-dark" id="navbar-greeting"></div>
            <small class="text-muted d-block">
              <i class="bi bi-person-circle me-1"></i>{{ Auth::user()->name }}
            </small>
          </div>
          <span class="badge bg-gradient-primary text-white px-3 py-2 text-capitalize">
            {{ Auth::user()->role }}
          </span>
        </div>
      @else
        {{-- Jika belum login --}}
        <div class="text-end">
          <a href="{{ route('admin.login') }}" class="btn btn-outline-primary btn-sm">Login</a>
        </div>
      @endif
    </div>

  </div>
</nav>
<!-- End Navbar -->

{{-- Script Ucapan --}}
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const hour = new Date().getHours();
    let greeting = "Selamat Malam";
    if (hour >= 4 && hour < 11) greeting = "Selamat Pagi";
    else if (hour >= 11 && hour < 15) greeting = "Selamat Siang";
    else if (hour >= 15 && hour < 18) greeting = "Selamat Sore";
    document.getElementById('navbar-greeting').textContent = greeting;
  });
</script>
