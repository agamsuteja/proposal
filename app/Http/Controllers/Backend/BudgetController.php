<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\BudgetPeriod;
use App\Models\BudgetRequest;
use App\Models\BudgetTransfer;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\User;
use App\Services\DepartmentBudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    private function departments($user)
    {
        $query = Departemen::with('grup');
        if (in_array($user->role, ['admin', 'keuangan', 'gembala'])) {
            return $query;
        }
        if ($user->role === 'group_head') {
            return $query->where('grup_id', $user->grup_id ?? 0);
        }

        return $query->whereKey($user->departemen_id ?? 0);
    }

    public function index(Request $request, DepartmentBudgetService $service)
    {
        $request->validate(['tahun' => 'nullable|integer|min:2000|max:2100']);
        $year = (int) ($request->tahun ?? now()->year);
        $departments = $this->departments($request->user())->orderBy('nama_departemen')->get();
        $budgets = DepartmentBudget::where('tahun', $year)->get()->keyBy('departemen_id');
        foreach ($departments as $department) {
            $department->budget_cents = DepartmentBudgetService::cents($budgets->get($department->id)?->jumlah ?? 0);
            $department->used_cents = $service->used($department->id, $year);
            $department->reserved_cents = $service->reserved($department->id, $year);
            $department->available_cents = $department->budget_cents - $department->used_cents - $department->reserved_cents;
        }
        // Include every department in visible groups when calculating group totals.
        $groupTotals = DepartmentBudget::join('departemen', 'departemen.id', '=', 'budget_departemen.departemen_id')
            ->where('tahun', $year)->whereIn('grup_id', $departments->pluck('grup_id'))
            ->selectRaw('grup_id, SUM(jumlah) as total')->groupBy('grup_id')->pluck('total', 'grup_id');
        $requests = BudgetRequest::with(['departemen.grup', 'user', 'approvals.approver', 'transfers.sumber', 'versions.user', 'reversal.user'])
            ->whereIn('departemen_id', $departments->pluck('id'))->where('tahun', $year)->latest()->paginate(15)->withQueryString();
        $periodStatus = BudgetPeriod::whereKey($year)->value('status') ?? 'open';
        $approvers = $request->user()->role === 'admin' ? User::where('is_active', 1)->whereIn('role', ['departemen_head', 'group_head', 'gembala'])->orderBy('name')->get() : collect();

        return view('backend.budget.index', compact('year', 'departments', 'requests', 'groupTotals', 'periodStatus', 'approvers'));
    }

    public function save(Request $request, DepartmentBudgetService $service)
    {
        abort_unless($request->user()->role === 'keuangan' && $request->user()->is_active, 403);
        $data = $request->validate(['departemen_id' => 'required|integer|exists:departemen,id',
            'tahun' => 'required|integer|min:2000|max:2100', 'jumlah' => 'required|numeric|decimal:0,2|min:0|max:99999999999999.99', 'alasan' => 'required|string|min:5|max:1000']);
        $service->setBudget((int) $data['departemen_id'], (int) $data['tahun'], (string) $data['jumlah'], $data['alasan']);

        return back()->with('success', 'Budget departemen disimpan.');
    }

    public function store(Request $request, DepartmentBudgetService $service)
    {
        abort_unless($request->user()->is_active, 403);
        $data = $request->validate(['departemen_id' => 'required|integer', 'tahun' => 'required|integer|min:2000|max:2100',
            'jumlah' => 'required|numeric|decimal:0,2|min:0.01|max:99999999999999.99', 'alasan' => 'required|string|max:5000']);
        $department = $this->departments($request->user())->findOrFail($data['departemen_id']);
        $service->submit($request->user(), $department, (int) $data['tahun'], (string) $data['jumlah'], $data['alasan']);

        return back()->with('success', 'Pengajuan tambahan budget dikirim untuk approval.');
    }

    public function decide(Request $request, int $id, DepartmentBudgetService $service)
    {
        $data = $request->validate(['status' => 'required|in:disetujui,ditolak', 'catatan' => 'nullable|string|max:1000']);
        $service->decide($id, $request->user(), $data['status'], $data['catatan'] ?? null);

        return back()->with('success', 'Keputusan approval disimpan.');
    }

    public function fund(Request $request, int $id, DepartmentBudgetService $service)
    {
        abort_unless($request->user()->role === 'keuangan' && $request->user()->is_active, 403);
        // Empty source rows in the form are intentionally ignored.
        $request->merge(['sumber' => array_values(array_filter((array) $request->input('sumber', []),
            fn ($source) => is_array($source) && isset($source['jumlah']) && $source['jumlah'] !== '' && $source['jumlah'] !== null))]);
        $data = $request->validate(['sumber' => 'required|array|min:1|max:100', 'sumber.*.departemen_id' => 'required|integer|distinct|exists:departemen,id',
            'sumber.*.jumlah' => 'required|numeric|decimal:0,2|min:0.01|max:99999999999999.99']);
        $service->fund($id, $request->user(), $data['sumber']);

        return back()->with('success', 'Budget berhasil dipindahkan. Total budget grup tetap sama.');
    }

    public function logs(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'keuangan']) && $request->user()->is_active, 403);
        $request->validate(['action' => 'nullable|string|max:100', 'user_id' => 'nullable|integer',
            'tanggal_awal' => 'nullable|date', 'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_awal']);
        $logs = ActivityLog::with('user')->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))
            ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('tanggal_awal'), fn ($q) => $q->whereDate('created_at', '>=', $request->tanggal_awal))
            ->when($request->filled('tanggal_akhir'), fn ($q) => $q->whereDate('created_at', '<=', $request->tanggal_akhir))
            ->orderByDesc('id')->paginate(30)->withQueryString();

        return view('backend.budget.logs', compact('logs'));
    }

    public function revise(Request $request, int $id, DepartmentBudgetService $service)
    {
        $data = $request->validate(['jumlah' => 'required|numeric|decimal:0,2|min:0.01|max:99999999999999.99', 'alasan' => 'required|string|min:5|max:5000']);
        $service->revise($id, $request->user(), (string) $data['jumlah'], $data['alasan']);

        return back()->with('success', 'Revisi diajukan ulang dari tahap pertama.');
    }

    public function cancel(Request $request, int $id, DepartmentBudgetService $service)
    {
        $data = $request->validate(['alasan' => 'required|string|min:5|max:1000']);
        $service->cancel($id, $request->user(), $data['alasan']);

        return back()->with('success', 'Pengajuan dibatalkan dan alasannya tersimpan.');
    }

    public function reassign(Request $request, int $id, DepartmentBudgetService $service)
    {
        $data = $request->validate(['approver_id' => 'required|integer|exists:users,id', 'alasan' => 'required|string|min:5|max:1000']);
        $service->reassign($id, $request->user(), (int) $data['approver_id'], $data['alasan']);

        return back()->with('success', 'Approver dialihkan dengan jejak audit.');
    }

    public function reverse(Request $request, int $id, DepartmentBudgetService $service)
    {
        $data = $request->validate(['alasan' => 'required|string|min:5|max:1000']);
        $service->reverse($id, $request->user(), $data['alasan']);

        return back()->with('success', 'Transfer dibalik ke sumber awal dan riwayat tetap disimpan.');
    }

    public function period(Request $request, DepartmentBudgetService $service)
    {
        $data = $request->validate(['tahun' => 'required|integer|min:2000|max:2100', 'status' => 'required|in:open,closed', 'alasan' => 'required|string|min:5|max:1000']);
        $service->period((int) $data['tahun'], $data['status'], $request->user(), $data['alasan']);

        return back()->with('success', 'Status periode diperbarui.');
    }

    public function copyYear(Request $request, DepartmentBudgetService $service)
    {
        $data = $request->validate(['sumber' => 'required|integer|min:2000|max:2100', 'tujuan' => 'required|integer|min:2000|max:2100|different:sumber', 'alasan' => 'required|string|min:5|max:1000']);
        $service->copyYear((int) $data['sumber'], (int) $data['tujuan'], $request->user(), $data['alasan']);

        return back()->with('success', 'Alokasi disalin ke tahun tujuan tanpa menimpa data.');
    }

    public function export(Request $request, DepartmentBudgetService $service)
    {
        $request->validate(['tahun' => 'nullable|integer|min:2000|max:2100']);
        $year = (int) ($request->tahun ?? now()->year);
        $departments = $this->departments($request->user())->orderBy('nama_departemen')->get();

        return response()->streamDownload(function () use ($departments, $year, $service) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Tahun', 'Grup', 'Departemen', 'Alokasi awal', 'Transfer masuk', 'Transfer keluar', 'Alokasi efektif', 'Terpakai', 'Dicadangkan', 'Tersedia']);
            foreach ($departments as $d) {
                $current = DepartmentBudget::where('departemen_id', $d->id)->where('tahun', $year)->value('jumlah') ?? 0;
                $net = BudgetTransfer::where('tahun', $year)->whereHas('request', fn ($q) => $q->where('status', 'ditransfer'));
                $in = DepartmentBudgetService::cents((clone $net)->where('tujuan_id', $d->id)->sum('jumlah'));
                $outgoing = DepartmentBudgetService::cents((clone $net)->where('sumber_id', $d->id)->sum('jumlah'));
                $safe = fn ($v) => preg_match('/^[=+@-]/u', (string) $v) ? "'".$v : $v;
                fputcsv($out, [$year, $safe($d->grup?->nama_grup), $safe($d->nama_departemen), DepartmentBudgetService::amount(DepartmentBudgetService::cents($current) - $in + $outgoing), DepartmentBudgetService::amount($in), DepartmentBudgetService::amount($outgoing), $current, DepartmentBudgetService::amount($service->used($d->id, $year)), DepartmentBudgetService::amount($service->reserved($d->id, $year)), DepartmentBudgetService::amount($service->available($d->id, $year))]);
            }fclose($out);
        }, 'budget-'.$year.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportLogs(Request $request)
    {
        abort_unless(in_array($request->user()->role, ['admin', 'keuangan']), 403);
        $request->validate(['action' => 'nullable|string|max:100', 'user_id' => 'nullable|integer', 'tanggal_awal' => 'nullable|date', 'tanggal_akhir' => 'nullable|date|after_or_equal:tanggal_awal']);
        $query = ActivityLog::with('user')->when($request->filled('action'), fn ($q) => $q->where('action', $request->action))->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
            ->when($request->filled('tanggal_awal'), fn ($q) => $q->whereDate('created_at', '>=', $request->tanggal_awal))->when($request->filled('tanggal_akhir'), fn ($q) => $q->whereDate('created_at', '<=', $request->tanggal_akhir));

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Waktu', 'Pengguna', 'Aksi', 'Objek', 'ID', 'Halaman', 'HTTP', 'Sebelum', 'Sesudah']);
            foreach ($query->orderBy('id')->lazyById(500) as $log) {
                $row = [$log->created_at, $log->user?->name, $log->action, $log->subject_type, $log->subject_id, $log->route, $log->response_status, json_encode($log->before, JSON_UNESCAPED_UNICODE), json_encode($log->after, JSON_UNESCAPED_UNICODE)];
                fputcsv($out,array_map(fn ($v) => preg_match('/^[=+@-]/u',(string) $v) ? "'".$v : $v,$row));
            }fclose($out);
        }, 'audit-'.now()->format('Ymd').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
