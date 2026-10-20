<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login | ESC Proposal</title>
  
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
      background: #ffffff;
      overflow-x: hidden;
    }

    .wrapper {
      display: flex;
      height: 100vh;
    }

    .left-panel {
      flex: 1;
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
      display: none;
      align-items: center;
      justify-content: center;
      padding: 40px;
      color: #fff;
      text-align: center;
    }

    .left-panel h1 {
      font-size: 42px;
      font-weight: 700;
      margin-bottom: 16px;
      letter-spacing: -0.5px;
    }

    .left-panel p {
      font-size: 18px;
      opacity: 0.9;
      line-height: 1.6;
      max-width: 500px;
      margin: 0 auto;
    }

    .logo-container {
      width: 80px;
      height: 80px;
      background: white;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin: 0 auto 24px;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
    }

    .logo-container img {
      width: 50px;
      height: 50px;
      object-fit: contain;
    }

    .right-panel {
      flex: 1;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 30px;
      background: #fff;
    }

    .form-box {
      width: 100%;
      max-width: 420px;
    }

    .form-header {
      text-align: center;
      margin-bottom: 32px;
    }

    .form-header h2 {
      font-size: 32px;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 8px;
    }

    .form-header p {
      font-size: 16px;
      color: #64748b;
      font-weight: 400;
    }

    .input-group {
      width: 100%;
      margin-bottom: 24px;
      position: relative;
    }

    .input-group label {
      display: block;
      font-size: 14px;
      margin-bottom: 8px;
      color: #334155;
      font-weight: 500;
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

    .input-with-icon input {
      width: 100%;
      padding: 12px 16px 12px 48px;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 16px;
      outline: none;
      color: #1e293b;
      transition: all 0.3s ease;
    }

    .input-with-icon input:focus {
      border-color: #af1ebd;
      box-shadow: 0 0 0 3px rgba(175, 30, 189, 0.1);
    }

    .forgot {
      text-align: right;
      font-size: 14px;
      margin-top: -10px;
      margin-bottom: 24px;
    }

    .forgot a {
      color: #af1ebd;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
    }

    .forgot a:hover {
      color: #510c2a;
      text-decoration: underline;
    }

    .btn-login {
      width: 100%;
      padding: 14px;
      border-radius: 12px;
      border: none;
      background: linear-gradient(135deg, rgb(175, 30, 173) 0%, rgb(51, 14, 46) 100%);
      color: #fff;
      font-size: 16px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(175, 30, 173, 0.3);
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(175, 30, 173, 0.4);
    }

    .error-message {
      background: #fee2e2;
      padding: 16px 20px;
      border-radius: 12px;
      color: #b91c1c;
      text-align: center;
      margin-bottom: 24px;
      font-weight: 500;
      border-left: 4px solid #ef4444;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .error-message i {
      font-size: 20px;
    }

    .register {
      text-align: center;
      margin-top: 24px;
      font-size: 14px;
      color: #64748b;
    }

    .register a {
      color: #af1ebd;
      text-decoration: none;
      font-weight: 600;
      transition: color 0.2s ease;
    }

    .register a:hover {
      color: #510c2a;
      text-decoration: underline;
    }

    @media (min-width: 900px) {
      .left-panel {
        display: flex;
      }
      .right-panel {
        flex: 0 0 480px;
      }
    }

    @media (max-width: 768px) {
      .wrapper {
        flex-direction: column;
      }
      
      .left-panel {
        display: flex;
        height: auto;
        padding: 60px 20px 40px;
      }
      
      .right-panel {
        padding: 40px 20px;
      }
      
      .form-box {
        max-width: 100%;
      }
      
      .left-panel h1 {
        font-size: 32px;
        margin-bottom: 12px;
      }
      
      .left-panel p {
        font-size: 16px;
      }
      
      .logo-container {
        width: 60px;
        height: 60px;
        margin-bottom: 20px;
      }
      
      .logo-container img {
        width: 40px;
        height: 40px;
      }
    }
  </style>
</head>

<body>

<div class="wrapper">
  <div class="left-panel">
    <div>
      <div class="logo-container">
        <img src="{{ asset('assets/img/esc10.png') }}" alt="ESC Logo">
      </div>
      <h1>ESC Proposal</h1>
      <p>Sistem Pengajuan Proposal yang Modern, Simple dan Terstruktur.</p>
    </div>
  </div>

  <div class="right-panel">
    <div class="form-box">
      <div class="form-header">
        <h2>Selamat Datang</h2>
        <p>Silahkan Login ke Akun Anda</p>
      </div>

      @if (session('error'))
        <div class="error-message">
          <i class="bi bi-exclamation-circle-fill"></i>
          {{ session('error') }}
        </div>
      @endif

      <form action="{{ route('admin.login.submit') }}" method="POST">
        @csrf

        <div class="input-group">
          <label>Email</label>
          <div class="input-with-icon">
            <i class="bi bi-envelope"></i>
            <input type="email" name="email" placeholder="Masukkan email Anda" required>
          </div>
        </div>

        <div class="input-group">
          <label>Password</label>
          <div class="input-with-icon">
            <i class="bi bi-lock"></i>
            <input type="password" name="password" placeholder="Masukkan password Anda" required>
          </div>
        </div>

        <div class="forgot">
          <a href="{{ route('admin.forgot.form') }}">Lupa password?</a>
        </div>

        <button class="btn-login" type="submit">Masuk</button>
      </form>

      <div class="register">
        Belum punya akun? 
        <a href="{{ route('admin.register.form') }}">Daftar Sekarang</a>
      </div>
    </div>
  </div>
</div>

</body>
</html>