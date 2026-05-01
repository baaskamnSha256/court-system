<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboard;
use App\Http\Controllers\Admin\HearingPrintController;
use App\Http\Controllers\Admin\HearingsController as AdminHearings;
use App\Http\Controllers\Admin\MatterCategoriesController;
use App\Http\Controllers\Admin\NotesHandoverController;
use App\Http\Controllers\Admin\NotificationLogsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TextMaskController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\CourtClerk\DashboardController as CourtClerkDashboard;
use App\Http\Controllers\DefendantSearchController;
use App\Http\Controllers\HearingController;
use App\Http\Controllers\InfoDesk\DashboardController as InfoDeskDashboard;
use App\Http\Controllers\InfoDesk\HearingPrintController as InfoDeskHearingPrintController;
use App\Http\Controllers\Judge\DashboardController as JudgeDashboard;
use App\Http\Controllers\Lawyer\DashboardController as LawyerDashboard;
use App\Http\Controllers\Prosecutor\DashboardController as ProsecutorDashboard;
use App\Http\Controllers\Role\HearingsController as RoleHearingsController;
use App\Http\Controllers\Secretary\DashboardController as SecretaryDashboard;
use App\Http\Controllers\Secretary\HearingsController as SecretaryHearings;
use App\Http\Controllers\Secretary\TextMaskController as SecretaryTextMaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/login');
})->name('home');

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/
Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
Route::post('/login', [LoginController::class, 'login'])
    ->middleware('throttle:20,1')
    ->name('login.store');
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
Route::get('/forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');

