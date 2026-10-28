<?php

namespace App\Providers;

use App\Models\ActivityLog;
use App\Models\ApprovalProposal;
use App\Models\AuthChallenge;
use App\Models\BudgetAdjustment;
use App\Models\BudgetApproval;
use App\Models\BudgetPeriod;
use App\Models\BudgetRequest;
use App\Models\BudgetRequestVersion;
use App\Models\BudgetReversal;
use App\Models\BudgetTransfer;
use App\Models\Departemen;
use App\Models\DepartmentBudget;
use App\Models\Grup;
use App\Models\LampiranProposal;
use App\Models\LogProposal;
use App\Models\MaintenanceItem;
use App\Models\MaintenanceProposal;
use App\Models\NotaPenggunaanProposal;
use App\Models\NotificationMessage;
use App\Models\PencairanDana;
use App\Models\PengembalianDana;
use App\Models\Proposal;
use App\Models\ProposalItem;
use App\Models\Rekening;
use App\Models\User;
use App\Observers\ActivityObserver;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Laravel 11 email rule hardening: reject controls/header injection at app boundaries.
        Validator::extend('safe_email', static fn ($attribute, $value) => is_string($value) && ! preg_match('/[\x00-\x1F\x7F]/', $value) && filter_var($value, FILTER_VALIDATE_EMAIL) !== false);
        Validator::replacer('safe_email', static fn () => 'Alamat email tidak valid.');
        RateLimiter::for('login', fn ($r) => [
            Limit::perMinute(20)->by('login-ip:'.$r->ip()),
            Limit::perMinutes(10, 10)->by('login-account:'.(is_string($r->input('email')) ? strtolower($r->input('email')) : 'invalid')),
            Limit::perMinute(5)->by('login:'.(is_string($r->input('email')) ? strtolower($r->input('email')) : 'invalid').'|'.$r->ip()),
        ]);
        RateLimiter::for('otp-send', fn ($r) => [
            Limit::perMinutes(10, 10)->by('otp-ip:'.$r->ip()),
            Limit::perMinutes(10, 3)->by('otp:'.(is_string($r->input('email')) ? strtolower($r->input('email')) : 'invalid')),
        ]);
        RateLimiter::for('otp-verify', fn ($r) => [
            Limit::perMinutes(10, 30)->by('verify-ip:'.$r->ip()),
            Limit::perMinutes(10, 5)->by('verify:'.(is_string($r->input('email')) ? strtolower($r->input('email')) : 'invalid')),
        ]);
        foreach ([
            User::class, Departemen::class, Grup::class,
            Proposal::class, ProposalItem::class, ApprovalProposal::class,
            PencairanDana::class, PengembalianDana::class, Rekening::class,
            LampiranProposal::class, MaintenanceItem::class,
            MaintenanceProposal::class, LogProposal::class,
            AuthChallenge::class, NotificationMessage::class, BudgetPeriod::class,
            BudgetAdjustment::class, BudgetRequestVersion::class, BudgetReversal::class,
            NotaPenggunaanProposal::class, DepartmentBudget::class,
            BudgetRequest::class, BudgetApproval::class, BudgetTransfer::class,
        ] as $model) {
            $model::observe(ActivityObserver::class);
        }
        Departemen::updating(function ($department) {
            if ($department->isDirty('grup_id') && (DepartmentBudget::where('departemen_id', $department->id)->exists() || Proposal::where('departemen_id', $department->id)->exists())) {
                throw ValidationException::withMessages(['grup_id' => 'Departemen dengan riwayat budget tidak dapat dipindahkan ke grup lain.']);
            }
        });
        foreach ([Login::class, Logout::class, Failed::class] as $event) {
            Event::listen($event, function ($event) {
                ActivityLog::create(['user_id' => $event->user?->id,
                    'action' => 'auth.'.strtolower(class_basename($event))]);
            });
        }
    }
}
