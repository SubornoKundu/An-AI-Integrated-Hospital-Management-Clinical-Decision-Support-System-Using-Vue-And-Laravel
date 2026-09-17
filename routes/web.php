<?php

use App\Http\Controllers\Admin\BedManagementController;
use App\Http\Controllers\Admin\DashboardController as AdminController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Doctor\DashboardController as DoctorController;
use App\Http\Controllers\Doctor\DoctorAiController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Patient\AiController;
use App\Http\Controllers\Patient\DashboardController as PatientController;
use App\Http\Controllers\Patient\SmartAiController;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// ── Public ────────────────────────────────────────────────────────────────
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/about', [HomeController::class, 'about'])->name('about');
Route::get('/contact', [HomeController::class, 'contact'])->name('contact');
Route::post('/contact', function (Request $r) {
    $r->validate([
        'name' => 'required|string|max:100',
        'email' => 'required|email',
        'contact' => 'required|string|max:20',
        'message' => 'required|string|max:2000',
    ]);
    Contact::create($r->only(['name', 'email', 'contact', 'message']));

    return back()->with('success', 'Message sent. We will contact you shortly.');
})->name('contact.send');

// ── Auth ──────────────────────────────────────────────────────────────────
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::get('/register', [AuthController::class, 'showLogin'])->name('register');

Route::post('/login/patient', [AuthController::class, 'loginPatient'])->middleware('throttle:login')->name('login.patient');
Route::post('/login/doctor', [AuthController::class, 'loginDoctor'])->middleware('throttle:login')->name('login.doctor');
Route::post('/login/admin', [AuthController::class, 'loginAdmin'])->middleware('throttle:login')->name('login.admin');
Route::post('/register/patient', [AuthController::class, 'registerPatient'])->name('register.patient');
Route::post('/forgot-password/send-code', [AuthController::class, 'sendResetCode'])->name('password.send-code');
Route::post('/forgot-password/verify-code', [AuthController::class, 'verifyResetCode'])->name('password.verify-code');
Route::post('/forgot-password/reset', [AuthController::class, 'resetPasswordWithCode'])->name('password.reset');

Route::post('/logout/patient', [AuthController::class, 'logoutPatient'])->name('logout.patient');
Route::post('/logout/doctor', [AuthController::class, 'logoutDoctor'])->name('logout.doctor');
Route::post('/logout/admin', [AuthController::class, 'logoutAdmin'])->name('logout.admin');

// ── Patient Panel ─────────────────────────────────────────────────────────
Route::prefix('patient')->name('patient.')->middleware('patient.auth')->group(function () {

    Route::get('dashboard', [PatientController::class, 'dashboardFull'])->name('dashboard');

    // Booking
    Route::get('book', [PatientController::class, 'bookStep1'])->name('book.step1');
    Route::get('book/doctor', [PatientController::class, 'bookStep2'])->name('book.step2');
    Route::get('book/slot', [PatientController::class, 'bookStep3'])->name('book.step3');
    Route::post('book/confirm', [PatientController::class, 'bookConfirm'])->name('book.confirm');
    Route::get('slots', [PatientController::class, 'getSlots'])->name('slots');

    // Appointments
    Route::get('appointments', [PatientController::class, 'appointments'])->name('appointments');
    Route::post('appointments/{id}/cancel', [PatientController::class, 'cancelAppointment'])->name('appointments.cancel');
    Route::post('appointments/{id}/rate', [PatientController::class, 'rateDoctor'])->name('appointments.rate');
    Route::get('appointments/{id}/invoice', [PatientController::class, 'downloadInvoice'])->name('appointments.invoice');
    Route::get('prescription/{id}/download', [PatientController::class, 'downloadPrescription'])->name('prescription.download');
    Route::get('invoices', [PatientController::class, 'invoices'])->name('invoices');
    Route::get('prescriptions', [PatientController::class, 'prescriptions'])->name('prescriptions');

    // Favourites
    Route::get('favourites', [PatientController::class, 'favourites'])->name('favourites');
    Route::post('favourites/{doctor}', [PatientController::class, 'toggleFavourite'])->name('favourites.toggle');

    // Notifications
    Route::get('notifications', [PatientController::class, 'notifications'])->name('notifications');

    // Profile
    Route::get('profile', [PatientController::class, 'profile'])->name('profile');
    Route::post('profile', [PatientController::class, 'updateProfile'])->name('profile.update');
    Route::delete('profile/photo', [PatientController::class, 'deletePhoto'])->name('profile.photo.delete');

    // Referrals
    Route::get('referrals', [PatientController::class, 'referrals'])->name('referrals');

    // Chat
    Route::get('chat/{appointment}', [PatientController::class, 'chat'])->name('chat');
    Route::post('chat/{appointment}/send', [PatientController::class, 'sendChat'])->name('chat.send');

    // Search & History
    Route::get('search', [PatientController::class, 'search'])->name('search');
    Route::get('doctors/suggest', [PatientController::class, 'doctorSuggest'])->name('doctors.suggest');
    Route::get('health-card', [PatientController::class, 'healthCard'])->name('health-card');
    Route::get('history', [PatientController::class, 'history'])->name('history');

    // Health Documents
    Route::get('documents', [PatientController::class, 'documents'])->name('documents');
    Route::post('documents', [PatientController::class, 'uploadDocument'])->name('documents.upload');
    Route::get('documents/{id}/download', [PatientController::class, 'downloadDocument'])->name('documents.download');
    Route::delete('documents/{id}', [PatientController::class, 'destroyDocument'])->name('documents.destroy');


    // AI Features — prefix 'ai'
    Route::prefix('ai')->name('ai.')->group(function () {
        Route::get('api-status', [AiController::class, 'apiStatus'])->name('api-status');
        Route::get('symptom-checker', [AiController::class, 'symptomChecker'])->name('symptom-checker');
        Route::post('symptom-checker/analyze', [AiController::class, 'analyzeSymptoms'])->middleware('throttle:ai')->name('symptoms.analyze');
        Route::post('symptom-checker/voice', [AiController::class, 'voiceToText'])->middleware('throttle:ai')->name('symptoms.voice');
        Route::get('prescription-explainer', [AiController::class, 'prescriptionExplainerIndex'])->name('prescription-explainer.index');
        Route::get('prescription/{id}/explain', [AiController::class, 'prescriptionExplainer'])->name('prescription.explainer');
        Route::post('prescription/{id}/explain', [AiController::class, 'explainPrescription'])->middleware('throttle:ai')->name('prescription.explain');
        Route::get('health-risk', [SmartAiController::class, 'healthRiskAssessment'])->name('risk');
        Route::post('health-risk/generate', [SmartAiController::class, 'generateRiskAssessment'])->middleware('throttle:ai')->name('risk.generate');
        Route::get('health-plan', [SmartAiController::class, 'healthPlan'])->name('health-plan');
        Route::post('health-plan/generate', [SmartAiController::class, 'generateHealthPlan'])->middleware('throttle:ai')->name('health-plan.generate');
    });
});

