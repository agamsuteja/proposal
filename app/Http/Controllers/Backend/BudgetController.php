<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;
use App\Models\{ActivityLog, BudgetRequest, DepartmentBudget, Departemen};
use App\Services\DepartmentBudgetService;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    private function departments($user)
    {
        $query = Departemen::with('grup');
        if (in_array($user->role, ['admin', 'keuangan', 'gembala'])) return $query;
        if ($user->role === 'group_head') return $query->where('grup_id', $user->grup_id ?? 0);
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
        }
        // Include every department in visible groups when calculating group totals.
        $groupTotals = DepartmentBudget::join('departemen', 'departemen.id', '=', 'budget_departemen.departemen_id')
            ->where('tahun', $year)->whereIn('grup_id', $departments->pluck('grup_id'))
            ->selectRaw('grup_id, SUM(jumlah) as total')->groupBy('grup_id')->pluck('total', 'grup_id');
        $requests = BudgetRequest::with(['departemen.grup', 'user', 'approvals.approver', 'transfers.sumber'])
            ->whereIn('departemen_id', $departments->pluck('id'))->where('tahun', $year)->latest()->paginate(15)->withQueryString();
        return view('backend.budget.index', compact('year', 'departments', 'requests', 'groupTotals'));
    }

    public function save(Request $request, DepartmentBudgetService $service)
    {
        abort_unless($request->user()->role === 'keuangan' && $request->user()->is_active, 403);
        $data = $request->validate(['departemen_id' => 'required|integer|exists:departemen,id',
            'tahun' => 'required|integer|min:2000|max:2100', 'jumlah' => 'required|numeric|decimal:0,2|min:0|max:99999999999999.99']);
        $service->setBudget((int) $data['departemen_id'], (int) $data['tahun'], (string) $data['jumlah']);
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
}
