@extends('backend.layouts.app')

@section('content')

<style>
/* ============================================================
   PENGEMBALIAN DANA SHOW — DESIGN SYSTEM (matches proposal)
   ============================================================ */
.pds-wrap { font-family:'Nunito',system-ui,sans-serif; color:#1a1a1a; padding:1.5rem 0 3rem }

/* ── Topbar ── */
.pds-topbar { background:linear-gradient(135deg,#7c3aed 0%,#4c1d95 100%); border-radius:14px; padding:1.1rem 1.4rem; display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; flex-wrap:wrap; gap:.6rem }
.pds-topbar h1 { color:#fff; font-size:15px; font-weight:700; margin:0; display:flex; align-items:center; gap:7px }
.pds-back-btn { background:rgba(255,255,255,.15); color:#fff; border:1px solid rgba(255,255,255,.3); border-radius:9px; padding:7px 16px; font-size:12.5px; font-weight:700; cursor:pointer; display:inline-flex; align-items:center; gap:6px; text-decoration:none; transition:all .15s }
.pds-back-btn:hover { background:rgba(255,255,255,.25); color:#fff; text-decoration:none }

/* ── Stat Row ── */
.pds-stats { display:grid; grid-template-columns:repeat(3,1fr); gap:.875rem; margin-bottom:1.25rem }
@media(max-width:768px){ .pds-stats { grid-template-columns:1fr } }
.pds-stat { background:#fff; border:0.5px solid #e5e7eb; border-radius:13px; padding:.875rem 1rem; border-left:3px solid transparent; box-shadow:0 1px 3px rgba(0,0,0,.04) }
.pds-stat.purple { border-left-color:#7c3aed }
.pds-stat.green  { border-left-color:#10b981 }
.pds-stat.blue   { border-left-color:#3b82f6 }
.pds-stat .s-icon { font-size:1.1rem; margin-bottom:.4rem }
.pds-stat .s-icon.purple { color:#7c3aed }
.pds-stat .s-icon.green  { color:#10b981 }
.pds-stat .s-icon.blue   { color:#3b82f6 }
.pds-stat .s-label { font-size:10.5px; font-weight:600; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:6px }
.pds-stat .s-value { font-size:18px; font-weight:700; color:#111; line-height:1.2 }
.pds-stat .s-value.sm { font-size:14px }

/* ── Badges ── */
.pds-badge { font-size:11px; font-weight:700; border-radius:20px; padding:4px 12px; white-space:nowrap; display:inline-flex; align-items:center; gap:5px }
.b-amber  { background:#fef3c7; color:#92400e }
.b-green  { background:#d1fae5; color:#065f46 }
.b-red    { background:#fee2e2; color:#991b1b }
.b-purple { background:#ede9fe; color:#5b21b6 }

/* ── Detail Card ── */
.pds-card { background:#fff; border:0.5px solid #e5e7eb; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04); margin-bottom:1rem }
.pds-card-header { background:#f9fafb; border-bottom:0.5px solid #e5e7eb; padding:.75rem 1.25rem; display:flex; align-items:center; gap:7px }
.pds-card-header i { color:#7c3aed; font-size:13px }
.pds-card-header span { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#374151 }

/* ── Detail Table ── */
.pds-table { width:100%; border-collapse:collapse; font-size:12.5px }
.pds-table tr { border-bottom:0.5px solid #f3f4f6; transition:background .12s }
.pds-table tr:last-child { border-bottom:none }
.pds-table tr:hover td, .pds-table tr:hover th { background:#faf8ff }
.pds-table th { padding:11px 14px 11px 1.25rem; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; width:28%; white-space:nowrap; vertical-align:top; padding-top:13px }
.pds-table td { padding:11px 14px; color:#374151; vertical-align:middle }
.pds-table .td-main { font-weight:700; color:#111 }
.pds-table .td-amount { font-weight:700; color:#059669; font-size:14px }

/* ── Note box ── */
.pds-note { background:#eff6ff; border:0.5px solid #bfdbfe; border-radius:8px; padding:.625rem .875rem; font-size:12.5px; color:#1e40af; line-height:1.55 }

/* ── Metode tag ── */
.pds-metode { font-size:10.5px; font-weight:700; border-radius:5px; padding:3px 10px; background:#ede9fe; color:#5b21b6; display:inline-block; text-transform:uppercase }

/* ── Bukti button ── */
.pds-bukti-btn { display:inline-flex; align-items:center; gap:5px; padding:6px 14px; border-radius:8px; background:#ede9fe; color:#5b21b6; font-size:12px; font-weight:700; text-decoration:none; transition:opacity .15s }
.pds-bukti-btn:hover { opacity:.8; color:#5b21b6 }

/* ── Approval Card ── */
.pds-approval { background:#fff; border:0.5px solid #fde68a; border-radius:14px; overflow:hidden; box-shadow:0 1px 4px rgba(0,0,0,.04) }
.pds-approval-header { background:linear-gradient(90deg,#fef3c7,#fef9ee); border-bottom:0.5px solid #fde68a; padding:.75rem 1.25rem; display:flex; align-items:center; gap:7px }
.pds-approval-header i { color:#d97706 }
.pds-approval-header span { font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.06em; color:#92400e }
.pds-approval-body { padding:1.25rem }

/* ── Approval alert ── */
.pds-warn { background:#fffbeb; border:0.5px solid #fde68a; border-radius:9px; padding:.75rem 1rem; font-size:12.5px; color:#92400e; display:flex; align-items:flex-start; gap:8px; margin-bottom:1.1rem; line-height:1.5 }

/* ── Select ── */
.pds-field label { display:block; font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:#6b7280; margin-bottom:5px }
.pds-select { width:100%; background:#f9fafb; border:0.5px solid #e5e7eb; border-radius:9px; padding:9px 12px; color:#374151; font-family:'Nunito',system-ui,sans-serif; font-size:13px; outline:none; appearance:none; -webkit-appearance:none; background-image:url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%236b7280' d='M6 8L1 3h10z'/%3E%3C/svg%3E"); background-repeat:no-repeat; background-position:right .875rem center; padding-right:2.5rem; transition:border-color .15s,box-shadow .15s; margin-bottom:.35rem }
.pds-select:focus { border-color:#7c3aed; box-shadow:0 0 0 3px rgba(124,58,237,.08); background:#fff }
.pds-select-hint { font-size:11px; color:#9ca3af; margin-bottom:1rem }

/* ── Action buttons ── */
.pds-btn { display:flex; align-items:center; justify-content:center; gap:6px; width:100%; padding:10px 1.5rem; border-radius:9px; font-family:'Nunito',system-ui,sans-serif; font-size:13px; font-weight:700; cursor:pointer; border:none; transition:opacity .15s,transform .1s; text-decoration:none }
.pds-btn:hover { opacity:.88; transform:translateY(-1px) }
.pds-btn-approve { background:linear-gradient(135deg,#10b981,#059669); color:#fff; margin-bottom:.875rem }
.pds-btn-reject { background:#fff; border:0.5px solid #fecaca; color:#991b1b }
.pds-btn-reject:hover { background:#fef2f2 }

.pds-divider { display:flex; align-items:center; gap:.75rem; margin:.875rem 0; color:#9ca3af; font-size:11px; font-weight:600 }
.pds-divider::before,.pds-divider::after { content:''; flex:1; height:0.5px; background:#e5e7eb }
</style>

<div class="pds-wrap container-fluid">

    {{-- ═══ TOPBAR ═══ --}}
    <div class="pds-topbar">
        <h1>
            <i class="fas fa-file-invoice-dollar"></i>
            Detail Pengembalian Dana
        </h1>
        <a href="{{ url()->previous() }}" class="pds-back-btn">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    {{-- ═══ STAT ROW ═══ --}}
    <div class="pds-stats">
        <div class="pds-stat purple">
            <div class="s-icon purple"><i class="fas fa-info-circle"></i></div>
            <div class="s-label">Status Pengembalian</div>
            <div>
                @if($data->status === 'pending')
                    <span class="pds-badge b-amber"><i class="fas fa-clock"></i> Pending</span>
                @elseif($data->status === 'diterima')
                    <span class="pds-badge b-green"><i class="fas fa-check-circle"></i> Diterima</span>
                @else
                    <span class="pds-badge b-red"><i class="fas fa-times-circle"></i> Ditolak</span>
                @endif
            </div>
        </div>

        <div class="pds-stat green">
            <div class="s-icon green"><i class="fas fa-coins"></i></div>
            <div class="s-label">Jumlah Pengembalian</div>
            <div class="s-value">Rp {{ number_format($data->jumlah, 0, ',', '.') }}</div>
        </div>

        <div class="pds-stat blue">
            <div class="s-icon blue"><i class="fas fa-exchange-alt"></i></div>
            <div class="s-label">Metode Pembayaran</div>
            <div class="s-value sm">
                <span class="pds-metode">{{ $data->metode }}</span>
            </div>
        </div>
    </div>

    {{-- ═══ DETAIL TABLE ═══ --}}
    <div class="pds-card">
        <div class="pds-card-header">
            <i class="fas fa-info-circle"></i>
            <span>Informasi Detail</span>
        </div>
        <table class="pds-table">
            <tbody>
                <tr>
                    <th><i class="fas fa-file-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Proposal</th>
                    <td class="td-main">{{ $data->proposal->judul }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-user fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Pemohon</th>
                    <td>{{ $data->proposal->user->name }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-building fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Grup</th>
                    <td>{{ $data->proposal->user->grup->nama_grup ?? '-' }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-money-bill-wave fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Jumlah Dana</th>
                    <td class="td-amount">Rp {{ number_format($data->jumlah, 0, ',', '.') }}</td>
                </tr>
                <tr>
                    <th><i class="fas fa-exchange-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Metode</th>
                    <td><span class="pds-metode">{{ strtoupper($data->metode) }}</span></td>
                </tr>
                <tr>
                    <th><i class="fas fa-sticky-note fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Catatan</th>
                    <td>
                        @if($data->catatan)
                            <div class="pds-note">{{ $data->catatan }}</div>
                        @else
                            <span style="color:#9ca3af;font-size:12px;font-style:italic">Tidak ada catatan</span>
                        @endif
                    </td>
                </tr>
                @if($data->bukti)
                <tr>
                    <th><i class="fas fa-image fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Bukti Pembayaran</th>
                    <td>
                        <a href="{{ route('admin.documents.show', ['path' => $data->bukti]) }}" target="_blank" class="pds-bukti-btn">
                            <i class="fas fa-eye"></i> Lihat Bukti
                        </a>
                    </td>
                </tr>
                @endif
                <tr>
                    <th><i class="fas fa-calendar-alt fa-sm" style="color:#c4b5fd;margin-right:5px"></i>Tanggal Diajukan</th>
                    <td style="font-size:12px;color:#6b7280">{{ $data->created_at->format('d F Y, H:i') }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- ═══ APPROVAL ACTIONS ═══ --}}
    @if(auth()->user()->role == 'keuangan' && $data->status == 'pending')
    <div class="pds-approval">
        <div class="pds-approval-header">
            <i class="fas fa-tasks"></i>
            <span>Aksi Persetujuan</span>
        </div>
        <div class="pds-approval-body">
            <div class="pds-warn">
                <i class="fas fa-exclamation-triangle" style="flex-shrink:0;margin-top:1px"></i>
                <div>
                    <strong>Pengembalian dana ini masih menunggu persetujuan.</strong><br>
                    Pilih rekening tujuan untuk menyetujui, atau tolak permintaan ini.
                </div>
            </div>

            <form action="{{ route('admin.pengembalian_dana.approve', $data->id) }}" method="POST">
                @csrf
                <div class="pds-field">
                    <label><i class="fas fa-university fa-sm" style="margin-right:3px"></i>Rekening Tujuan</label>
                    <select name="rekening_id" class="pds-select" required>
                        <option value="">— Pilih Rekening —</option>
                        @foreach($rekening as $rek)
                            <option value="{{ $rek->id }}">
                                {{ $rek->nama_bank }} — {{ $rek->nama_rekening }} ({{ $rek->nomor_rekening }})
                            </option>
                        @endforeach
                    </select>
                    <div class="pds-select-hint">Rekening yang dipilih akan menerima dana pengembalian</div>
                </div>
                <button type="submit" class="pds-btn pds-btn-approve">
                    <i class="fas fa-check-circle"></i> Approve & Tambah Saldo
                </button>
            </form>

            <div class="pds-divider">atau</div>

            <form action="{{ route('admin.pengembalian_dana.reject', $data->id) }}" method="POST">
                @csrf
                <button type="submit" class="pds-btn pds-btn-reject"
                        onclick="return confirm('Apakah Anda yakin ingin menolak pengembalian dana ini?')">
                    <i class="fas fa-times-circle"></i> Tolak Pengembalian
                </button>
            </form>
        </div>
    </div>
    @endif

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    tooltipTriggerList.map(function (el) { return new bootstrap.Tooltip(el) });
});
</script>

@endsection