// ── Doctor Panel ──────────────────────────────────────────────────────────
Route::prefix('doctor')->name('doctor.')->middleware('doctor.auth')->group(function () {

    Route::get('dashboard', [DoctorController::class, 'dashboard'])->name('dashboard');

    // Appointments
    Route::get('appointments', [DoctorController::class, 'appointments'])->name('appointments');
    Route::post('appointments/{id}/complete', [DoctorController::class, 'completeAppointment'])->name('appointments.complete');
    Route::post('appointments/{id}/cancel', [DoctorController::class, 'cancelAppointment'])->name('appointments.cancel');

    // Prescriptions
    Route::get('prescription/{appointment}', [DoctorController::class, 'writePrescription'])->name('prescription.write');
    Route::post('prescription/{appointment}', [DoctorController::class, 'savePrescription'])->name('prescription.save');

    // Templates
    Route::get('templates', [DoctorController::class, 'templates'])->name('templates');
    Route::post('templates', [DoctorController::class, 'saveTemplate'])->name('templates.save');
    Route::delete('templates/{id}', [DoctorController::class, 'deleteTemplate'])->name('templates.delete');

    // Referrals
    Route::get('referrals', [DoctorController::class, 'referrals'])->name('referrals');
    Route::post('referrals', [DoctorController::class, 'createReferral'])->name('referrals.create');

    // Other
    Route::get('earnings', [DoctorController::class, 'earnings'])->name('earnings');
    Route::get('calendar', [DoctorController::class, 'calendar'])->name('calendar');
    Route::post('blocked-dates', [DoctorController::class, 'storeBlockedDate'])->name('blocked-dates.store');
    Route::delete('blocked-dates/{id}', [DoctorController::class, 'deleteBlockedDate'])->name('blocked-dates.delete');
    Route::get('notifications', [DoctorController::class, 'notifications'])->name('notifications');
    Route::get('profile', [DoctorController::class, 'profile'])->name('profile');
    Route::post('profile', [DoctorController::class, 'updateProfile'])->name('profile.update');
    Route::delete('profile/photo', [DoctorController::class, 'deletePhoto'])->name('profile.photo.delete');

    // Chat
    Route::get('messages', [DoctorController::class, 'messages'])->name('messages');
    Route::get('chat/{appointment}', [DoctorController::class, 'chat'])->name('chat');
    Route::post('chat/{appointment}/send', [DoctorController::class, 'sendChat'])->name('chat.send');

    // AI Features
    Route::post('prescription/{appointment}/ai-summary', [DoctorAiController::class, 'generatePatientSummary'])->middleware('throttle:ai')->name('prescription.ai-summary');
    Route::post('prescription/{appointment}/safety-check', [DoctorAiController::class, 'prescriptionSafetyCheck'])->middleware('throttle:ai')->name('prescription.safety-check');
});

