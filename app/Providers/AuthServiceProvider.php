<?php

namespace App\Providers;

use App\Models\SubjectClass;
use App\Models\SubstitutionRequest;
use App\Models\User;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        //
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        // Gate untuk menentukan apakah guru bisa mengelola kelas pengganti tertentu
        Gate::define('manage-substitute-class', function (User $user, SubjectClass $subjectClass) {
            // Cek apakah ada permintaan penggantian yang disetujui untuk user ini
            return SubstitutionRequest::where('substitute_teacher_id', $user->id)
                ->where('subject_class_id', $subjectClass->id)
                ->where('status', 'approved')
                ->where(function ($query) {
                    $query->whereDate('start_date', '<=', now())
                        ->where(function ($q) {
                            $q->whereDate('end_date', '>=', now())
                                ->orWhereNull('end_date');
                        });
                })
                ->exists();
        });
    }
}
