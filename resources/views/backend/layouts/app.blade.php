<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="apple-touch-icon" sizes="76x76" href="{{ asset('assets/img/logo1.jpeg') }}">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/esc10.png') }}">
  <title>@yield('title') | Admin</title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet" />
  <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet" />
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.0.0') }}" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

</head>



{{-- <body class="g-sidenav-show bg-gray-200"> --}}
<body class="d-flex flex-column min-vh-100">

  @include('backend.layouts.sidebar')

  <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg">
    @include('backend.layouts.navbar')

    <div class="container-fluid py-4">
      @yield('content')
      
    </div>
    @include('backend.layouts.footer')
  </main>

  <style>
    html, body { max-width:100%; overflow-x:clip; }
    #navbarBlur .container-fluid { min-width:0; flex-wrap:wrap; gap:.5rem; }
    #navbarBlur .d-flex { min-width:0; }
    #navbarBlur .text-end { max-width:150px; overflow:hidden; text-overflow:ellipsis; }
    @media(max-width:575px) { #navbarBlur .badge { font-size:10px; padding:.5rem!important; } }
    .form-control, .form-select {
        border: 1px solid #ced4da !important;  /* garis abu default bootstrap */
        box-shadow: none !important;
    }
    .form-control:focus, .form-select:focus {
        border-color: #198754 !important;  /* hijau bootstrap saat fokus */
        box-shadow: 0 0 0 0.15rem rgba(25, 135, 84, .25) !important;
    }
</style>


    <!-- JS Files -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/smooth-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/chartjs.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/js/material-dashboard.min.js?v=3.0.0') }}"></script> --}}
    <!-- Tambahkan ini -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  
  
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script src="{{ asset('assets/js/material-dashboard.min.js?v=3.0.0') }}"></script>


  <script>
    document.addEventListener('DOMContentLoaded', function () {
        const sidebar = document.getElementById('sidenav-main');
        const toggleButton = document.getElementById('toggleSidebar');
    
        // Buat overlay gelap
        const overlay = document.createElement('div');
        overlay.classList.add('sidebar-overlay');
        document.body.appendChild(overlay);
    
        // Klik tombol hamburger
        toggleButton.addEventListener('click', () => {
            sidebar.classList.toggle('show');
            overlay.classList.toggle('active');
        });
    
        // Klik di luar sidebar
        overlay.addEventListener('click', () => {
            sidebar.classList.remove('show');
            overlay.classList.remove('active');
        });
    });
    </script>
    
</body>

</html>