// ── Admin Panel ───────────────────────────────────────────────────────────
Route::prefix('admin')->name('admin.')->middleware('admin.auth')->group(function () {

    Route::get('dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Doctors
    Route::get('doctors', [AdminController::class, 'doctors'])->name('doctors');
    Route::get('doctors/suggest', [AdminController::class, 'doctorSuggest'])->name('doctors.suggest');
    Route::get('doctors/create', [AdminController::class, 'createDoctor'])->name('doctors.create');
    Route::post('doctors', [AdminController::class, 'storeDoctor'])->name('doctors.store');
    Route::get('doctors/{id}/edit', [AdminController::class, 'editDoctor'])->name('doctors.edit');
    Route::put('doctors/{id}', [AdminController::class, 'updateDoctor'])->name('doctors.update');
    Route::delete('doctors/{id}', [AdminController::class, 'deleteDoctor'])->name('doctors.delete');
    Route::get('doctors/{id}/schedule', [AdminController::class, 'doctorSchedule'])->name('doctors.schedule');
    Route::post('doctors/{id}/schedule', [AdminController::class, 'saveDoctorSchedule'])->name('doctors.schedule.save');

    // Patients
    Route::get('patients', [AdminController::class, 'patients'])->name('patients');
    Route::get('patients/suggest', [AdminController::class, 'patientSuggest'])->name('patients.suggest');
    Route::get('patients/{id}', [AdminController::class, 'patientDetail'])->name('patients.detail');
    Route::put('patients/{id}/edit', [AdminController::class, 'updatePatient'])->name('patients.update');
    Route::post('patients/{id}/reset-password', [AdminController::class, 'resetPatientPassword'])->name('patients.reset-password');

    // Appointments & Revenue
    Route::get('appointments', [AdminController::class, 'appointments'])->name('appointments');
    Route::get('revenue', [AdminController::class, 'revenue'])->name('revenue');

    // Categories
    Route::get('categories', [AdminController::class, 'categories'])->name('categories');
    Route::post('categories', [AdminController::class, 'storeCategory'])->name('categories.store');
    Route::put('categories/{id}', [AdminController::class, 'updateCategory'])->name('categories.update');
    Route::delete('categories/{id}', [AdminController::class, 'deleteCategory'])->name('categories.delete');

    // Holidays
    Route::get('holidays', [AdminController::class, 'holidays'])->name('holidays');
    Route::post('holidays', [AdminController::class, 'storeHoliday'])->name('holidays.store');
    Route::delete('holidays/{id}', [AdminController::class, 'deleteHoliday'])->name('holidays.delete');

    // Feedback, Staff, Settings
    Route::get('feedback', [AdminController::class, 'feedback'])->name('feedback');
    Route::post('feedback/{id}/resolve', [AdminController::class, 'resolveContact'])->name('feedback.resolve');
    // AI
    Route::get('ai-insights', [AdminController::class, 'aiInsights'])->name('ai-insights');
    Route::post('ai-insights/generate', [AdminController::class, 'generateAiInsights'])->middleware('throttle:ai')->name('ai-insights.generate');
    // Admin Management
    Route::get('admins', [AdminController::class, 'admins'])->name('admins');
    Route::post('admins', [AdminController::class, 'storeAdmin'])->name('admins.store');
    Route::put('admins/{id}', [AdminController::class, 'updateAdmin'])->name('admins.update');
    Route::delete('admins/{id}', [AdminController::class, 'deleteAdmin'])->name('admins.delete');

    Route::get('profile', [AdminController::class, 'myProfile'])->name('profile');
    Route::post('profile', [AdminController::class, 'updateMyProfile'])->name('profile.update');
    Route::delete('profile/photo', [AdminController::class, 'deletePhoto'])->name('profile.photo.delete');

    Route::get('settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('settings', [AdminController::class, 'updateSettings'])->name('settings.update');

    // Staff (receptionist / accountant / manager)

    // Bed Management
    Route::prefix('beds')->name('beds.')->group(function () {
        Route::get('/', [BedManagementController::class, 'index'])->name('index');
        Route::post('ward', [BedManagementController::class, 'storeWard'])->name('ward.store');
        Route::delete('ward/{id}', [BedManagementController::class, 'destroyWard'])->name('ward.delete');
        Route::post('bed', [BedManagementController::class, 'storeBed'])->name('bed.store');
        Route::patch('bed/{id}/status', [BedManagementController::class, 'updateBedStatus'])->name('bed.status');
        Route::delete('bed/{id}', [BedManagementController::class, 'destroyBed'])->name('bed.delete');
        Route::post('admit', [BedManagementController::class, 'admitPatient'])->name('admit');
        Route::put('admissions/{admissionId}/discharge', [BedManagementController::class, 'dischargePatient'])->name('discharge');
    });
});

// NOTE: the old routes/settings.php (Profile/Security/Appearance under
// /settings/*) was removed. It ran on Laravel's default 'web' auth guard
// tied to the App\Models\User starter-kit model, which is entirely separate
// from this app's real patient/doctor/admin guards below — no real session
// in this app could ever satisfy that guard, so those pages were dead on
// arrival. Each role already has its own working profile page
// (patient.profile / doctor.profile / admin.profile above).
