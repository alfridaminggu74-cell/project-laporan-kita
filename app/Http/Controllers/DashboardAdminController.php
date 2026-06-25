<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Notifications\LaporanStatusNotification;
use App\Services\CloudinaryService;
use App\Services\NaiveBayesService;
use App\Models\Kategori;


class DashboardAdminController extends Controller
{
    protected $cloudinary;
    protected NaiveBayesService $nb;

    public function __construct(CloudinaryService $cloudinary, NaiveBayesService $nb)
    {
        $this->cloudinary = $cloudinary;
        $this->nb = $nb;
    }
    public function index()
    {
        // Statistik Laporan
        $totalCount = Laporan::count();
        $baruCount = Laporan::where('status', 'baru')->count();
        $prosesCount = Laporan::where('status', 'diproses')->count();
        $selesaiCount = Laporan::where('status', 'selesai')->count();

        // 5 Laporan Terbaru
        $recentReports = Laporan::with('user')
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Data Chart (7 hari terakhir)
        $chartData = [];
        $labels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $labels[] = $date->isoFormat('ddd');
            $chartData[] = Laporan::whereDate('created_at', $date)->count();
        }

        return view('dashboardadmin.dashboardAdmin', compact(
            'totalCount',
            'baruCount',
            'prosesCount',
            'selesaiCount',
            'recentReports',
            'chartData',
            'labels'
        ));
    }

    public function semuaLaporan()
    {
        $laporans = Laporan::with('user')->orderBy('created_at', 'desc')->paginate(10);
        return view('dashboardadmin.semualaporan', compact('laporans'));
    }

    public function filterLaporan(Request $request)
    {
        $query = Laporan::with('user')->orderBy('created_at', 'desc');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('kategori')) {
            $query->where('kategori', $request->kategori);
        }

        $laporans = $query->paginate(10);
        return view('dashboardadmin.filterlaporan', compact('laporans'));
    }

    public function show($id)
    {
        $laporan = Laporan::with('user')->findOrFail($id);
        $kategoris = Kategori::all();

        $hasilNB = null;
        $teksLaporan = trim(($laporan->judul ?? '') . ' ' . ($laporan->deskripsi ?? '') . ' ' . ($laporan->lokasi ?? ''));
        try {
            $hasilNB = $this->nb->predict($teksLaporan);
        } catch (\Exception $e) {
            $hasilNB = null;
        }

        return view('dashboardadmin.detaillaporan', compact('laporan', 'kategoris', 'hasilNB'));
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:baru,diproses,selesai,ditolak',
            'kategori' => 'required|string|max:100',
            'is_training' => 'nullable|boolean',
        ]);

        $laporan = Laporan::with('user')->findOrFail($id);
        $oldStatus = $laporan->status;
        $laporan->status = $request->status;
        $laporan->kategori = $request->kategori;
        $laporan->is_training = $request->boolean('is_training');
        $laporan->save();

        // Kirim notifikasi ke pelapor jika status berubah
        if ($oldStatus !== $laporan->status && $laporan->user) {
            $laporan->user->notify(new LaporanStatusNotification($laporan));
        }

        return redirect()->back()->with('success', 'Detail laporan #' . $laporan->id . ' berhasil diperbarui!');
    }

    // ========================
    // PROFIL ADMIN
    // ========================
    public function profil()
    {
        $admin = Auth::user();
        return view('dashboardadmin.profiladmin', compact('admin'));
    }

    public function updateProfil(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'name'      => 'required|string|max:255',
            'email'     => 'required|email|unique:users,email,' . $admin->id,
            'telepon'   => 'nullable|string|max:20',
            'nip'       => 'nullable|string|max:30',
            'instansi'  => 'nullable|string|max:255',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name'     => $request->name,
            'email'    => $request->email,
            'telepon'  => $request->telepon,
            'nip'      => $request->nip,
            'instansi' => $request->instansi,
        ];

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $this->cloudinary->upload($request->file('foto_profil')->getRealPath(), 'profil');
        }

        $admin->update($data);

        return redirect()->route('admin.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $admin = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->with('tab', 'keamanan');
        }

        $admin->update(['password' => Hash::make($request->password)]);

        return redirect()->route('admin.profil')->with('success', 'Kata sandi berhasil diperbarui.')->with('tab', 'keamanan');
    }

    // ========================
    // MANAJEMEN USER
    // ========================
    public function users(Request $request)
    {
        $query = \App\Models\User::withCount('laporans')->latest();

        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $users = $query->paginate(10)->withQueryString();

        $totalUsers   = \App\Models\User::count();
        $totalAdmin   = \App\Models\User::where('role', 'admin')->count();
        $totalRegular = \App\Models\User::where('role', 'user')->count();
        $newThisMonth = \App\Models\User::whereMonth('created_at', now()->month)
                            ->whereYear('created_at', now()->year)->count();

        return view('dashboardadmin.manajemenuser', compact(
            'users', 'totalUsers', 'totalAdmin', 'totalRegular', 'newThisMonth'
        ));
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role'     => 'required|in:admin,user',
            'telepon'  => 'nullable|string|max:20',
            'nik'      => 'nullable|string|max:20',
            'nip'      => 'nullable|string|max:30',
            'instansi' => 'nullable|string|max:255',
            'alamat'   => 'nullable|string',
        ]);

        $user = \App\Models\User::create([
            'name'        => $request->name,
            'email'       => $request->email,
            'password'    => Hash::make($request->password),
            'role'        => $request->role,
            'telepon'     => $request->telepon,
            'nik'         => $request->nik,
            'nip'         => $request->nip,
            'instansi'    => $request->instansi,
            'alamat'      => $request->alamat,
            'is_active'   => true,
            'is_verified' => true,
        ]);

        return redirect()->route('admin.users')->with('success', 'User ' . $user->name . ' berhasil ditambahkan.');
    }

    public function userDetail($id)
    {
        $user = \App\Models\User::withCount('laporans')->findOrFail($id);
        $laporans = \App\Models\Laporan::where('user_id', $id)->latest()->paginate(5);
        return view('dashboardadmin.detailuser', compact('user', 'laporans'));
    }

    public function updateUser(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email,' . $user->id,
            'telepon'  => 'nullable|string|max:20',
            'nik'      => 'nullable|string|max:20',
            'nip'      => 'nullable|string|max:30',
            'instansi' => 'nullable|string|max:255',
            'alamat'   => 'nullable|string',
        ]);

        $user->update($request->only([
            'name', 'email', 'telepon', 'nik', 'nip', 'instansi', 'alamat'
        ]));

        return back()->with('success', 'Data informasi user berhasil diperbarui.');
    }


    public function updateUserRole(Request $request, $id)
    {
        $request->validate(['role' => 'required|in:admin,user']);

        $user = \App\Models\User::findOrFail($id);

        // Cegah admin mengubah role dirinya sendiri
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat mengubah role akun Anda sendiri.');
        }

        $user->update(['role' => $request->role]);
        return back()->with('success', "Role {$user->name} berhasil diubah menjadi {$request->role}.");
    }

    public function toggleUserStatus(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menonaktifkan akun Anda sendiri.');
        }

        $newStatus = $user->is_active ? 0 : 1;
        $user->update(['is_active' => $newStatus]);
        $label = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
        return back()->with('success', "Akun {$user->name} berhasil {$label}.");
    }

    public function deleteUser(Request $request, $id)
    {
        $user = \App\Models\User::findOrFail($id);

        if ($user->id === Auth::id()) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri.');
        }

        $name = $user->name;
        $user->delete();
        return redirect()->route('admin.users')->with('success', "Akun \"{$name}\" berhasil dihapus.");
    }
}
