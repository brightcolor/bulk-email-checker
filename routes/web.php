<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\ApiKeyController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\BulkJobController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TenantSettingsController;
use App\Http\Controllers\TenantSwitchController;
use Illuminate\Support\Facades\Route;

// Redirect root to dashboard or login
Route::get('/', fn() => redirect()->route('dashboard'));

// ─── Auth (guest only) ────────────────────────────────────────────────────────
Route::middleware('guest')->group(function () {
    Route::get('/register', [RegisterController::class, 'showForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/login', [LoginController::class, 'showForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);

    Route::get('/forgot-password', [PasswordResetController::class, 'showForgotForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'resetPassword'])->name('password.update');
});

// Logout (auth required, no tenant required)
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Invitations (accept — can be guest or auth)
Route::get('/invite/{token}', [InvitationController::class, 'showAccept'])->name('invitations.accept');
Route::post('/invite/{token}', [InvitationController::class, 'accept'])->middleware('auth');

// ─── Tenant selection (auth, no active tenant needed) ─────────────────────────
Route::middleware('auth')->group(function () {
    Route::get('/workspace/select', [TenantSwitchController::class, 'select'])->name('tenant.select');
    Route::post('/workspace/switch', [TenantSwitchController::class, 'switch'])->name('tenant.switch');
});

// ─── App (auth + active tenant) ───────────────────────────────────────────────
Route::middleware(['auth', \App\Http\Middleware\SetActiveTenant::class])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Bulk Jobs
    Route::resource('bulk-jobs', BulkJobController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::post('/bulk-jobs/{bulkJob}/cancel', [BulkJobController::class, 'cancel'])
        ->name('bulk-jobs.cancel');
    Route::get('/bulk-jobs/{bulkJob}/export', [BulkJobController::class, 'export'])
        ->name('bulk-jobs.export');

    // Members
    Route::get('/members', [MemberController::class, 'index'])->name('members.index');
    Route::patch('/members/{membership}/role', [MemberController::class, 'updateRole'])->name('members.updateRole');
    Route::delete('/members/{membership}', [MemberController::class, 'destroy'])->name('members.destroy');

    // Invitations
    Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
    Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
    Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');

    // Teams
    Route::resource('teams', TeamController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::patch('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/teams/{team}/members/{user}', [TeamController::class, 'removeMember'])->name('teams.members.remove');

    // API Keys
    Route::get('/api-keys', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{apiKey}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');

    // Tenant Settings
    Route::get('/settings', [TenantSettingsController::class, 'edit'])->name('settings.edit');
    Route::post('/settings', [TenantSettingsController::class, 'update'])->name('settings.update');

    // Audit Log
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
});
