@if(Auth::check())
<aside class="sidenav navbar navbar-vertical navbar-expand-xs border-radius-lg fixed-start ms-2 bg-white my-2" id="sidenav-main">
  <div class="sidenav-header">
    <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none" id="iconSidenav"></i>
    <a class="navbar-brand px-4 py-3 m-0" href="{{ route(match(Auth::user()->role) { 'karyawan'=>'admin.proposal.index', 'departemen_head','group_head','gembala'=>'admin.approval.index', default=>'admin.dashboard' }) }}">
      <img src="{{ asset('assets/img/esc10.png') }}" class="navbar-brand-img" width="26" height="26" alt="main_logo">
      <span class="ms-1 text-sm text-dark fw-bold">ESC Proposal</span>
    </a>
  </div>

  <hr class="horizontal dark mt-0 mb-2">

  <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
    <ul class="navbar-nav">
      <li class="nav-item"><a class="nav-link text-dark {{ request()->routeIs('admin.budget.index') ? 'active bg-gradient-primary text-white' : '' }}" href="{{ route('admin.budget.index') }}"><i class="material-symbols-rounded">account_balance_wallet</i><span class="nav-link-text ms-1">Budget Departemen</span></a></li>
      <li class="nav-item"><a class="nav-link text-dark {{ request()->routeIs('admin.budget.requests') ? 'active bg-gradient-primary text-white' : '' }}" href="{{ route('admin.budget.requests') }}"><i class="material-symbols-rounded">add_card</i><span class="nav-link-text ms-1">Tambahan Budget</span></a></li>
      @if(in_array(Auth::user()->role, ['admin', 'keuangan']))
      <li class="nav-item"><a class="nav-link text-dark {{ request()->routeIs('admin.notifications.*') ? 'active bg-gradient-primary text-white' : '' }}" href="{{ route('admin.notifications.index') }}"><i class="material-symbols-rounded">notifications</i><span class="nav-link-text ms-1">Status Notifikasi</span></a></li>
      <li class="nav-item"><a class="nav-link text-dark {{ request()->routeIs('admin.activity.index') ? 'active bg-gradient-primary text-white' : '' }}" href="{{ route('admin.activity.index') }}"><i class="material-symbols-rounded">history</i><span class="nav-link-text ms-1">Log Aktivitas</span></a></li>
      @endif

      {{-- ================= DASHBOARD (Hanya Admin & Keuangan) ================= --}}
      @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'keuangan']))
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.dashboard') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.dashboard') }}">
          <i class="material-symbols-rounded opacity-5">dashboard</i>
          <span class="nav-link-text ms-1">Dashboard</span>
        </a>
      </li>
      <hr class="horizontal dark mt-2 mb-2">
      @endif

      {{-- ================= KARYAWAN: Menu Pengajuan Proposal ================= --}}
      @if(Auth::check() && Auth::user()->role === 'karyawan')
      <h6 class="ps-3 text-uppercase text-xs text-secondary font-weight-bolder opacity-6">
        Pengajuan Proposal
      </h6>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.proposal.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.proposal.index') }}">
          <i class="material-symbols-rounded opacity-5">send</i>
          <span class="nav-link-text ms-1">Ajukan Proposal</span>
        </a>
      </li>
      @endif

      {{-- ================= DEPT HEAD / GROUP HEAD / GEMBALA ================= --}}
      @if(Auth::check() && in_array(Auth::user()->role, ['departemen_head', 'group_head', 'gembala', 'keuangan']))
      <h6 class="ps-3 text-uppercase text-xs text-secondary font-weight-bolder opacity-6">
        Persetujuan Proposal
      </h6>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.approval.index') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.approval.index') }}">
          <i class="material-symbols-rounded opacity-5">checklist</i>
          <span class="nav-link-text ms-1">Persetujuan Proposal</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.approval.history') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.approval.history') }}">
          <i class="material-symbols-rounded opacity-5">history</i>
          <span class="nav-link-text ms-1">Riwayat Persetujuan</span>
        </a>
      </li>
      @endif

      {{-- ================= KEUANGAN ================= --}}
      @if(Auth::check() && Auth::user()->role === 'keuangan')
      <h6 class="ps-3 text-uppercase text-xs text-secondary font-weight-bolder opacity-6">
        Keuangan & Pencairan
      </h6>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.rekening.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.rekening.index') }}">
          <i class="material-symbols-rounded opacity-5">account_balance</i>
          <span class="nav-link-text ms-1">Rekening</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.pencairan.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.pencairan.index') }}">
          <i class="material-symbols-rounded opacity-5">payments</i>
          <span class="nav-link-text ms-1">Pencairan Dana</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.pengembalian_dana.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.pengembalian_dana.index') }}">
          <i class="material-symbols-rounded opacity-5">undo</i>
          <span class="nav-link-text ms-1">Pengembalian Dana</span>
        </a>
      </li>

      {{-- <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.approval.history') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.approval.history') }}">
          <i class="material-symbols-rounded opacity-5">history</i>
          <span class="nav-link-text ms-1">Riwayat Persetujuan</span>
        </a>
      </li> --}}
      @endif

      {{-- ================= ADMIN (Full Access) ================= --}}
      @if(Auth::check() && Auth::user()->role === 'admin')
      <h6 class="ps-3 text-uppercase text-xs text-secondary font-weight-bolder opacity-6">
        Menu Admin
      </h6>
      {{-- ================= USER PENDING ================= --}}
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.user.pending') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.user.pending') }}">
          <i class="material-symbols-rounded opacity-5">hourglass_top</i>
          <span class="nav-link-text ms-1">User Pending</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.departemen.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.departemen.index') }}">
          <i class="material-symbols-rounded opacity-5">apartment</i>
          <span class="nav-link-text ms-1">Departemen</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.grup.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.grup.index') }}">
          <i class="material-symbols-rounded opacity-5">groups</i>
          <span class="nav-link-text ms-1">Grup</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.user.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.user.index') }}">
          <i class="material-symbols-rounded opacity-5">people</i>
          <span class="nav-link-text ms-1">User</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.proposal.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.proposal.index') }}">
          <i class="material-symbols-rounded opacity-5">send</i>
          <span class="nav-link-text ms-1">Proposal</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.approval.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.approval.index') }}">
          <i class="material-symbols-rounded opacity-5">checklist</i>
          <span class="nav-link-text ms-1">Persetujuan Proposal</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.rekening.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.rekening.index') }}">
          <i class="material-symbols-rounded opacity-5">account_balance</i>
          <span class="nav-link-text ms-1">Rekening</span>
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link text-dark {{ request()->routeIs('admin.pencairan.*') ? 'active bg-gradient-primary text-white' : '' }}"
          href="{{ route('admin.pencairan.index') }}">
          <i class="material-symbols-rounded opacity-5">payments</i>
          <span class="nav-link-text ms-1">Pencairan Dana</span>
        </a>
      </li>
      @endif

      {{-- ================= LAPORAN ================= --}}
      @if(Auth::check() && in_array(Auth::user()->role, ['admin', 'keuangan', 'departemen_head', 'group_head', 'gembala']))
        <hr class="horizontal dark mt-3 mb-2">
        <h6 class="ps-3 text-uppercase text-xs text-secondary font-weight-bolder opacity-6">
          Laporan
        </h6>
        <li class="nav-item">
          <a class="nav-link text-dark {{ request()->routeIs('admin.laporan.index') ? 'active bg-gradient-primary text-white' : '' }}"
            href="{{ route('admin.laporan.index') }}">
            <i class="material-symbols-rounded opacity-5">bar_chart</i>
            <span class="nav-link-text ms-1">Laporan</span>
          </a>
        </li>
      @endif

    </ul>
  </div>

  {{-- ================= LOGOUT BUTTON ================= --}}
  <div class="sidenav-footer position-absolute w-100 bottom-0">
    <div class="mx-3">
      <form action="{{ route('admin.logout') }}" method="POST">
        @csrf
        <button type="submit" class="btn btn-outline-dark mt-4 w-100">Logout</button>
      </form>
    </div>
  </div>
</aside>
@endif
