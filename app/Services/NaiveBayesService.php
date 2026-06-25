<?php

namespace App\Services;

use App\Models\Laporan;
use App\Models\NaiveBayesWord;
use App\Models\NaiveBayesClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * NaiveBayesService
 *
 * Implementasi algoritma Naive Bayes dengan Laplace Smoothing
 * untuk klasifikasi laporan pengaduan masyarakat berdasarkan
 * teks (judul + deskripsi + lokasi) ke dalam kategori laporan.
 *
 * Referensi: "Penerapan Metode Naive Bayes untuk Klasifikasi
 * Laporan Pengaduan Masyarakat pada Sistem Informasi Pengaduan Masyarakat"
 */
class NaiveBayesService
{
    /**
     * Daftar stopwords Bahasa Indonesia
     * Kata-kata umum yang tidak memiliki makna spesifik untuk klasifikasi
     */
    protected array $stopwords = [
        'yang', 'dan', 'di', 'ke', 'dari', 'untuk', 'dengan', 'ini', 'itu',
        'pada', 'adalah', 'dalam', 'atau', 'juga', 'ada', 'sudah', 'akan',
        'tidak', 'bisa', 'saya', 'kami', 'kita', 'mereka', 'dia', 'anda',
        'bapak', 'ibu', 'pak', 'bu', 'telah', 'sudah', 'belum', 'masih',
        'sangat', 'lebih', 'paling', 'agar', 'supaya', 'karena', 'oleh',
        'sehingga', 'namun', 'tetapi', 'tapi', 'maka', 'ketika', 'saat',
        'bila', 'kalau', 'jika', 'meski', 'walaupun', 'lagi', 'juga',
        'pun', 'pun', 'ya', 'oh', 'hah', 'eh', 'ah', 'nih', 'aja', 'dong',
        'sih', 'deh', 'kok', 'kan', 'lah', 'kah', 'toh', 'pula', 'hanya',
        'saja', 'sekali', 'memang', 'bahwa', 'bahkan', 'sempat', 'selalu',
        'selain', 'antara', 'hingga', 'sampai', 'sebelum', 'sesudah',
        'setelah', 'karena', 'sebab', 'akibat', 'melalui', 'tanpa', 'bagi',
        'tentang', 'terhadap', 'sejak', 'kemudian', 'lalu', 'serta',
        'seperti', 'sebagai', 'yaitu', 'yakni', 'misalnya', 'contoh',
        'mohon', 'tolong', 'harap', 'segera', 'cepat', 'baik', 'buruk',
        'tersebut', 'atas', 'bawah', 'depan', 'belakang', 'kanan', 'kiri',
        'dalam', 'luar', 'atas', 'bawah', 'sekitar', 'dekat', 'jauh',
        'mau', 'ingin', 'harus', 'perlu', 'boleh', 'dapat', 'bisa', 'mampu',
        'kami', 'kita', 'mereka', 'dia', 'ia', 'nya', 'nya',
    ];

    /**
     * Preprocessing teks: tokenisasi, case folding, stopword removal, stemming sederhana
     *
     * @param string $text Teks mentah
     * @return array Array token yang sudah diproses
     */
    public function tokenize(string $text): array
    {
        // 1. Case folding — semua huruf kecil
        $text = mb_strtolower($text);

        // 2. Hapus karakter khusus, angka, dan tanda baca — hanya sisakan huruf dan spasi
        $text = preg_replace('/[^a-z\s]/', ' ', $text);

        // 3. Tokenisasi — pecah berdasarkan spasi
        $tokens = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        // 4. Stopword removal — filter kata yang ada di daftar stopword
        $tokens = array_filter($tokens, function ($token) {
            return strlen($token) > 2 && !in_array($token, $this->stopwords);
        });

        // 5. Stemming sederhana (prefix stripping bahasa Indonesia)
        $tokens = array_map([$this, 'simpleStem'], $tokens);

        // 6. Hapus duplikat berlebih — kembalikan array token
        return array_values($tokens);
    }

    /**
     * Stemming sederhana untuk Bahasa Indonesia
     * Menghapus awalan (me-, pe-, ber-, ter-, ke-, se-, di-)
     * dan akhiran (-kan, -an, -i, -nya)
     *
     * @param string $word Kata yang akan di-stem
     * @return string Kata hasil stemming
     */
    protected function simpleStem(string $word): string
    {
        // Hapus akhiran
        $suffixes = ['nya', 'kan', 'an', 'i'];
        foreach ($suffixes as $suffix) {
            if (str_ends_with($word, $suffix) && strlen($word) - strlen($suffix) >= 3) {
                $word = substr($word, 0, -strlen($suffix));
                break;
            }
        }

        // Hapus awalan (dari yang paling panjang ke pendek)
        $prefixes = ['menge', 'mempe', 'mence', 'menge', 'memper', 'diper', 'diber',
                     'me', 'pe', 'ber', 'ter', 'ke', 'se', 'di'];
        foreach ($prefixes as $prefix) {
            if (str_starts_with($word, $prefix) && strlen($word) - strlen($prefix) >= 3) {
                $word = substr($word, strlen($prefix));
                break;
            }
        }

        return $word;
    }