/*
|--------------------------------------------------------------------------
| ROLE BASED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', function () {
        $u = auth()->user();
        if ($u->hasAnyRole(['admin', 'head_of_department'])) {
            return redirect()->route('admin.dashboard');
        }
        if ($u->hasRole('judge')) {
            return redirect()->route('judge.dashboard');
        }
        if ($u->hasRole('secretary')) {
            return redirect()->route('secretary.dashboard');
        }
        if ($u->hasRole('court_clerk')) {
            return redirect()->route('court_clerk.dashboard');
        }
        if ($u->hasRole('prosecutor')) {
            return redirect()->route('prosecutor.dashboard');
        }
        if ($u->hasRole('info_desk')) {
            return redirect()->route('info_desk.dashboard');
        }
        if ($u->hasRole('lawyer')) {
            return redirect()->route('lawyer.dashboard');
        }
        abort(403);
    })->name('dashboard');

    // Admin
    Route::middleware(['role:admin|head_of_department'])->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [AdminDashboard::class, 'index'])->name('dashboard');
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::post('/users', [UsersController::class, 'store'])->middleware('role:admin')->name('users.store');
        Route::patch('/users/{user}/toggle', [UsersController::class, 'toggle'])->middleware('role:admin')->name('users.toggle');
        Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->middleware('role:admin')->name('users.edit');
        Route::put('/users/{user}', [UsersController::class, 'update'])->middleware('role:admin')->name('users.update');

        Route::post('hearings/check-conflict', [AdminHearings::class, 'checkConflict'])->middleware('role:admin')->name('hearings.checkConflict');
        Route::get('hearings/by-date', [AdminHearings::class, 'byDate'])->middleware('role:admin')->name('hearings.byDate');
        Route::get('defendant-search', [DefendantSearchController::class, 'search'])->middleware('role:admin')->name('defendant-search');
        Route::get('hearings/print', [HearingPrintController::class, 'index'])->middleware('role:admin')->name('hearings.print');
        Route::get('hearings/print/excel', [HearingPrintController::class, 'download'])->middleware('role:admin')->name('hearings.print.download');
        Route::get('hearings', [AdminHearings::class, 'index'])->name('hearings.index');
        Route::resource('hearings', AdminHearings::class)->middleware('role:admin')->except(['show', 'index']);

        Route::get('/notes-handover', [NotesHandoverController::class, 'index'])->name('notes.index');
        Route::patch('/notes-handover/{hearing}', [NotesHandoverController::class, 'update'])->name('notes.update');
        Route::post('/notes-handover/{hearing}/reschedule', [NotesHandoverController::class, 'reschedule'])->name('notes.reschedule');
        Route::get('/text-mask', [TextMaskController::class, 'index'])->name('textmask.index');
        Route::post('/text-mask', [TextMaskController::class, 'process'])->name('textmask.process');
        Route::get('/text-mask/download', [TextMaskController::class, 'downloadPreview'])->name('textmask.download');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::get('/settings/reports', [\App\Http\Controllers\Admin\ReportController::class, 'index'])->name('reports.index');
        Route::get('/settings/reports/excel', [\App\Http\Controllers\Admin\ReportController::class, 'download'])->name('reports.download');
        Route::get('/settings/reports/excel-defendant-details', [\App\Http\Controllers\Admin\ReportController::class, 'downloadDefendantDetails'])->name('reports.download.defendant-details');
        Route::get('/notifications/logs', [NotificationLogsController::class, 'index'])->name('notifications.logs.index');
        Route::get('/settings/matter-categories', [MatterCategoriesController::class, 'index'])->name('matter-categories.index');
        Route::post('/settings/matter-categories', [MatterCategoriesController::class, 'store'])->name('matter-categories.store');
        Route::delete('/settings/matter-categories/{matterCategory}', [MatterCategoriesController::class, 'destroy'])->name('matter-categories.destroy');
    });

    // Secretary
    Route::middleware(['role:secretary'])->prefix('secretary')->name('secretary.')->group(function () {
        Route::get('/', [SecretaryDashboard::class, 'index'])->name('dashboard');
        Route::get('hearings/by-date', [SecretaryHearings::class, 'byDate'])->name('hearings.byDate');
        Route::get('defendant-search', [DefendantSearchController::class, 'search'])->name('defendant-search');
        Route::post('hearings/check-conflict', [SecretaryHearings::class, 'checkConflict'])->name('hearings.checkConflict');
        Route::get('/notes-handover', [\App\Http\Controllers\Secretary\NotesHandoverController::class, 'index'])->name('notes.index');
        Route::patch('/notes-handover/{hearing}', [\App\Http\Controllers\Secretary\NotesHandoverController::class, 'update'])->name('notes.update');
        Route::post('/notes-handover/{hearing}/reschedule', [\App\Http\Controllers\Secretary\NotesHandoverController::class, 'reschedule'])->name('notes.reschedule');
        Route::get('/text-mask', [SecretaryTextMaskController::class, 'index'])->name('textmask.index');
        Route::post('/text-mask', [SecretaryTextMaskController::class, 'process'])->name('textmask.process');
        Route::get('/text-mask/download', [SecretaryTextMaskController::class, 'downloadPreview'])->name('textmask.download');
        Route::resource('hearings', SecretaryHearings::class)->except(['show']);
    });

    // Judge
    Route::middleware(['role:judge'])->prefix('judge')->name('judge.')->group(function () {
        Route::get('/', [JudgeDashboard::class, 'index'])->name('dashboard');
        Route::get('/hearings', [RoleHearingsController::class, 'judgeIndex'])->name('hearings.index');
    });

    // Prosecutor
    Route::middleware(['role:prosecutor'])->prefix('prosecutor')->name('prosecutor.')->group(function () {
        Route::get('/', [ProsecutorDashboard::class, 'index'])->name('dashboard');
        Route::get('/hearings', [RoleHearingsController::class, 'prosecutorIndex'])->name('hearings.index');
    });

    // Court Clerk
    Route::middleware(['role:court_clerk'])->prefix('court-clerk')->name('court_clerk.')->group(function () {
        Route::get('/', [CourtClerkDashboard::class, 'index'])->name('dashboard');
        Route::get('/hearings', [RoleHearingsController::class, 'courtClerkIndex'])->name('hearings.index');
        Route::get('/notes-handover', [\App\Http\Controllers\CourtClerk\NotesHandoverController::class, 'index'])->name('notes.index');
        Route::patch('/notes-handover/{hearing}', [\App\Http\Controllers\CourtClerk\NotesHandoverController::class, 'update'])->name('notes.update');
        Route::get('/reports', [\App\Http\Controllers\CourtClerk\ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/excel', [\App\Http\Controllers\CourtClerk\ReportController::class, 'download'])->name('reports.download');
        Route::post('hearings/check-conflict', [HearingController::class, 'checkConflict'])->name('hearings.checkConflict');
        Route::resource('hearings', HearingController::class)->except(['index', 'create', 'store']);
    });

    // Info Desk
    Route::middleware(['role:info_desk'])->prefix('info-desk')->name('info_desk.')->group(function () {
        Route::get('/', [InfoDeskDashboard::class, 'index'])->name('dashboard');
        Route::get('hearings/print', [InfoDeskHearingPrintController::class, 'index'])->name('hearings.print');
        Route::get('hearings/print/excel', [InfoDeskHearingPrintController::class, 'download'])->name('hearings.print.download');
    });

    // Lawyer
    Route::middleware(['role:lawyer'])->prefix('lawyer')->name('lawyer.')->group(function () {
        Route::get('/', [LawyerDashboard::class, 'index'])->name('dashboard');
        Route::get('/hearings', [RoleHearingsController::class, 'lawyerIndex'])->name('hearings.index');
    });
});
