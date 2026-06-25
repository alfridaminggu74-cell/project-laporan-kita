<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Fix: Pemalakan liar -> ketertiban (bukan kebersihan)
        DB::table('laporans')
            ->where('kategori', 'kebersihan')
            ->where(function ($q) {
                $q->where('judul', 'LIKE', '%pemalakan%')
                  ->orWhere('judul', 'LIKE', '%perampokan%')
                  ->orWhere('judul', 'LIKE', '%pencurian%')
                  ->orWhere('judul', 'LIKE', '%penjambretan%')
                  ->orWhere('deskripsi', 'LIKE', '%pemalakan%')
                  ->orWhere('deskripsi', 'LIKE', '%perampokan%');
            })
            ->update(['kategori' => 'ketertiban']);

        // Fix: Jalan berlubang -> infrastruktur (bukan ketertiban)
        DB::table('laporans')
            ->where('kategori', 'ketertiban')
            ->where(function ($q) {
                $q->where('judul', 'LIKE', '%berlubang%')
                  ->orWhere('judul', 'LIKE', '%rusak%')
                  ->orWhere('judul', 'LIKE', '%jalan%')
                  ->orWhere('deskripsi', 'LIKE', '%berlubang%')
                  ->orWhere('deskripsi', 'LIKE', '%aspal%')
                  ->orWhere('deskripsi', 'LIKE', '%jalan rusak%');
            })
            ->update(['kategori' => 'infrastruktur']);
    }

    public function down(): void
    {
        // Tidak bisa di-reverse secara otomatis karena konteks hilang
    }
};
