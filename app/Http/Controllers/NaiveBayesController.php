<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Laporan;
use App\Services\NaiveBayesService;
use App\Models\NaiveBayesClass;
use App\Models\NaiveBayesWord;
use Illuminate\Support\Facades\DB;

class NaiveBayesController extends Controller
{
    protected NaiveBayesService $nb;

    public function __construct(NaiveBayesService $nb)
    {
        $this->nb = $nb;
    }

    /**
     * Halaman utama manajemen Naive Bayes — dashboard klasifikasi
     */
    public function index()
    {
        $modelStats = $this->nb->getModelStats();

        // Data untuk tabel evaluasi (semua laporan)
        $laporans = Laporan::with('user')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->latest()
            ->take(20)
            ->get();

        // Distribusi kategori data training
        $distribusiKategori = Laporan::whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->selectRaw('kategori, COUNT(*) as total')
            ->groupBy('kategori')
            ->orderByDesc('total')
            ->get();

        return view('dashboardadmin.naivebayes', compact('modelStats', 'laporans', 'distribusiKategori'));
    }

    /**
     * Training model Naive Bayes
     * Menggunakan semua laporan yang sudah memiliki kategori
     */
    public function train(Request $request)
    {
        $result = $this->nb->train();

        if ($result['status'] === 'success') {
            $pesan = "Model berhasil dilatih! {$result['total_dokumen']} dokumen, "
                   . "{$result['total_kelas']} kelas, {$result['total_kata_unik']} kata unik.";
            return redirect()->route('admin.naivebayes')->with('success', $pesan);
        }

        return redirect()->route('admin.naivebayes')->with('error', $result['message']);
    }

    /**
     * Klasifikasikan satu laporan menggunakan Naive Bayes
     */
    public function klasifikasi(Request $request)
    {
        $request->validate([
            'teks' => 'required|string|min:3',
        ]);

        $hasil = $this->nb->predict($request->teks);

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($hasil);
        }

        return redirect()->route('admin.naivebayes')
            ->with('hasil_klasifikasi', $hasil)
            ->with('teks_input', $request->teks);
    }

    /**
     * Evaluasi akurasi model — uji seluruh data laporan yang sudah ada kategorinya
     */
    public function evaluasi()
    {
        $modelStats = $this->nb->getModelStats();

        if (!$modelStats['terlatih']) {
            return redirect()->route('admin.naivebayes')
                ->with('error', 'Model belum dilatih. Lakukan training terlebih dahulu.');
        }

        $batchResult = $this->nb->batchPredict(false);

        return view('dashboardadmin.naivebayes_evaluasi', compact('batchResult', 'modelStats'));
    }

    /**
     * Prediksi kategori via AJAX — digunakan di form buat laporan
     */
    public function predictAjax(Request $request)
    {
        $request->validate([
            'judul'     => 'nullable|string',
            'deskripsi' => 'nullable|string',
            'lokasi'    => 'nullable|string',
        ]);

        $text = trim(
            ($request->judul ?? '') . ' ' .
            ($request->deskripsi ?? '') . ' ' .
            ($request->lokasi ?? '')
        );

        if (strlen($text) < 5) {
            return response()->json(['kategori' => null, 'pesan' => 'Teks terlalu pendek.']);
        }

        $hasil = $this->nb->predict($text);

        return response()->json([
            'kategori'    => $hasil['kategori'],
            'probabilitas' => $hasil['probabilitas'],
            'probabilitas_semua' => $hasil['probabilitas_semua'] ?? [],
            'token_digunakan' => $hasil['token_digunakan'] ?? 0,
            'pesan'       => $hasil['pesan'],
        ]);
    }

    /**
     * Reset / hapus model yang tersimpan
     */
    public function reset()
    {
        DB::table('naive_bayes_words')->truncate();
        DB::table('naive_bayes_classes')->truncate();

        return redirect()->route('admin.naivebayes')
            ->with('success', 'Model Naive Bayes berhasil direset.');
    }
}
