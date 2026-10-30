<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Backend\ApprovalProposalController;
use App\Http\Controllers\Backend\BudgetController;
use App\Http\Controllers\Backend\DashboardController;
use App\Http\Controllers\Backend\DepartemenController;
use App\Http\Controllers\Backend\DocumentController;
use App\Http\Controllers\Backend\GrupController;
use App\Http\Controllers\Backend\LaporanController;
use App\Http\Controllers\Backend\MaintenanceProposalController;
use App\Http\Controllers\Backend\NotificationController;
use App\Http\Controllers\Backend\PencairanDanaController;
use App\Http\Controllers\Backend\PengembalianDanaController;
use App\Http\Controllers\Backend\ProposalController;
use App\Http\Controllers\Backend\RekeningController;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route('admin.login'));
Route::get('/login', fn () => redirect()->route('admin.login'))->name('login');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login')->name('login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/forgot-password', [AuthController::class, 'forgotPasswordForm'])->name('forgot.form');
    Route::post('/forgot-password', [AuthController::class, 'forgotPasswordSend'])->middleware('throttle:otp-send')->name('forgot.send');
    Route::get('/reset-password', [AuthController::class, 'resetPasswordForm'])->name('reset.form');
    Route::post('/reset-password', [AuthController::class, 'resetPasswordSubmit'])->middleware('throttle:otp-verify')->name('reset.submit');
    Route::get('/register', [AuthController::class, 'showRegisterForm'])->name('register.form');
    Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:otp-send')->name('register.submit');
    Route::post('/register/resend', [AuthController::class, 'resendRegistrationOtp'])->middleware('throttle:otp-send')->name('register.resend');
    Route::get('/register/verify', [AuthController::class, 'showRegisterVerifyForm'])->name('register.verify.form');
    Route::post('/register/verify', [AuthController::class, 'registerVerify'])->middleware('throttle:otp-verify')->name('register.verify.submit');

    Route::middleware(['auth', 'active'])->group(function () {
        Route::middleware('role:admin')->group(function () {
            Route::get('/user-pending', [AuthController::class, 'pendingUsers'])->name('user.pending');
            Route::post('/user-approve/{id}', [AuthController::class, 'approveUser'])->whereNumber('id')->name('user.approve');
            Route::resource('user', UserController::class)->except('show');
            Route::resource('departemen', DepartemenController::class)->except('show');
            Route::resource('grup', GrupController::class)->except('show');
        });
        Route::middleware('role:admin,keuangan')->group(function () {
            Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
            Route::get('/dashboard/data-pencairan', [DashboardController::class, 'dataPencairan'])->name('dashboard.dataPencairan');
            Route::resource('rekening', RekeningController::class)->except('show');
            Route::get('/pencairan', [PencairanDanaController::class, 'index'])->name('pencairan.index');
            Route::get('/pencairan/{id}', [PencairanDanaController::class, 'show'])->whereNumber('id')->name('pencairan.show');
            Route::get('/notifikasi', [NotificationController::class, 'index'])->name('notifications.index');
            Route::post('/notifikasi/{id}/retry', [NotificationController::class, 'retry'])->middleware('throttle:10,1')->whereNumber('id')->name('notifications.retry');
        });
        Route::post('/pencairan', [PencairanDanaController::class, 'store'])->middleware('role:keuangan')->name('pencairan.store');
        Route::resource('proposal', ProposalController::class);
        Route::post('/proposal/{id}/request-revision', [ProposalController::class, 'requestRevision'])->middleware('role:admin')->whereNumber('id')->name('proposal.request-revision');
        Route::post('/proposal/{id}/upload-nota', [ProposalController::class, 'uploadNota'])->whereNumber('id')->name('proposal.uploadNota');
        Route::get('/dokumen/{path}', [DocumentController::class, 'show'])->where('path', '.*')->name('documents.show');
        Route::middleware('role:admin,keuangan,departemen_head,group_head,gembala')->group(function () {
            Route::get('/approval', [ApprovalProposalController::class, 'index'])->name('approval.index');
            Route::get('/approval/riwayat', [ApprovalProposalController::class, 'history'])->name('approval.history');
            Route::get('/approval/{id}', [ApprovalProposalController::class, 'show'])->whereNumber('id')->name('approval.show');
        });
        Route::match(['put', 'patch'], '/approval/{id}', [ApprovalProposalController::class, 'update'])->middleware('role:departemen_head,group_head,gembala')->whereNumber('id')->name('approval.update');
        Route::post('/approval/{id}/notif-keuangan', [ApprovalProposalController::class, 'notifKeuangan'])->middleware(['role:group_head', 'throttle:5,1'])->whereNumber('id')->name('approval.notif-keuangan');
        Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('/laporan/export', [LaporanController::class, 'exportExcel'])->name('laporan.export');
        Route::get('/laporan/export/excel', [LaporanController::class, 'exportExcel'])->name('laporan.export.excel');
        Route::get('/laporan/export/pdf', [LaporanController::class, 'exportPdf'])->name('laporan.export.pdf');
        Route::prefix('pengembalian-dana')->name('pengembalian_dana.')->group(function () {
            Route::get('/', [PengembalianDanaController::class, 'index'])->name('index');
            Route::get('/create/{proposal}', [PengembalianDanaController::class, 'create'])->whereNumber('proposal')->name('create');
            Route::post('/store', [PengembalianDanaController::class, 'store'])->name('store');
            Route::get('/export/pdf', [PengembalianDanaController::class, 'exportPdf'])->name('export');
            Route::post('/approve/{id}', [PengembalianDanaController::class, 'approve'])->middleware('role:keuangan')->whereNumber('id')->name('approve');
            Route::post('/reject/{id}', [PengembalianDanaController::class, 'reject'])->middleware('role:keuangan')->whereNumber('id')->name('reject');
            Route::get('/{id}', [PengembalianDanaController::class, 'show'])->whereNumber('id')->name('show');
        });
        Route::prefix('maintenance')->name('maintenance.')->group(function () {
            Route::get('/', [MaintenanceProposalController::class, 'index'])->name('index');
            Route::get('/create', [MaintenanceProposalController::class, 'create'])->name('create');
            Route::post('/', [MaintenanceProposalController::class, 'store'])->name('store');
        });
        Route::get('/budget', [BudgetController::class, 'index'])->name('budget.index');
        Route::get('/budget/pengajuan', [BudgetController::class, 'index'])->name('budget.requests');
        Route::get('/budget/export', [BudgetController::class, 'export'])->name('budget.export');
        Route::get('/budget/pengajuan/export/pdf', [BudgetController::class, 'exportRequestsPdf'])->name('budget.requests.pdf');
        Route::post('/budget/pendanaan', [BudgetController::class, 'saveFunding'])->middleware('role:keuangan')->name('budget.funding');
        Route::post('/budget/bagi-rata', [BudgetController::class, 'distribute'])->middleware('role:keuangan')->name('budget.distribute');
        Route::put('/budget', [BudgetController::class, 'save'])->middleware('role:keuangan')->name('budget.save');
        Route::post('/budget/pengajuan', [BudgetController::class, 'store'])->name('budget.store');
        Route::post('/budget/periode', [BudgetController::class, 'period'])->middleware('role:keuangan')->name('budget.period');
        Route::post('/budget/copy', [BudgetController::class, 'copyYear'])->middleware('role:keuangan')->name('budget.copy');
        Route::post('/budget/{id}/approval', [BudgetController::class, 'decide'])->whereNumber('id')->name('budget.decide');
        Route::post('/budget/{id}/transfer', [BudgetController::class, 'fund'])->middleware('role:keuangan')->whereNumber('id')->name('budget.fund');
        Route::post('/budget/{id}/revise', [BudgetController::class, 'revise'])->whereNumber('id')->name('budget.revise');
        Route::post('/budget/{id}/cancel', [BudgetController::class, 'cancel'])->whereNumber('id')->name('budget.cancel');
        Route::post('/budget/{id}/reassign', [BudgetController::class, 'reassign'])->middleware('role:admin')->whereNumber('id')->name('budget.reassign');
        Route::post('/budget/{id}/reverse', [BudgetController::class, 'reverse'])->middleware('role:keuangan')->whereNumber('id')->name('budget.reverse');
        Route::get('/aktivitas', [BudgetController::class, 'logs'])->middleware('role:admin,keuangan')->name('activity.index');
        Route::get('/aktivitas/export', [BudgetController::class, 'exportLogs'])->middleware('role:admin,keuangan')->name('activity.export');
    });
});