    /**
     * TRAINING: Melatih model Naive Bayes dari data laporan yang sudah ada
     * Menggunakan laporan dengan kategori yang ditetapkan sebagai data training
     *
     * @param array|null $laporanIds ID laporan spesifik (null = semua laporan)
     * @return array Statistik hasil training
     */
    public function train(?array $laporanIds = null): array
    {
        // Ambil data laporan untuk training — hanya yang sudah diverifikasi
        $query = Laporan::whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->where('is_training', true);

        if ($laporanIds) {
            $query->whereIn('id', $laporanIds);
        }

        $laporans = $query->get();

        if ($laporans->isEmpty()) {
            return ['status' => 'error', 'message' => 'Tidak ada data laporan untuk training.'];
        }

        // Reset model sebelumnya
        DB::table('naive_bayes_words')->truncate();
        DB::table('naive_bayes_classes')->truncate();

        // Hitung frekuensi kata per kategori
        $wordFreq = [];    // ['kategori']['kata'] = frekuensi
        $classStats = [];  // ['kategori']['jumlah_dokumen', 'total_kata']

        foreach ($laporans as $laporan) {
            $kategori = strtolower(trim($laporan->kategori));

            // Gabungkan teks dari judul + deskripsi + lokasi
            $textGabung = ($laporan->judul ?? '') . ' '
                        . ($laporan->deskripsi ?? '') . ' '
                        . ($laporan->lokasi ?? '');

            $tokens = $this->tokenize($textGabung);

            if (!isset($classStats[$kategori])) {
                $classStats[$kategori] = ['jumlah_dokumen' => 0, 'total_kata' => 0];
                $wordFreq[$kategori] = [];
            }

            $classStats[$kategori]['jumlah_dokumen']++;
            $classStats[$kategori]['total_kata'] += count($tokens);

            foreach ($tokens as $token) {
                if (!isset($wordFreq[$kategori][$token])) {
                    $wordFreq[$kategori][$token] = 0;
                }
                $wordFreq[$kategori][$token]++;
            }
        }

        // Simpan ke database dalam bulk insert untuk performa
        foreach ($wordFreq as $kategori => $words) {
            foreach (array_chunk($words, 500, true) as $chunk) {
                $insertData = [];
                foreach ($chunk as $kata => $frekuensi) {
                    $insertData[] = [
                        'kategori'   => $kategori,
                        'kata'       => $kata,
                        'frekuensi'  => $frekuensi,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
                DB::table('naive_bayes_words')->insert($insertData);
            }
        }

        foreach ($classStats as $kategori => $stats) {
            NaiveBayesClass::create([
                'kategori'        => $kategori,
                'jumlah_dokumen'  => $stats['jumlah_dokumen'],
                'total_kata'      => $stats['total_kata'],
            ]);
        }

        // Hitung total kata unik dalam semua kelas (untuk Laplace smoothing)
        $totalUniqueWords = NaiveBayesWord::distinct('kata')->count('kata');

        return [
            'status'            => 'success',
            'total_dokumen'     => $laporans->count(),
            'total_kelas'       => count($classStats),
            'total_kata_unik'   => $totalUniqueWords,
            'distribusi_kelas'  => array_map(fn($s) => $s['jumlah_dokumen'], $classStats),
        ];
    }

    /**
     * PREDIKSI: Mengklasifikasikan teks laporan ke dalam kategori menggunakan Naive Bayes
     * Menggunakan log-probabilities untuk menghindari underflow
     *
     * Formula: log P(C|D) = log P(C) + Σ log P(wi|C)
     * Dengan Laplace Smoothing: P(wi|C) = (count(wi, C) + 1) / (count(C) + |V|)
     *
     * @param string $text Teks laporan yang akan diklasifikasikan
     * @return array Hasil prediksi ['kategori', 'probabilitas', 'semua_skor']
     */
    public function predict(string $text): array
    {
        // Ambil semua kelas dari database
        $classes = NaiveBayesClass::all();

        if ($classes->isEmpty()) {
            return [
                'kategori'    => null,
                'probabilitas' => 0,
                'semua_skor'  => [],
                'pesan'       => 'Model belum dilatih. Silakan lakukan training terlebih dahulu.',
            ];
        }

        // Tokenisasi teks input
        $tokens = $this->tokenize($text);

        if (empty($tokens)) {
            return [
                'kategori'    => null,
                'probabilitas' => 0,
                'semua_skor'  => [],
                'pesan'       => 'Teks tidak mengandung kata bermakna.',
            ];
        }

        // Total dokumen training (untuk menghitung prior P(C))
        $totalDokumen = $classes->sum('jumlah_dokumen');

        // Total kata unik dalam semua kelas (untuk Laplace smoothing denominator)
        $vocabulary = NaiveBayesWord::distinct('kata')->count('kata');

        $scores = [];

        foreach ($classes as $class) {
            // Log-prior: log P(C) = log(jumlah_dokumen_kelas / total_dokumen)
            $logPrior = log($class->jumlah_dokumen / $totalDokumen);

            // Log-likelihood: Σ log P(wi|C)
            $logLikelihood = 0.0;
            $totalKataKelas = $class->total_kata;

            // Ambil semua kata yang relevan dari database sekaligus (1 query per kelas)
            $wordFreqs = NaiveBayesWord::where('kategori', $class->kategori)
                ->whereIn('kata', $tokens)
                ->pluck('frekuensi', 'kata')
                ->toArray();

            foreach ($tokens as $token) {
                $frekuensi = $wordFreqs[$token] ?? 0;

                // Laplace Smoothing: P(wi|C) = (count(wi,C) + 1) / (total_kata_C + |V|)
                $p_word_given_class = ($frekuensi + 1) / ($totalKataKelas + $vocabulary);
                $logLikelihood += log($p_word_given_class);
            }

            $scores[$class->kategori] = $logPrior + $logLikelihood;
        }

        // Pilih kategori dengan skor tertinggi
        arsort($scores);
        $bestKategori = array_key_first($scores);
        $bestScore    = $scores[$bestKategori];

        // Konversi log-scores ke probabilitas (softmax-like normalization)
        $probabilities = $this->normalizeToProbabilities($scores);

        return [
            'kategori'    => $bestKategori,
            'probabilitas' => round($probabilities[$bestKategori] * 100, 2),
            'semua_skor'  => array_map(fn($s) => round($s, 4), $scores),
            'probabilitas_semua' => array_map(fn($p) => round($p * 100, 2), $probabilities),
            'token_digunakan' => count($tokens),
            'pesan'       => 'Prediksi berhasil.',
        ];
    }

    /**
     * Normalisasi log-scores ke probabilitas menggunakan softmax
     *
     * @param array $logScores Array log-scores per kategori
     * @return array Probabilitas per kategori (jumlah = 1)
     */
    protected function normalizeToProbabilities(array $logScores): array
    {
        // Stabilitas numerik: kurangi dengan nilai maksimum sebelum exp
        $maxScore = max($logScores);
        $expScores = array_map(fn($s) => exp($s - $maxScore), $logScores);
        $sumExp = array_sum($expScores);

        return array_map(fn($e) => $e / $sumExp, $expScores);
    }

    /**
     * Mendapatkan statistik model yang tersimpan
     *
     * @return array Statistik model
     */
    public function getModelStats(): array
    {
        $classes = NaiveBayesClass::all();

        if ($classes->isEmpty()) {
            return ['terlatih' => false, 'kelas' => []];
        }

        $totalDokumen = $classes->sum('jumlah_dokumen');
        $totalWords   = NaiveBayesWord::count();
        $uniqueWords  = NaiveBayesWord::distinct('kata')->count('kata');

        return [
            'terlatih'          => true,
            'total_dokumen'     => $totalDokumen,
            'total_kelas'       => $classes->count(),
            'total_kata_entry'  => $totalWords,
            'total_kata_unik'   => $uniqueWords,
            'kelas'             => $classes->map(fn($c) => [
                'kategori'       => $c->kategori,
                'jumlah_dokumen' => $c->jumlah_dokumen,
                'total_kata'     => $c->total_kata,
                'updated_at'     => $c->updated_at?->format('d M Y, H:i'),
            ])->toArray(),
            'last_trained'      => $classes->max('updated_at')?->format('d M Y, H:i') ?? '-',
        ];
    }

    /**
     * Batch prediksi untuk seluruh laporan tanpa kategori
     * atau laporan yang ingin divalidasi kategorinya
     *
     * @param bool $onlyUnkategorized Hanya laporan tanpa kategori
     * @return array Hasil prediksi batch
     */
    public function batchPredict(bool $onlyUnkategorized = false): array
    {
        $query = Laporan::query();

        if ($onlyUnkategorized) {
            $query->where(function ($q) {
                $q->whereNull('kategori')->orWhere('kategori', '');
            });
        }

        $laporans = $query->get();
        $results  = [];

        foreach ($laporans as $laporan) {
            $text = ($laporan->judul ?? '') . ' ' . ($laporan->deskripsi ?? '');
            $hasil = $this->predict($text);
            $results[] = [
                'id'          => $laporan->id,
                'judul'       => $laporan->judul,
                'kategori_asli' => $laporan->kategori,
                'prediksi'    => $hasil['kategori'],
                'probabilitas' => $hasil['probabilitas'],
                'cocok'       => strtolower($laporan->kategori ?? '') === strtolower($hasil['kategori'] ?? ''),
            ];
        }

        // Hitung akurasi (untuk laporan yang sudah punya kategori)
        $denganKategori = array_filter($results, fn($r) => $r['kategori_asli'] !== null);
        $benar  = array_filter($denganKategori, fn($r) => $r['cocok']);
        $akurasi = count($denganKategori) > 0
            ? round(count($benar) / count($denganKategori) * 100, 2)
            : null;

        return [
            'total'   => count($results),
            'akurasi' => $akurasi,
            'hasil'   => $results,
        ];
    }
}
