<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $laporans = DB::table('laporans')
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->get();

        foreach ($laporans as $laporan) {
            $text = strtolower(($laporan->judul ?? '') . ' ' . ($laporan->deskripsi ?? ''));
            $kategoriBaru = $this->getCorrectKategori($text, $laporan->kategori);

            if ($kategoriBaru !== $laporan->kategori) {
                DB::table('laporans')
                    ->where('id', $laporan->id)
                    ->update(['kategori' => $kategoriBaru]);
            }
        }
    }

    public function down(): void {}

    private function getCorrectKategori(string $text, string $currentKategori): string
    {
        // Keywords untuk setiap kategori
        $keywords = [
            'ketertiban' => [
                'pemalakan', 'perampokan', 'pencurian', 'penjambretan', 'begal',
                'balap liar', 'balapan liar', 'tawuran', 'parkir liar',
                'pengamen', 'pesta miras', 'minuman keras', 'orang gila',
                'mengamuk', 'gangguan jiwa', 'preman', 'pungli', 'penipuan',
                'judi', 'narkoba', 'miras', 'mabuk', 'onar', 'keributan',
                'kekerasan', 'pengeroyokan', 'baku hantam', 'santet',
                'PKL', 'pedagang kaki lima', 'trotoar', 'macet',
            ],
            'kebersihan' => [
                'sampah', 'limbah', 'penebangan pohon', 'pohon liar',
                'selokan tersumbat', 'banjir', 'sungai', 'bangkai',
                'bakar sampah', 'plastik', 'berserakan', 'kotor',
                'bau', 'menumpuk', 'membersihkan', 'kebersihan',
                'lingkungan', 'pencemaran', 'tinja', ' BAB',
            ],
            'infrastruktur' => [
                'jalan berlubang', 'jalan rusak', 'aspal', 'jembatan',
                'pipa', 'tiang listrik', 'irigasi', 'pager', 'pagar',
                'tol', 'drainase', 'saluran air', 'gorong-gorong',
                'jalan', 'berlubang', 'retak', 'roboh', 'miring',
                'penyebab banjir', 'water hammer',
            ],
            'fasilitas' => [
                'lampu jalan', 'lampu mati', 'halte', 'taman', 'toilet',
                'kursi', 'gym', 'papan petunjuk', 'rambu', 'plang',
                'wahana bermain', 'ayunan', 'perosotan', ' fasilitas ',
                'bus', 'halte bus', 'pos ronda', 'masjid', 'mushola',
            ],
            'lainnya' => [
                'kehilangan', 'dompet', 'KTP', 'pelayanan lambat',
                'apresiasi', 'terima kasih', 'pertanyaan', 'bansos',
                'aplikasi error', 'posyandu', 'saran', 'informasi',
                'pendaftaran', 'keluhan', 'saran',
            ],
        ];

        // Hitung skor untuk setiap kategori berdasarkan jumlah keyword yang cocok
        $scores = [];
        foreach ($keywords as $kategori => $kws) {
            $score = 0;
            foreach ($kws as $kw) {
                if (str_contains($text, trim($kw))) {
                    $score++;
                }
            }
            $scores[$kategori] = $score;
        }

        // Ambil kategori dengan skor tertinggi
        arsort($scores);
        $bestKategori = array_key_first($scores);

        // Jika tidak ada keyword yang cocok, pertahankan kategori asli
        if ($scores[$bestKategori] === 0) {
            return $currentKategori;
        }

        return $bestKategori;
    }
};
