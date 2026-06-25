<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Evaluasi Naive Bayes - LaporanKita Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300..600,0..1&display=swap" rel="stylesheet"/>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: { extend: { fontFamily: { sans: ['Inter','sans-serif'] }, colors: { brand: { 50:'#f0fdfa',100:'#ccfbf1',400:'#2dd4bf',500:'#14b8a6',600:'#0d9488',800:'#115e59',900:'#0f172a' } } } }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0,'wght' 400,'GRAD' 0,'opsz' 24; }
        .icon-filled { font-variation-settings: 'FILL' 1; }
        .custom-scrollbar::-webkit-scrollbar { height:6px; width:6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background:transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background:#cbd5e1; border-radius:4px; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-[#FAFAFA] text-slate-900 antialiased flex h-screen overflow-hidden">

    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden md:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleSidebar()"></div>

    <!-- SIDEBAR -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-brand-900 text-white flex flex-col h-full z-40 sidebar-transition -translate-x-full md:translate-x-0 shrink-0 shadow-2xl md:shadow-none">
        <div class="h-16 flex items-center px-6 border-b border-slate-700/50">
            <div class="flex items-center gap-2.5">
                <div class="bg-brand-500 p-1.5 rounded"><span class="material-symbols-outlined icon-filled text-[18px]">maps_ugc</span></div>
                <span class="text-lg font-bold">Laporan<span class="text-brand-400">Kita</span></span>
                <span class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500 uppercase">Admin</span>
            </div>
            <button class="md:hidden ml-auto p-1 text-slate-400 hover:text-white" onclick="toggleSidebar()">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>
        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-3">Menu Manajemen</div>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400">dashboard</span> Dashboard
            </a>
            <a href="{{ route('admin.laporan') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400">list_alt</span> Semua Laporan
            </a>
            <a href="{{ route('admin.filter') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400">filter_alt</span> Filter Laporan
            </a>
            <a href="{{ route('admin.kategori.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">category</span> Manajemen Kategori
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400">group</span> Manajemen User
            </a>
            <a href="{{ route('admin.naivebayes') }}" class="flex items-center gap-3 px-3 py-2.5 bg-brand-800 text-white rounded-lg font-semibold text-sm border border-brand-700 shadow-inner">
                <span class="material-symbols-outlined icon-filled text-[20px] text-brand-400">model_training</span> Naive Bayes
            </a>
        </div>
        <div class="p-4 border-t border-slate-700/50">
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">@csrf</form>
            <a href="#" onclick="if(confirm('Keluar?')){document.getElementById('logout-form').submit()}" class="flex items-center gap-3 px-3 py-2.5 text-rose-400 hover:bg-rose-500/10 rounded-lg text-sm">
                <span class="material-symbols-outlined text-[20px]">logout</span> Keluar
            </a>
        </div>
    </aside>

    <!-- MAIN -->
    <main class="flex-1 flex flex-col h-full w-full overflow-hidden">
        <header class="h-16 bg-white border-b border-slate-200 flex items-center px-4 sm:px-8 gap-3 shrink-0 shadow-sm">
            <button class="md:hidden p-2 text-slate-500 hover:bg-slate-50 rounded-md" onclick="toggleSidebar()">
                <span class="material-symbols-outlined">menu</span>
            </button>
            <a href="{{ route('admin.naivebayes') }}" class="p-1.5 text-slate-400 hover:text-brand-900 rounded-lg transition-colors">
                <span class="material-symbols-outlined">arrow_back</span>
            </a>
            <div class="w-px h-6 bg-slate-200"></div>
            <span class="material-symbols-outlined text-brand-500">assessment</span>
            <h2 class="font-bold text-brand-900 text-lg">Evaluasi Model</h2>
            @if($batchResult['akurasi'] !== null)
            <span class="ml-auto px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-bold rounded-lg">
                Akurasi: {{ $batchResult['akurasi'] }}%
            </span>
            @endif
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 custom-scrollbar">
            <div class="max-w-7xl mx-auto space-y-6">

                <!-- RINGKASAN EVALUASI -->
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Total Laporan Diuji</p>
                        <p class="text-2xl font-extrabold text-brand-900">{{ $batchResult['total'] }}</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Prediksi Benar</p>
                        @php $benar = collect($batchResult['hasil'])->where('cocok', true)->count(); @endphp
                        <p class="text-2xl font-extrabold text-emerald-600">{{ $benar }}</p>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Prediksi Salah</p>
                        <p class="text-2xl font-extrabold text-rose-600">{{ $batchResult['total'] - $benar }}</p>
                    </div>
                    <div class="bg-emerald-50 border border-emerald-200 rounded-2xl shadow-sm p-5">
                        <p class="text-[10px] font-bold text-emerald-600 uppercase tracking-wider mb-1">Akurasi Model</p>
                        <p class="text-2xl font-extrabold text-emerald-700">{{ $batchResult['akurasi'] ?? 'N/A' }}{{ $batchResult['akurasi'] !== null ? '%' : '' }}</p>
                    </div>
                </div>

                <!-- INFO MODEL -->
                <div class="bg-brand-50 border border-brand-100 rounded-xl p-4 flex items-start gap-3 text-sm text-brand-800">
                    <span class="material-symbols-outlined text-brand-500 text-[20px] shrink-0 mt-0.5">info</span>
                    <div>
                        <p class="font-semibold mb-1">Tentang Evaluasi Ini</p>
                        <p class="text-brand-700">Model diuji menggunakan data yang <strong>sama</strong> dengan data training (in-sample evaluation). Untuk hasil yang lebih valid, gunakan cross-validation dengan data terpisah. Kategori dibandingkan secara case-insensitive.</p>
                        <p class="mt-1 text-brand-600 text-xs">Vocabulary: {{ number_format($modelStats['total_kata_unik']) }} kata unik | Kelas: {{ $modelStats['total_kelas'] }} | Dokumen training: {{ $modelStats['total_dokumen'] }}</p>
                    </div>
                </div>

                <!-- TABEL HASIL -->
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center gap-2">
                        <span class="material-symbols-outlined text-brand-500 text-[20px]">table_view</span>
                        <h3 class="font-bold text-sm text-brand-900">Hasil Prediksi Per Laporan</h3>
                    </div>
                    <div class="overflow-x-auto custom-scrollbar">
                        <table class="w-full text-left whitespace-nowrap">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200">
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">ID</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Judul Laporan</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Kategori Asli</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Prediksi NB</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Probabilitas</th>
                                    <th class="px-4 py-3 text-[11px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($batchResult['hasil'] as $hasil)
                                <tr class="hover:bg-slate-50 transition-colors" id="row-{{ $hasil['id'] }}">
                                    <td class="px-4 py-3 text-xs font-mono text-slate-500">#LPK-{{ $hasil['id'] }}</td>
                                    <td class="px-4 py-3 text-sm text-slate-700 max-w-[220px] truncate" title="{{ $hasil['judul'] }}">{{ $hasil['judul'] }}</td>
                                    <td class="px-4 py-3">
                                        <span class="kategori-asli px-2 py-0.5 bg-slate-100 text-slate-700 text-xs font-semibold rounded capitalize">
                                            {{ $hasil['kategori_asli'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="px-2 py-0.5 bg-brand-50 text-brand-700 text-xs font-semibold rounded border border-brand-100 capitalize">
                                            {{ $hasil['prediksi'] ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-2">
                                            <div class="w-16 h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                                <div class="h-full rounded-full prob-bar {{ $hasil['probabilitas'] >= 70 ? 'bg-emerald-500' : ($hasil['probabilitas'] >= 40 ? 'bg-amber-500' : 'bg-rose-400') }}"
                                                    data-width="{{ $hasil['probabilitas'] }}"></div>
                                            </div>
                                            <span class="text-xs font-bold text-slate-600">{{ $hasil['probabilitas'] }}%</span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($hasil['cocok'])
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 bg-emerald-50 border border-emerald-100 text-emerald-700 text-[10px] font-bold rounded uppercase">
                                            <span class="material-symbols-outlined text-[12px]">check_circle</span> Benar
                                        </span>
                                        @else
                                        <span class="status-badge inline-flex items-center gap-1 px-2 py-0.5 bg-rose-50 border border-rose-100 text-rose-700 text-[10px] font-bold rounded uppercase">
                                            <span class="material-symbols-outlined text-[12px]">cancel</span> Salah
                                        </span>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('sidebar-overlay');
            const isClosed = sidebar.classList.contains('-translate-x-full');
            if (isClosed) {
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                setTimeout(() => { overlay.classList.remove('opacity-0'); overlay.classList.add('opacity-100'); }, 10);
            } else {
                sidebar.classList.add('-translate-x-full');
                overlay.classList.remove('opacity-100'); overlay.classList.add('opacity-0');
                setTimeout(() => { overlay.classList.add('hidden'); }, 300);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            document.querySelectorAll('.prob-bar').forEach(function (el) {
                var w = el.getAttribute('data-width');
                if (w) el.style.width = w + '%';
            });
        });
    </script>
</body>
</html>
