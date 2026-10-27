<?php

use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Backend\ApprovalProposalController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartemenController;
use App\Http\Controllers\Backend\GrupController;
use App\Http\Controllers\Backend\KategoriController;
use App\Http\Controllers\Backend\PencairanDanaController;
use App\Http\Controllers\Backend\PengembalianDanaController;
use App\Http\Controllers\Backend\PertanyaanController;
use App\Http\Controllers\Backend\ProposalController;
use App\Http\Controllers\Backend\RekeningController;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Route;

/*
 * |--------------------------------------------------------------------------
 * | Default redirect ke login admin
 * |--------------------------------------------------------------------------
 */
Route::get('/', function () {
    return redirect()->route('admin.login');
});

Route::get('login', function () {
    return redirect()->route('admin.login');
})->name('login');

/*
 * |--------------------------------------------------------------------------
 * | ADMIN
 * |--------------------------------------------------------------------------
 */
Route::prefix('admin')->name('admin.')->group(function () {
    // ── Auth (tidak perlu login) ──────────────────────────────────────────
    Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');

    Route::get('/forgot-password', [AdminAuthController::class, 'forgotPasswordForm'])->name('forgot.form');
    Route::post('/forgot-password', [AdminAuthController::class, 'forgotPasswordSend'])->name('forgot.send');

    Route::get('/reset-password/{token}', [AdminAuthController::class, 'resetPasswordForm'])->name('reset.form');
    Route::post('/reset-password', [AdminAuthController::class, 'resetPasswordSubmit'])->name('reset.submit');

    Route::get('/register', [AdminAuthController::class, 'showRegisterForm'])->name('register.form');
    Route::post('/register', [AdminAuthController::class, 'register'])->name('register.submit');

    Route::get('/register/verify', [AdminAuthController::class, 'showRegisterVerifyForm'])->name('register.verify.form');
    Route::post('/register/verify', [AdminAuthController::class, 'registerVerify'])->name('register.verify.submit');

    Route::get('/user-pending', [AdminAuthController::class, 'pendingUsers'])->name('user.pending');
    Route::post('/user-approve/{id}', [AdminAuthController::class, 'approveUser'])->name('user.approve');

    // ── Laporan (tanpa auth — sesuaikan jika perlu dipindah ke dalam auth) ──
    Route::get('/laporan', [\App\Http\Controllers\Backend\LaporanController::class, 'index'])->name('laporan.index');
    Route::get('/laporan/export', [\App\Http\Controllers\Backend\LaporanController::class, 'export'])->name('laporan.export');
    Route::get('/laporan/export/excel', [\App\Http\Controllers\Backend\LaporanController::class, 'exportExcel'])->name('laporan.export.excel');
    Route::get('/laporan/export/pdf', [\App\Http\Controllers\Backend\LaporanController::class, 'exportPdf'])->name('laporan.export.pdf');

    // ── Protected (perlu login) ───────────────────────────────────────────
    Route::middleware(['auth'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/data-pencairan', [DashboardController::class, 'dataPencairan'])->name('dashboard.dataPencairan');

        // Resources
        Route::resource('proposal', ProposalController::class);
        Route::resource('departemen', DepartemenController::class)->parameters(['departemen' => 'departemen']);
        Route::resource('grup', GrupController::class);
        Route::resource('user', UserController::class);
        Route::resource('rekening', RekeningController::class);
        Route::resource('pencairan', PencairanDanaController::class);

        // Approval resource (index + update saja dari resource)
        Route::resource('approval', ApprovalProposalController::class)->only(['index', 'update']);

        // ✅ Approval tambahan — urutan penting: 'riwayat' & 'notif-keuangan' HARUS di atas '/{id}'
        Route::get('/approval/riwayat', [ApprovalProposalController::class, 'history'])->name('approval.history');

        Route::post('/approval/{id}/notif-keuangan', [ApprovalProposalController::class, 'notifKeuangan'])
            ->name('approval.notif-keuangan');

        Route::get('/approval/{id}', [ApprovalProposalController::class, 'show'])->name('approval.show');

        // Upload nota
        Route::post('/proposal/{id}/upload-nota', [ProposalController::class, 'uploadNota'])->name('proposal.uploadNota');

        // Pencairan show
        Route::get('/pencairan/{id}', [PencairanDanaController::class, 'show'])->name('pencairan.show');

        // Pengembalian Dana
        Route::prefix('pengembalian-dana')->name('pengembalian_dana.')->group(function () {
            Route::get('/', [PengembalianDanaController::class, 'index'])->name('index');
            Route::get('/create/{proposal}', [PengembalianDanaController::class, 'create'])->name('create');
            Route::post('/store', [PengembalianDanaController::class, 'store'])->name('store');
            Route::get('/export/pdf', [PengembalianDanaController::class, 'exportPdf'])->name('export');
            // approve & reject HARUS di atas /{id}
            Route::post('/approve/{id}', [PengembalianDanaController::class, 'approve'])->name('approve');
            Route::post('/reject/{id}', [PengembalianDanaController::class, 'reject'])->name('reject');
            Route::get('/{id}', [PengembalianDanaController::class, 'show'])->name('show');
        });

        // Maintenance
        Route::prefix('maintenance')->name('admin.maintenance.')->group(function () {
            Route::get('/', [MaintenanceProposalController::class, 'index'])->name('index');
            Route::get('/create', [MaintenanceProposalController::class, 'create'])->name('create');
            Route::post('/', [MaintenanceProposalController::class, 'store'])->name('store');
        });
    });  // end middleware auth
});  // end prefix admin

// Budget departemen dan pengajuan tambahan menggunakan alur approval yang sama.
Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::get('/budget', [\App\Http\Controllers\Backend\BudgetController::class, 'index'])->name('budget.index');
    Route::get('/budget/pengajuan', [\App\Http\Controllers\Backend\BudgetController::class, 'index'])->name('budget.requests');
    Route::put('/budget', [\App\Http\Controllers\Backend\BudgetController::class, 'save'])->name('budget.save');
    Route::post('/budget/pengajuan', [\App\Http\Controllers\Backend\BudgetController::class, 'store'])->name('budget.store');
    Route::post('/budget/{id}/approval', [\App\Http\Controllers\Backend\BudgetController::class, 'decide'])->name('budget.decide');
    Route::post('/budget/{id}/transfer', [\App\Http\Controllers\Backend\BudgetController::class, 'fund'])->name('budget.fund');
    Route::get('/aktivitas', [\App\Http\Controllers\Backend\BudgetController::class, 'logs'])->name('activity.index');
});
