<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Pengembalian Dana</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 20px;
        }

        .header {
            text-align: center;
            margin-bottom: 10px;
        }

        .header h2 {
            margin: 0;
        }

        .header p {
            margin: 2px 0;
            font-size: 11px;
        }

        .line {
            border-top: 2px solid #000;
            margin: 10px 0 15px 0;
        }

        .info {
            margin-bottom: 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th {
            background-color: #eaeaea;
            border: 1px solid #000;
            padding: 6px;
            font-weight: bold;
        }

        td {
            border: 1px solid #000;
            padding: 6px;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }

        .total-box {
            margin-top: 15px;
            width: 100%;
        }

        .total-box td {
            border: none;
            padding: 5px;
        }

        .footer {
            margin-top: 40px;
            width: 100%;
        }

        .ttd {
            width: 200px;
            text-align: center;
            float: right;
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="header">
        <h2>LAPORAN PENGEMBALIAN DANA</h2>
        <p>Sistem Pengelolaan Proposal</p>
        <p>{{ now()->format('d F Y') }}</p>
    </div>

    <div class="line"></div>

    <!-- FILTER INFO -->
    <div class="info">
        <strong>Status :</strong>
        {{ $request->status ?? 'Semua' }}
        <br>
        <strong>Periode :</strong>
        {{ $request->tanggal_awal ?? '-' }}
        s/d
        {{ $request->tanggal_akhir ?? '-' }}
    </div>

    <!-- TABLE -->
    <table>
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Proposal</th>
                <th width="15%">Pemohon</th>
                <th width="15%">Rekening</th>
                <th width="15%">Jumlah</th>
                <th width="10%">Status</th>
                <th width="10%">Tanggal</th>
            </tr>
        </thead>
        <tbody>
            @forelse($data as $item)
            <tr>
                <td class="text-center">{{ $loop->iteration }}</td>
                <td>{{ $item->proposal->judul ?? '-' }}</td>
                <td>{{ $item->proposal->user->name ?? '-' }}</td>
                <td>{{ $item->rekening->nama_rekening ?? '-' }}</td>
                <td class="text-right">
                    Rp {{ number_format($item->jumlah,0,',','.') }}
                </td>
                <td class="text-center">
                    {{ ucfirst($item->status) }}
                </td>
                <td class="text-center">
                    {{ $item->created_at->format('d-m-Y') }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="7" class="text-center">
                    Tidak ada data
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <!-- TOTAL -->
    <table class="total-box">
        <tr>
            <td width="70%"></td>
            <td width="30%" class="text-right">
                <strong>Total Dana Disetujui :</strong><br>
                <span style="font-size:14px;">
                    Rp {{ number_format($totalDiterima,0,',','.') }}
                </span>
            </td>
        </tr>
    </table>

    <!-- FOOTER -->
    <div class="footer">
        <div class="ttd">
            <p>Mengetahui,</p>
            <br><br><br>
            <p><strong>Bagian Keuangan</strong></p>
        </div>
    </div>

</body>
</html>
