<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title>Laporan Proposal {{ ucfirst($role) }}</title>
  <style>
    /* ====== PAGE SETTINGS ====== */
    @page { margin: 40px 40px 80px 40px; }
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
      color: #333;
    }

    /* ====== HEADER ====== */
    .header {
      text-align: center;
      border-bottom: 2px solid #444;
      padding-bottom: 8px;
      margin-bottom: 25px;
      position: relative;
    }
    .header img {
      position: absolute;
      left: 0;
      top: 0;
      width: 65px;
    }
    .header h2 {
      margin: 0;
      font-size: 18px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }
    .header p {
      margin: 0;
      font-size: 12px;
      color: #555;
    }

    /* ====== TITLE ====== */
    .report-title {
      text-align: center;
      font-size: 15px;
      margin: 15px 0 25px;
      font-weight: bold;
      color: #222;
      text-transform: uppercase;
    }

    /* ====== TABLE ====== */
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 5px;
    }
    th, td {
      border: 1px solid #999;
      padding: 6px 8px;
    }
    th {
      background-color: #f3f3f3;
      font-weight: bold;
      text-align: center;
      font-size: 11px;
    }
    td {
      font-size: 10.5px;
    }
    tr:nth-child(even) { background-color: #fafafa; }

    /* ====== SIGNATURE SECTION ====== */
    .signature-section {
      margin-top: 60px;
      width: 100%;
    }
    .signature-section table {
      border: none;
      width: 100%;
      text-align: center;
    }
    .signature-section td {
      border: none;
      vertical-align: top;
      padding-top: 40px;
    }
    .signature-title {
      font-weight: bold;
      text-transform: capitalize;
    }
    .signature-name {
      margin-top: 60px;
      font-weight: bold;
      text-decoration: underline;
    }
    .signature-position {
      font-size: 10px;
      color: #555;
      margin-top: -5px;
    }

    /* ====== FOOTER ====== */
    .footer {
      position: fixed;
      bottom: 0;
      left: 0;
      right: 0;
      text-align: right;
      font-size: 9px;
      color: #666;
      border-top: 1px solid #aaa;
      padding-top: 5px;
    }

    /* ====== UTILITY ====== */
    .text-right { text-align: right; }
    .text-center { text-align: center; }
    .text-left { text-align: left; }
  </style>
</head>
<body>

  <!-- HEADER -->
  <div class="header">
    <img src="{{ public_path('assets/img/esc10.png') }}" class="navbar-brand-img" width="26" height="50" alt="Logo Yayasan">
    {{-- <img src="{{ asset('assets/img/esc10.png') }}" class="navbar-brand-img" width="26" height="26" alt="main_logo"> --}}
    <h2>Gereja GBI El Shaddai</h2>
    <p>Jl. Prof M.Yamin No. 1A, Pontianak 40212 | Telp: (022) 1234567</p>
  </div>

  <!-- TITLE -->
  <div class="report-title">
    LAPORAN PROPOSAL {{ strtoupper($role) }}
  </div>

  <!-- TABLE -->
  <table>
    <thead>
      <tr>
        <th width="5%">No</th>
        <th width="22%">Judul Proposal</th>
        <th width="15%">Pemohon</th>
        <th width="15%">Departemen</th>
        <th width="12%">Status</th>
        <th width="16%">Total Anggaran</th>
        <th width="15%">Tanggal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $i => $item)
      <tr>
        <td class="text-center">{{ $i + 1 }}</td>
        <td class="text-left">{{ $item->judul ?? $item->proposal->judul }}</td>
        <td class="text-left">{{ $item->user->name ?? $item->proposal->user->name }}</td>
        <td class="text-left">{{ $item->departemen->nama_departemen ?? $item->proposal->departemen->nama_departemen ?? '-' }}</td>
        <td class="text-center">{{ strtoupper($item->status ?? 'DICAIRKAN') }}</td>
        <td class="text-right">Rp {{ number_format($item->total_anggaran ?? $item->jumlah_dana, 0, ',', '.') }}</td>
        <td class="text-center">{{ $item->created_at->format('d M Y') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <!-- SIGNATURE SECTION -->
  <div class="signature-section">
    <table>
      <tr>
        {{-- <td>
          <div class="signature-title">Dibuat Oleh</div>
          <div class="signature-name">___________________</div>
          <div class="signature-position">Bendahara</div>
        </td> --}}
        <td>
          <div class="signature-title">Diperiksa Oleh</div>
          <div class="signature-name">___________________</div>
          <div class="signature-position">Eiva Rade Sitio</div>
        </td>
        <td>
          <div class="signature-title">Disetujui Oleh</div>
          <div class="signature-name">___________________</div>
          <div class="signature-position">Pdt. Yehezkiel Wilan M.Th</div>
        </td>
      </tr>
    </table>
  </div>

  <!-- FOOTER -->
  <div class="footer">
    Dicetak pada {{ now()->format('d M Y H:i') }}
  </div>

</body>
</html>
