<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lupa Password | ESC Proposal</title>
  
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
      background: linear-gradient(135deg,rgb(175, 30, 173) 0%,rgb(51, 14, 46) 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
    }

    .auth-card {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.25);
      width: 100%;
      max-width: 480px;
      overflow: hidden;
    }

    .card-header {
      background: linear-gradient(135deg,rgb(175, 30, 173) 0%,rgb(51, 14, 46) 100%);
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
      padding: 14px 16px 14px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      color: #1e293b;
      transition: all 0.3s ease;
      background-color: #f8fafc;
    }

    .form-control:focus {
      outline: none;
      border-color:rgb(157, 45, 149);
      box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
      background-color: white;
    }

    .form-control::placeholder {
      color: #94a3b8;
    }

    .btn-primary {
      width: 100%;
      padding: 14px;
      background: linear-gradient(135deg,rgb(175, 30, 173) 0%,rgb(51, 14, 46) 100%);
      border: none;
      border-radius: 12px;
      color: white;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(30, 64, 175, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(30, 64, 175, 0.4);
    }

    .btn-primary:active {
      transform: translateY(0);
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 24px;
      color: #3b82f6;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .back-link:hover {
      color: #1e40af;
      text-decoration: underline;
    }

    .alert {
      padding: 16px 20px;
      border-radius: 12px;
      margin-bottom: 24px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .alert-error {
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
        padding: 12px 16px 12px 48px;
        font-size: 15px;
      }
      
      .btn-primary {
        padding: 12px;
        font-size: 15px;
      }
    }
  </style>
</head>

<body>
  <div class="auth-card">
    <div class="card-header">
      <h1>Lupa Password?</h1>
      <p>Masukkan email dan nomor WhatsApp Anda untuk menerima link reset password</p>
    </div>
    
    <div class="card-body">
      @if (session('error'))
        <div class="alert alert-error">
          <i class="bi bi-exclamation-circle-fill"></i>
          {{ session('error') }}
        </div>
      @endif

      @if (session('success'))
        <div class="alert alert-success">
          <i class="bi bi-check-circle-fill"></i>
          {{ session('success') }}
        </div>
      @endif

      <form action="{{ route('admin.forgot.send') }}" method="POST">
        @csrf

        <div class="form-group">
          <label class="form-label">Email</label>
          <div class="input-with-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" class="form-control" placeholder="Masukkan email yang terdaftar" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Nomor WhatsApp</label>
          <div class="input-with-icon">
            <i class="bi bi-whatsapp"></i>
            <input type="text" name="nohp" class="form-control" placeholder="Masukkan nomor WhatsApp aktif" required>
          </div>
        </div>

        <button class="btn-primary" type="submit">
          Kirim Link Reset Password
        </button>
      </form>

      <a class="back-link" href="{{ route('admin.login') }}">
        <i class="bi bi-arrow-left me-1"></i>Kembali ke Login
      </a>
    </div>
  </div>
</body>
</html>