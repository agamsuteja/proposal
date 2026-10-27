<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        foreach ([
            \App\Models\User::class, \App\Models\Departemen::class, \App\Models\Grup::class,
            \App\Models\Proposal::class, \App\Models\ProposalItem::class, \App\Models\ApprovalProposal::class,
            \App\Models\PencairanDana::class, \App\Models\PengembalianDana::class, \App\Models\Rekening::class,
            \App\Models\LampiranProposal::class, \App\Models\MaintenanceItem::class,
            \App\Models\MaintenanceProposal::class, \App\Models\LogProposal::class,
            \App\Models\NotaPenggunaanProposal::class, \App\Models\DepartmentBudget::class,
            \App\Models\BudgetRequest::class, \App\Models\BudgetApproval::class, \App\Models\BudgetTransfer::class,
        ] as $model) {
            $model::observe(\App\Observers\ActivityObserver::class);
        }
        \App\Models\Departemen::updating(function ($department) {
            if ($department->isDirty('grup_id') && \App\Models\DepartmentBudget::where('departemen_id', $department->id)->exists()) {
                throw \Illuminate\Validation\ValidationException::withMessages(['grup_id' => 'Departemen dengan riwayat budget tidak dapat dipindahkan ke grup lain.']);
            }
        });
        foreach ([\Illuminate\Auth\Events\Login::class, \Illuminate\Auth\Events\Logout::class, \Illuminate\Auth\Events\Failed::class] as $event) {
            \Illuminate\Support\Facades\Event::listen($event, function ($event) {
                \App\Models\ActivityLog::create(['user_id' => $event->user?->id,
                    'action' => 'auth.'.strtolower(class_basename($event))]);
            });
        }
    }
}
