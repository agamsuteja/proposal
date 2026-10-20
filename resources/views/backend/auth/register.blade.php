<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Register | ESC Proposal</title>
  
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  
  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    /* ✅ PERBAIKAN: GANTI DARI height:100% KE min-height:100vh */
    body {
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      overflow-x: hidden;
    }

    .auth-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
      width: 100%;
      max-width: 480px;
      overflow: hidden;
      margin: 20px 0; /* ✅ Tambahkan margin untuk mobile */
    }

    .card-header {
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
      padding: 40px 30px 30px;
      text-align: center;
      color: white;
    }

    .card-header h1 {
      font-weight: 700;
      font-size: 28px;
      margin-bottom: 8px;
      letter-spacing: -0.5px;
    }

    .card-header p {
      font-weight: 400;
      font-size: 16px;
      opacity: 0.9;
      line-height: 1.5;
    }

    .card-body {
      padding: 35px;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: #1e293b;
      margin-bottom: 8px;
      font-size: 14px;
    }

    .input-with-icon {
      position: relative;
    }

    .input-with-icon i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: #64748b;
      font-size: 18px;
    }

    .form-control, .form-select {
      width: 100%;
      padding: 12px 16px 12px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      color: #1e293b;
      transition: all 0.3s ease;
      background-color: #f8fafc;
    }

    .form-control:focus, .form-select:focus {
      outline: none;
      border-color: #af1ebd;
      box-shadow: 0 0 0 3px rgba(175, 30, 189, 0.1);
      background-color: white;
    }

    .form-control::placeholder, .form-select::placeholder {
      color: #94a3b8;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
      border: none;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(175, 30, 173, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(175, 30, 173, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .login-link {
      display: block;
      text-align: center;
      margin-top: 20px;
      color: #af1ebd;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .login-link:hover {
      color: #510c2a;
      text-decoration: underline;
    }

    .alert {
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 20px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert-danger {
      background-color: #fee2e2;
      color: #b91c1c;
      border-left: 4px solid #ef4444;
    }

    .alert-success {
      background-color: #dcfce7;
      color: #166534;
      border-left: 4px solid #22c55e;
    }

    .alert i {
      font-size: 20px;
    }

    .alert ul {
      margin: 0;
      padding-left: 20px;
    }

    .alert li {
      margin-bottom: 4px;
    }

    .alert li:last-child {
      margin-bottom: 0;
    }

    @media (max-width: 576px) {
      .auth-card {
        border-radius: 16px;
        margin: 10px 0; /* ✅ Kurangi margin di mobile */
      }
      
      .card-header {
        padding: 30px 20px 25px;
      }
      
      .card-header h1 {
        font-size: 24px;
      }
      
      .card-header p {
        font-size: 14px;
      }
      
      .card-body {
        padding: 25px 20px;
      }
      
      .form-control, .form-select {
        padding: 10px 16px 10px 48px;
        font-size: 15px;
      }
      
      .btn-primary {
        padding: 12px;
        font-size: 15px;
      }
      
      .login-link {
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  @if ($errors->any())
  <div class="alert alert-danger">
    <i class="bi bi-exclamation-circle-fill"></i>
    <ul>
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
    </ul>
  </div>
  @endif

  <div class="auth-card">
    <div class="card-header">
      <h1>Register</h1>
      <p>Buat akun dengan nomor WhatsApp (verifikasi via OTP)</p>
    </div>
    
    <div class="card-body">
      @if(session('error')) 
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle-fill"></i>
          {{ session('error') }}
        </div> 
      @endif
      
      @if(session('success')) 
        <div class="alert alert-success">
          <i class="bi bi-check-circle-fill"></i>
          {{ session('success') }}
        </div> 
      @endif

      <form action="{{ route('admin.register.submit') }}" method="POST">
        @csrf

        <div class="form-group">
          <label class="form-label">Nama</label>
          <div class="input-with-icon">
            <i class="bi bi-person"></i>
            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-with-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">No. HP (WhatsApp)</label>
          <div class="input-with-icon">
            <i class="bi bi-whatsapp"></i>
            <input type="text" name="nohp" class="form-control" value="{{ old('nohp') }}" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Konfirmasi Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock-fill"></i>
            <input type="password" name="password_confirmation" class="form-control" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Role</label>
          <div class="input-with-icon">
            <i class="bi bi-shield"></i>
            <select name="role" class="form-select" required>
              <option value="">-- Pilih Role --</option>
              @foreach($roles as $r)
                <option value="{{ $r }}" {{ old('role') == $r ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$r)) }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Departemen (jika berlaku)</label>
          <div class="input-with-icon">
            <i class="bi bi-building"></i>
            <select name="departemen_id" class="form-select">
              <option value="">-- Pilih Departemen --</option>
              @foreach($departemens as $d)
                <option value="{{ $d->id }}" {{ old('departemen_id') == $d->id ? 'selected' : '' }}>{{ $d->nama_departemen }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Grup (jika berlaku)</label>
          <div class="input-with-icon">
            <i class="bi bi-collection"></i>
            <select name="grup_id" class="form-select">
              <option value="">-- Pilih Grup --</option>
              @foreach($grups as $g)
                <option value="{{ $g->id }}" {{ old('grup_id') == $g->id ? 'selected' : '' }}>{{ $g->nama_grup }}</option>
              @endforeach
            </select>
          </div>
        </div>

        <button class="btn-primary" type="submit">Daftar & Kirim OTP</button>
      </form>

      <a class="login-link" href="{{ route('admin.login') }}">
        <i class="bi bi-arrow-left me-1"></i>Sudah punya akun? Login
      </a>
    </div>
  </div>
</body>
</html>