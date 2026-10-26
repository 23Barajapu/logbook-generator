<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\MahasiswaController;
use App\Http\Controllers\PembimbingController;
use Illuminate\Support\Facades\Route;

// Rute Autentikasi Publik
Route::get('/', [AuthController::class, 'showLogin'])->name('login');
Route::get('/login', [AuthController::class, 'showLogin']);
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Authenticated Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Document Generation Routes (Shared with role authorization inside controller)
    Route::get('/documents/{internship}/pdf', [DocumentController::class, 'downloadPdf'])->name('documents.pdf');
    Route::get('/documents/{internship}/docx', [DocumentController::class, 'downloadDocx'])->name('documents.docx');
    Route::get('/documents/{internship}/summary', [DocumentController::class, 'summary'])->name('documents.summary');

    // Mahasiswa Routes
    Route::middleware('role:mahasiswa')->prefix('mahasiswa')->name('mahasiswa.')->group(function () {
        Route::get('/dashboard', [MahasiswaController::class, 'dashboard'])->name('dashboard');
        Route::get('/activities', [MahasiswaController::class, 'activities'])->name('activities');
        Route::get('/activities/create', [MahasiswaController::class, 'createActivity'])->name('activities.create');
        Route::post('/activities', [MahasiswaController::class, 'storeActivity'])->name('activities.store');
        Route::get('/activities/{activity}', [MahasiswaController::class, 'showActivity'])->name('activities.show');
        Route::get('/activities/{activity}/edit', [MahasiswaController::class, 'editActivity'])->name('activities.edit');
        Route::put('/activities/{activity}', [MahasiswaController::class, 'updateActivity'])->name('activities.update');
        Route::delete('/activities/{activity}', [MahasiswaController::class, 'destroyActivity'])->name('activities.destroy');
        Route::post('/activities/{activity}/submit', [MahasiswaController::class, 'submitApproval'])->name('activities.submit');

        // Import Activities from DOCX
        Route::get('/activities-import', [MahasiswaController::class, 'showImportForm'])->name('activities.import');
        Route::post('/activities-import', [MahasiswaController::class, 'processImport'])->name('activities.import.process');
        Route::get('/activities-import/preview', [MahasiswaController::class, 'previewImport'])->name('activities.import.preview');
        Route::post('/activities-import/confirm', [MahasiswaController::class, 'confirmImport'])->name('activities.import.confirm');
        Route::post('/activities-import/cancel', [MahasiswaController::class, 'cancelImport'])->name('activities.import.cancel');

        // Pengelolaan PKL & Dosen Pembimbing oleh Mahasiswa
        Route::get('/internship', [MahasiswaController::class, 'editInternship'])->name('internship.edit');
        Route::put('/internship', [MahasiswaController::class, 'updateInternship'])->name('internship.update');
    });

    // Pembimbing Routes
    Route::middleware('role:pembimbing')->prefix('pembimbing')->name('pembimbing.')->group(function () {
        Route::get('/dashboard', [PembimbingController::class, 'dashboard'])->name('dashboard');
        Route::get('/students', [PembimbingController::class, 'students'])->name('students');
        Route::get('/students/{internship}', [PembimbingController::class, 'studentDetail'])->name('students.show');
        Route::get('/activities/{activity}/review', [PembimbingController::class, 'reviewActivity'])->name('activities.review');
        Route::post('/activities/{activity}/approval', [PembimbingController::class, 'processApproval'])->name('activities.approval');
        Route::get('/students/{internship}/evaluate', [PembimbingController::class, 'evaluateForm'])->name('students.evaluate');
        Route::post('/students/{internship}/evaluate', [PembimbingController::class, 'saveEvaluation'])->name('students.evaluate.save');
    });

    // Admin Routes
    Route::middleware('role:admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

        // Students
        Route::get('/students', [AdminController::class, 'students'])->name('students');
        Route::get('/students/create', [AdminController::class, 'createStudent'])->name('students.create');
        Route::post('/students', [AdminController::class, 'storeStudent'])->name('students.store');
        Route::get('/students/{student}/edit', [AdminController::class, 'editStudent'])->name('students.edit');
        Route::put('/students/{student}', [AdminController::class, 'updateStudent'])->name('students.update');
        Route::delete('/students/{student}', [AdminController::class, 'destroyStudent'])->name('students.destroy');

        // Supervisors
        Route::get('/supervisors', [AdminController::class, 'supervisors'])->name('supervisors');
        Route::get('/supervisors/create', [AdminController::class, 'createSupervisor'])->name('supervisors.create');
        Route::post('/supervisors', [AdminController::class, 'storeSupervisor'])->name('supervisors.store');
        Route::get('/supervisors/{supervisor}/edit', [AdminController::class, 'editSupervisor'])->name('supervisors.edit');
        Route::put('/supervisors/{supervisor}', [AdminController::class, 'updateSupervisor'])->name('supervisors.update');
        Route::delete('/supervisors/{supervisor}', [AdminController::class, 'destroySupervisor'])->name('supervisors.destroy');

        // Companies
        Route::get('/companies', [AdminController::class, 'companies'])->name('companies');
        Route::get('/companies/create', [AdminController::class, 'createCompany'])->name('companies.create');
        Route::post('/companies', [AdminController::class, 'storeCompany'])->name('companies.store');
        Route::get('/companies/{company}/edit', [AdminController::class, 'editCompany'])->name('companies.edit');
        Route::put('/companies/{company}', [AdminController::class, 'updateCompany'])->name('companies.update');
        Route::delete('/companies/{company}', [AdminController::class, 'destroyCompany'])->name('companies.destroy');

        // Internships
        Route::get('/internships', [AdminController::class, 'internships'])->name('internships');
        Route::get('/internships/create', [AdminController::class, 'createInternship'])->name('internships.create');
        Route::post('/internships', [AdminController::class, 'storeInternship'])->name('internships.store');
        Route::get('/internships/{internship}/edit', [AdminController::class, 'editInternship'])->name('internships.edit');
        Route::put('/internships/{internship}', [AdminController::class, 'updateInternship'])->name('internships.update');
        Route::delete('/internships/{internship}', [AdminController::class, 'destroyInternship'])->name('internships.destroy');
    });
});
