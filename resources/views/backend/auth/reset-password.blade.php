<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Reset Password | ESC Proposal</title>
  
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

    body, html {
      height: 100%;
      font-family: 'Poppins', sans-serif;
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
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
      margin-bottom: 24px;
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

    .form-control {
      width: 100%;
      padding: 12px 16px 12px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      color: #1e293b;
      transition: all 0.3s ease;
      background-color: #f8fafc;
    }

    .form-control:focus {
      outline: none;
      border-color: #af1ebd;
      box-shadow: 0 0 0 3px rgba(175, 30, 189, 0.1);
      background-color: white;
    }

    .form-control::placeholder {
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

    .alert {
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
      background-color: #fee2e2;
      color: #b91c1c;
      border-left: 4px solid #ef4444;
    }

    .alert i {
      font-size: 20px;
    }

    @media (max-width: 576px) {
      .auth-card {
        border-radius: 16px;
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
      
      .form-control {
        padding: 10px 16px 10px 48px;
        font-size: 15px;
      }
      
      .btn-primary {
        padding: 12px;
        font-size: 15px;
      }
    }
    html { display:block; min-height:100%; height:auto; padding:0; }
    body { width:100%; min-height:100vh; height:auto; padding:24px 16px; }
  </style>
</head>

<body>

  <div class="auth-card">
    <div class="card-header">
      <h1>Reset Password</h1>
      <p>Masukkan kode OTP dari WhatsApp dan buat password baru.</p>
    </div>
    
    <div class="card-body">
      @if (session('error'))
        <div class="alert">
          <i class="bi bi-exclamation-circle-fill"></i>
          {{ session('error') }}
        </div>
      @endif

      @if (session('success'))<div class="alert" style="background:#dcfce7;color:#166534;border-color:#22c55e" role="status">{{ session('success') }}</div>@endif

      @if($errors->any())<div class="alert alert-danger" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<form action="{{ route('admin.reset.submit') }}" method="POST">
        @csrf
        <div class="form-group">
          <label class="form-label" for="reset-email">Email terdaftar</label>
          <div class="input-with-icon"><i class="bi bi-envelope"></i><input id="reset-email" type="email" name="email" class="form-control" value="{{ $email }}" autocomplete="email" readonly required></div>
        </div>
        <div class="form-group">
          <label class="form-label" for="reset-otp">Kode OTP WhatsApp</label>
          <div class="input-with-icon"><i class="bi bi-shield-lock"></i><input id="reset-otp" type="text" name="otp" class="form-control" placeholder="6 digit kode OTP" inputmode="numeric" autocomplete="one-time-code" minlength="6" maxlength="6" pattern="[0-9]{6}" required></div>
          <p style="font-size:12px;color:#64748b;line-height:1.6;margin-top:8px">Berlaku 10 menit dan hanya sekali pakai. Jika meminta ulang, gunakan kode dari permintaan terbaru.</p>
        </div>

        <div class="form-group">
          <label class="form-label">Password Baru</label>
          <div class="input-with-icon">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" minlength="10" autocomplete="new-password" class="form-control" placeholder="Minimal 10 karakter" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Konfirmasi Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock-fill"></i>
            <input type="password" name="password_confirmation" minlength="10" autocomplete="new-password" class="form-control" placeholder="Ulangi password baru" required>
          </div>
        </div>

        <button class="btn-primary" type="submit">Reset Password</button>
      </form>
      <div style="display:flex;flex-direction:column;gap:12px;text-align:center;margin-top:24px;font-size:14px">
        <a href="{{ route('admin.forgot.form') }}">Belum menerima kode? Minta kode OTP baru</a>
        <a href="{{ route('admin.login') }}">Kembali ke Login</a>
      </div>
    </div>
  </div>

</body>
</html>