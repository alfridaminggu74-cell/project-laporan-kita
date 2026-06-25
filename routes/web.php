<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardAdminController;
use App\Http\Controllers\DashboardUserController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\NaiveBayesController;
use App\Models\Laporan;

// Halaman Guest
Route::get('/', function () {
    // public feed untuk ditampilkan di landing page
    $laporans = Laporan::with('user')->latest()->take(3)->get();
    
    // Ambil 1 laporan terbaru untuk hero section
    $latestReport = Laporan::latest()->first();
    
    // Statistik laporan
    $totalLaporan = Laporan::count();
    $laporanDiproses = Laporan::where('status', 'diproses')->count();
    $laporanSelesai = Laporan::where('status', 'selesai')->count();
    
    return view('landingpage', compact('laporans', 'latestReport', 'totalLaporan', 'laporanDiproses', 'laporanSelesai'));
})->name('home');

// ========================
// Halaman Publik (tanpa login)
// ========================
Route::get('/semualaporan', [PublicController::class, 'semualaporan'])->name('public.semualaporan');
Route::get('/pelajari', [PublicController::class, 'pelajari'])->name('public.pelajari');
Route::get('/syarat-ketentuan', [PublicController::class, 'syaratKetentuan'])->name('public.syarat');
Route::get('/kebijakan-privasi', [PublicController::class, 'kebijakanPrivasi'])->name('public.privasi');
Route::get('/prosedur-laporan', [PublicController::class, 'prosedurLaporan'])->name('public.prosedur');
Route::get('/faq', [PublicController::class, 'faq'])->name('public.faq');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
    Route::post('/register', [AuthController::class, 'register'])->name('register');

    // Google OAuth
    Route::get('/auth/google', [AuthController::class, 'redirectToGoogle'])->name('auth.google');
    Route::get('/auth/google/callback', [AuthController::class, 'handleGoogleCallback']);
});

// Halaman yang butuh login
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // User Dashboard & Reports
    Route::get('/dashboarduser', [DashboardUserController::class, 'index'])->name('dashboarduser.index');
    Route::get('/buatlaporan', [DashboardUserController::class, 'create'])->name('laporan.create');
    Route::post('/buatlaporan', [DashboardUserController::class, 'store'])->name('laporan.store');
    Route::get('/laporan/{id}', [DashboardUserController::class, 'show'])->name('laporan.show');
    Route::get('/laporan/{id}/edit', [DashboardUserController::class, 'edit'])->name('laporan.edit');
    Route::put('/laporan/{id}', [DashboardUserController::class, 'update'])->name('laporan.update');
    Route::delete('/laporan/{id}', [DashboardUserController::class, 'destroy'])->name('laporan.destroy');
    Route::get('/laporansaya', [DashboardUserController::class, 'laporansaya'])->name('dashboarduser.laporan');

    // User Profile
    Route::get('/profil', [DashboardUserController::class, 'profil'])->name('dashboarduser.profil');
    Route::post('/profil', [DashboardUserController::class, 'updateProfil'])->name('dashboarduser.profil.update');
    Route::post('/profil/password', [DashboardUserController::class, 'updatePassword'])->name('dashboarduser.profil.password');

    // User Support
    Route::post('/laporan/{id}/support', [DashboardUserController::class, 'toggleSupport'])->name('laporan.support');

    // User Notifikasi
    Route::get('/notifikasi', [DashboardUserController::class, 'notifikasi'])->name('dashboarduser.notifikasi');
    Route::post('/notifikasi/read', [DashboardUserController::class, 'markNotifRead'])->name('dashboarduser.notifikasi.read');

    // Admin Dashboard & Management
    Route::middleware('role:admin')->group(function () {
        Route::get('/dashboardadmin', [DashboardAdminController::class, 'index'])->name('admin.dashboard');
        Route::get('/dashboardadmin/laporan', [DashboardAdminController::class, 'semuaLaporan'])->name('admin.laporan');
        Route::get('/dashboardadmin/laporan/{id}', [DashboardAdminController::class, 'show'])->name('admin.laporan.show');
        Route::post('/dashboardadmin/laporan/{id}/status', [DashboardAdminController::class, 'updateStatus'])->name('admin.laporan.update');
        Route::get('/dashboardadmin/filter', [DashboardAdminController::class, 'filterLaporan'])->name('admin.filter');

        // Admin Profile
        Route::get('/dashboardadmin/profil', [DashboardAdminController::class, 'profil'])->name('admin.profil');
        Route::post('/dashboardadmin/profil', [DashboardAdminController::class, 'updateProfil'])->name('admin.profil.update');
        Route::post('/dashboardadmin/profil/password', [DashboardAdminController::class, 'updatePassword'])->name('admin.profil.password');

        // Admin User Management
        Route::get('/dashboardadmin/users', [DashboardAdminController::class, 'users'])->name('admin.users');
        Route::post('/dashboardadmin/users', [DashboardAdminController::class, 'storeUser'])->name('admin.users.store');
        Route::get('/dashboardadmin/users/{id}', [DashboardAdminController::class, 'userDetail'])->name('admin.users.show');
        Route::put('/dashboardadmin/users/{id}', [DashboardAdminController::class, 'updateUser'])->name('admin.users.update');
        Route::patch('/dashboardadmin/users/{id}/role', [DashboardAdminController::class, 'updateUserRole'])->name('admin.users.role');
        Route::patch('/dashboardadmin/users/{id}/status', [DashboardAdminController::class, 'toggleUserStatus'])->name('admin.users.status');
        Route::delete('/dashboardadmin/users/{id}', [DashboardAdminController::class, 'deleteUser'])->name('admin.users.delete');

        // Naive Bayes Klasifikasi
        Route::get('/dashboardadmin/naivebayes', [NaiveBayesController::class, 'index'])->name('admin.naivebayes');
        Route::post('/dashboardadmin/naivebayes/train', [NaiveBayesController::class, 'train'])->name('admin.naivebayes.train');
        Route::post('/dashboardadmin/naivebayes/klasifikasi', [NaiveBayesController::class, 'klasifikasi'])->name('admin.naivebayes.klasifikasi');
        Route::get('/dashboardadmin/naivebayes/evaluasi', [NaiveBayesController::class, 'evaluasi'])->name('admin.naivebayes.evaluasi');
        Route::post('/dashboardadmin/naivebayes/reset', [NaiveBayesController::class, 'reset'])->name('admin.naivebayes.reset');
        // Admin Kategori Management
        Route::resource('/dashboardadmin/kategori', \App\Http\Controllers\KategoriController::class)->names('admin.kategori')->except(['create', 'show', 'edit']);
    });

    // AJAX Predict (untuk form buat laporan user) — butuh auth tapi bukan admin
    Route::post('/api/naivebayes/predict', [NaiveBayesController::class, 'predictAjax'])->name('naivebayes.predict');
});
    



