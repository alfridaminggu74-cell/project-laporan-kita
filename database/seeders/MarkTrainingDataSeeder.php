<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Laporan;

class MarkTrainingDataSeeder extends Seeder
{
    protected array $trainingJuduls = [
        'Jalan berlubang besar di Jalan Raya Bogor',
        'Jembatan penghubung retak dan hampir roboh',
        'Pipa PDAM bocor menggenangi jalan',
        'Tiang listrik miring hampir roboh ke jalan',
        'Saluran irigasi warga jebol dan rusak',
        'Aspal jalan mengelupas sepanjang 100 meter',
        'Pagar pembatas jalan tol rusak parah',
        'Tumpukan sampah liar menumpuk di pasar',
        'Limbah industri dibuang ke sungai warga',
        'Penebangan pohon pelindung jalan secara liar',
        'Selokan tersumbat sampah plastik memicu banjir',
        'Sampah menumpuk di pinggir sungai',
        'Bangkai binatang di tengah jalan raya',
        'Warga membakar sampah plastik sembarangan',
        'Pedagang kaki lima mengokupasi trotoar jalan',
        'Aksi balap liar motor di malam hari',
        'Orang gila mengamuk di depan pertokoan',
        'Parkir liar mobil di jalan membuat macet parah',
        'Tawuran antarkelompok remaja bersenjata tajam',
        'Pengamen jalanan memaksa meminta uang di lampu merah',
        'Pesta minuman keras liar di taman kota',
        'Lampu penerangan jalan umum mati total',
        'Kaca halte bus pecah dan dicorat-coret',
        'Wahana bermain anak di taman kota rusak',
        'Toilet umum di taman mati air dan kotor',
        'Kursi halte bus patah tidak bisa diduduki',
        'Fasilitas gym outdoor taman kota patah',
        'Papan petunjuk jalan roboh diterjang angin',
        'Kehilangan dompet berisi dokumen penting KTP',
        'Pelayanan kantor kelurahan sangat lambat',
        'Apresiasi kinerja petugas kebersihan kelurahan',
        'Pertanyaan seputar pendaftaran bansos mandiri',
        'Kendala pendaftaran aplikasi online daerah',
        'Informasi jadwal posyandu balita bulan ini',
        'Saran penambahan area hijau taman perumahan',
    ];

    public function run(): void
    {
        foreach ($this->trainingJuduls as $judul) {
            Laporan::where('judul', $judul)->update(['is_training' => true]);
        }

        // Also mark all reports where kategori was corrected by the keyword fix
        // as potential training data
        $this->command?->info(count($this->trainingJuduls) . ' laporan ditandai sebagai data training.');
    }
}
