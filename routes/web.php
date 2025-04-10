<?php

use App\Models\User;
use Livewire\Volt\Volt;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClassReportController;


Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::view('users', 'admin.user.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('users');

Route::view('teachers', 'admin.user.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('teachers');

Route::view('students', 'admin.user.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('students');


Route::view('create-user', 'admin.user.create')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('create.user');

Volt::route('users/{user}/edit', 'admin.edit-user')->middleware(['auth', 'verified', 'role:admin'])->name('user.edit');

Route::view('classes', 'admin.classes.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('classes');

Route::view('settings', 'admin.settings.index')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('settings');

Route::view('classes-detail/{id}', 'admin.classes.show')
    ->middleware(['auth', 'verified', 'role:admin'])
    ->name('classes.detail');


Volt::route('classes/{major}/edit', 'admin.edit-major')->middleware(['auth', 'verified', 'role:admin'])->name('edit.major');


Route::get('/attendance/scan', function () {
    return view('admin.attendances.qr-scanner');
})->middleware(['auth', 'verified', 'role:admin'])->name('attendance.scan');

Route::get('/users/{user}/download-qr', [
    'as' => 'user.download.qr',
    'uses' => function ($userId) {
        $user = \App\Models\User::findOrFail($userId);

        if (!$user->qr_code_path) {
            return redirect()->back()->with('error', 'QR Code tidak tersedia');
        }

        return response()->download(storage_path('app/public/' . $user->qr_code_path));
    }
])->middleware('auth');

Route::view('attendances', 'admin.attendances.index')->middleware(['auth', 'verified', 'role:admin'])->name('qr.attendances');

Route::view('arrival-scanner', 'admin.attendances.arrival-scanner')->middleware(['auth', 'verified', 'role:admin'])->name('arrival.scanner');

Volt::route('users/{user}/detail', 'user.user-detail')->middleware(['auth', 'verified', 'role:admin|teacher'])->name('user.detail');

Route::view('classes-attendances', 'teacher.classes.index')->middleware(['auth', 'verified', 'role:teacher'])->name('classes.attendances');
Volt::route('classes-attendances/{subjectClass}/detail', 'teacher.detail-subject-class')->middleware(['auth', 'verified', 'role:teacher'])->name('subject.detail');

Volt::route('/session/{session}/attendance', 'teacher.subject-class-attendance')->middleware(['auth', 'verified', 'role:teacher'])
    ->name('session.attendance');

Route::get('/attendance/{session}/pdf', [App\Http\Controllers\AttendancePdfController::class, 'downloadAttendancePdf'])
    ->middleware(['auth'])
    ->name('attendance.pdf');

Route::view('permission-submission', 'teacher.permission')->middleware(['auth', 'verified', 'role:teacher|student'])->name('permission-submission');
Route::view('permission-management', 'admin.permission-submission.index')->middleware(['auth', 'verified', 'role:admin'])->name('permission-management');
Route::view('class-management', 'teacher.student-class.index')->middleware(['auth', 'verified', 'role:teacher'])->name('class-management');

// Kelas Pengganti
Route::view('/teacher/substitute-classes', 'teacher.substitution.index')->middleware(['auth', 'verified', 'role:teacher'])
    ->name('teacher.substitute');


Route::view('/substitution-request', 'admin.substitution.index')->middleware(['auth', 'verified', 'role:admin'])
    ->name('substitution-request');



Volt::route('/teacher/substitute/class/{subjectClass}', 'teacher.substitution-class-management')
    ->middleware(['auth', 'verified', 'role:teacher'])
    ->name('substitute.class');


Volt::route('/teacher/session/{session}/attendance', 'teacher.session-attendance-management')->middleware(['auth', 'verified', 'role:teacher'])
    ->name('substitute.attendance');


Route::get('/class/{subjectClass}/agenda-report', [ClassReportController::class, 'generateAgendaReport'])->middleware(['auth', 'verified', 'role:admin|teacher'])
    ->name('agenda.report');

Route::get('/class/{subjectClass}/attendance-report', [ClassReportController::class, 'generateAttendanceReport'])
    ->name('attendance.report');

Route::view('/student/reports', 'student.report.index')
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('reports');

Route::view('/student/attendance-history', 'student.history.index')
    ->middleware(['auth', 'verified', 'role:student'])
    ->name('attendance-history');

// Detail kehadiran QR
Route::get('/student/attendance/detail/{date}', function ($date) {
    return view('student.attendance.detail', [
        'date' => $date,
        'type' => 'qr'
    ]);
})->name('student.attendance.detail.qr');

// Detail kehadiran mata pelajaran
Route::get('/student/attendance/detail/subject/{date}/{id}', function ($date, $id) {
    return view('student.attendance.detail', [
        'date' => $date,
        'type' => 'subject',
        'id' => $id
    ]);
})->name('student.attendance.detail.subject');



require __DIR__ . '/auth.php';
