<table border="1" cellspacing="0" cellpadding="5">
    <thead>
      <tr style="background-color:#f1f1f1; text-align:center;">
        <th>No</th>
        <th>Judul Proposal</th>
        <th>Pemohon</th>
        <th>Departemen</th>
        <th>Status</th>
        <th>Total Anggaran</th>
        <th>Tanggal</th>
      </tr>
    </thead>
    <tbody>
      @foreach($data as $i => $item)
        <tr>
          <td>{{ $i+1 }}</td>
          <td>{{ $item->judul ?? $item->proposal->judul }}</td>
          <td>{{ $item->user->name ?? $item->proposal->user->name }}</td>
          <td>{{ $item->departemen->nama_departemen ?? $item->proposal->departemen->nama_departemen ?? '-' }}</td>
          <td>{{ strtoupper($item->status ?? 'DICAIRKAN') }}</td>
          <td>Rp {{ number_format($item->total_anggaran ?? $item->jumlah_dana, 0, ',', '.') }}</td>
          <td>{{ $item->created_at->format('d M Y') }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>
  