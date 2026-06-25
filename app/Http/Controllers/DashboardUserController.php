<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Laporan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use App\Models\Support;
use App\Models\Kategori;
use App\Services\CloudinaryService;
use App\Services\NaiveBayesService;

class DashboardUserController extends Controller
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
        $user = Auth::user();

        $totalLaporan = Laporan::where('user_id', $user->id)->count();
        $laporanDiproses = Laporan::where('user_id', $user->id)->where('status', 'diproses')->count();
        $laporanSelesai = Laporan::where('user_id', $user->id)->where('status', 'selesai')->count();

        $laporans = Laporan::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get();

        // Fetch reports from other users
        $laporanLain = Laporan::with(['user', 'supports'])
            ->where('user_id', '!=', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        // Notifikasi belum dibaca
        $unreadNotifications = $user->unreadNotifications;
        $unreadCount = $unreadNotifications->count();

        return view('dashboarduser.dashboarduser', compact(
            'totalLaporan',
            'laporanDiproses',
            'laporanSelesai',
            'laporans',
            'laporanLain',
            'unreadNotifications',
            'unreadCount'
        ));
    }

    public function create()
    {
        $kategoris = Kategori::all();
        return view('dashboarduser.buatlaporan', compact('kategoris'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'judul'   => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'deskripsi' => 'required|string',
            'lokasi'  => 'nullable|string|max:255',
            'foto'    => 'required|array|min:1',
            'foto.*'  => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        $fotoPaths = [];
        if ($request->hasFile('foto')) {
            foreach ($request->file('foto') as $file) {
                $fotoPaths[] = $this->cloudinary->upload($file->getRealPath(), 'laporans');
            }
        }

        // Dapatkan prediksi NB untuk teks laporan
        $teksLaporan = trim(($request->judul ?? '') . ' ' . ($request->deskripsi ?? '') . ' ' . ($request->lokasi ?? ''));
        $hasilNB = $this->nb->predict($teksLaporan);
        $kategoriNB = $hasilNB['kategori'] ?? null;
        $probNB = $hasilNB['probabilitas'] ?? 0;

        // Tentukan kategori final:
        // - Jika NB punya prediksi dengan confidence >= 60%, gunakan prediksi NB
        // - Jika tidak, gunakan pilihan user
        $kategoriFinal = $request->kategori;
        $kategoriUser = $request->kategori;

        if ($kategoriNB && $kategoriNB !== $request->kategori && $probNB >= 60) {
            $kategoriFinal = $kategoriNB;
        }

        $laporan = Laporan::create([
            'user_id'          => Auth::id(),
            'judul'            => $request->judul,
            'kategori'         => $kategoriFinal,
            'kategori_asli_user' => ($kategoriUser !== $kategoriFinal) ? $kategoriUser : null,
            'deskripsi'        => $request->deskripsi,
            'lokasi'           => $request->lokasi,
            'foto'             => $fotoPaths,
            'status'           => 'baru',
            'is_training'      => false,
        ]);

        // Kirim notifikasi ke semua admin
        $admins = \App\Models\User::where('role', 'admin')->get();
        foreach ($admins as $admin) {
            $admin->notify(new \App\Notifications\NewLaporanAdminNotification($laporan));
        }

        $pesan = 'Laporan berhasil dikirim!';
        if ($kategoriNB && $kategoriNB !== $request->kategori && $probNB >= 60) {
            $pesan = 'Laporan berhasil dikirim! Kategori disesuaikan menjadi "' . ucfirst($kategoriNB) . '" berdasarkan analisis sistem.';
        }

        return redirect()->route('dashboarduser.index')->with('success', $pesan);
    }

    public function show($id)
    {
        // Allow viewing any report if user is logged in
        $laporan = Laporan::with(['user', 'supports'])->findOrFail($id);
        
        // Check if user has supported this report
        $hasSupported = $laporan->supports()->where('user_id', Auth::id())->exists();
        $supportCount = $laporan->supports()->count();

        return view('dashboarduser.detaillaporan', compact('laporan', 'hasSupported', 'supportCount'));
    }

    public function laporansaya()
    {
        $user = Auth::user();
        $laporans = Laporan::where('user_id', $user->id)->get();
        return view('dashboarduser.laporansaya', compact('laporans'));
    }

    // ========================
    // NOTIFIKASI
    // ========================
    public function notifikasi()
    {
        $user = Auth::user();
        $notifications = $user->notifications()->orderBy('created_at', 'desc')->paginate(15);

        // Tandai semua sebagai sudah dibaca
        $user->unreadNotifications->markAsRead();

        return view('dashboarduser.notifikasi', compact('notifications'));
    }

    public function markNotifRead(Request $request)
    {
        $user = Auth::user();
        if ($request->id) {
            $notif = $user->notifications()->find($request->id);
            if ($notif) $notif->markAsRead();
        } else {
            $user->unreadNotifications->markAsRead();
        }
        return response()->json(['success' => true]);
    }

    // ========================
    // PROFIL USER
    // ========================
    public function profil()
    {
        $user = Auth::user();
        $totalLaporan = Laporan::where('user_id', $user->id)->count();
        $unreadCount = $user->unreadNotifications()->count();
        return view('dashboarduser.profiluser', compact('user', 'totalLaporan', 'unreadCount'));
    }

    public function updateProfil(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email,' . $user->id,
            'telepon'     => 'nullable|string|max:20',
            'alamat'      => 'nullable|string|max:500',
            'nik'         => 'nullable|string|max:20',
            'foto_profil' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'telepon' => $request->telepon,
            'alamat'  => $request->alamat,
            'nik'     => $request->nik,
        ];

        if ($request->hasFile('foto_profil')) {
            $data['foto_profil'] = $this->cloudinary->upload($request->file('foto_profil')->getRealPath(), 'profil');
        }

        // Automatic verification logic
        if (!empty($data['nik']) && !empty($data['telepon']) && !empty($data['alamat']) && !empty($data['name'])) {
            $data['is_verified'] = true;
        } else {
            $data['is_verified'] = false;
        }

        $user->update($data);

        return redirect()->route('dashboarduser.profil')->with('success', 'Profil berhasil diperbarui.');
    }

    public function updatePassword(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'current_password' => 'required',
            'password'         => 'required|min:8|confirmed',
        ]);

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Kata sandi saat ini tidak sesuai.'])->with('tab', 'keamanan');
        }

        $user->update(['password' => Hash::make($request->password)]);

        return redirect()->route('dashboarduser.profil')->with('success', 'Kata sandi berhasil diperbarui.')->with('tab', 'keamanan');
    }

    // ========================
    // DUKUNGAN LAPORAN
    // ========================
    public function toggleSupport($id)
    {
        $user_id = Auth::id();
        $support = Support::where('user_id', $user_id)->where('laporan_id', $id)->first();

        if ($support) {
            $support->delete();
            $status = 'unsupported';
        } else {
            Support::create([
                'user_id' => $user_id,
                'laporan_id' => $id
            ]);
            $status = 'supported';
        }

        $count = Support::where('laporan_id', $id)->count();

        return response()->json([
            'success' => true,
            'status' => $status,
            'count' => $count
        ]);
    }

    // ========================
    // EDIT & HAPUS LAPORAN
    // ========================
    public function edit($id)
    {
        $user = Auth::user();
        $laporan = Laporan::where('user_id', $user->id)->findOrFail($id);
        
        if ($laporan->status !== 'baru') {
            return redirect()->route('dashboarduser.laporan')->with('error', 'Laporan yang sedang diproses tidak dapat diubah.');
        }

        $unreadCount = $user->unreadNotifications()->count();
        $kategoris = Kategori::all();

        return view('dashboarduser.editlaporan', compact('laporan', 'unreadCount', 'kategoris'));
    }

    public function update(Request $request, $id)
    {
        $user = Auth::user();
        $laporan = Laporan::where('user_id', $user->id)->findOrFail($id);

        if ($laporan->status !== 'baru') {
            return redirect()->route('dashboarduser.laporan')->with('error', 'Laporan yang sedang diproses tidak dapat diubah.');
        }

        $request->validate([
            'judul'   => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'deskripsi' => 'required|string',
            'lokasi'  => 'nullable|string|max:255',
            'foto'    => 'nullable|array',
            'foto.*'  => 'image|mimes:jpeg,png,jpg|max:5120',
        ]);

        // Dapatkan prediksi NB untuk teks laporan yang diupdate
        $teksLaporan = trim(($request->judul ?? '') . ' ' . ($request->deskripsi ?? '') . ' ' . ($request->lokasi ?? ''));
        $hasilNB = $this->nb->predict($teksLaporan);
        $kategoriNB = $hasilNB['kategori'] ?? null;
        $probNB = $hasilNB['probabilitas'] ?? 0;

        $kategoriFinal = $request->kategori;
        $kategoriUser = $request->kategori;

        if ($kategoriNB && $kategoriNB !== $request->kategori && $probNB >= 60) {
            $kategoriFinal = $kategoriNB;
        }

        $data = [
            'judul'            => $request->judul,
            'kategori'         => $kategoriFinal,
            'kategori_asli_user' => ($kategoriUser !== $kategoriFinal) ? $kategoriUser : $laporan->kategori_asli_user,
            'deskripsi'        => $request->deskripsi,
            'lokasi'           => $request->lokasi,
        ];

        if ($request->hasFile('foto')) {
            $fotoPaths = [];
            foreach ($request->file('foto') as $file) {
                $fotoPaths[] = $this->cloudinary->upload($file->getRealPath(), 'laporans');
            }
            $data['foto'] = $fotoPaths;
        }

        $laporan->update($data);

        $pesan = 'Laporan berhasil diperbarui!';
        if ($kategoriNB && $kategoriNB !== $request->kategori && $probNB >= 60) {
            $pesan = 'Laporan berhasil diperbarui! Kategori disesuaikan menjadi "' . ucfirst($kategoriNB) . '" berdasarkan analisis sistem.';
        }

        return redirect()->route('dashboarduser.laporan')->with('success', $pesan);
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $laporan = Laporan::where('user_id', $user->id)->findOrFail($id);

        if ($laporan->status !== 'baru') {
            return redirect()->route('dashboarduser.laporan')->with('error', 'Laporan yang sedang diproses tidak dapat dihapus.');
        }

        // Hapus foto dari storage
        if ($laporan->foto) {
            foreach ($laporan->foto as $foto) {
                Storage::disk('public')->delete($foto);
            }
        }

        $laporan->delete();

        return redirect()->route('dashboarduser.laporan')->with('success', 'Laporan berhasil dihapus.');
    }
}
