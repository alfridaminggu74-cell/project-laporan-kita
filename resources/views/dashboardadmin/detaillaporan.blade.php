<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no"/>
    <title>Detail Laporan #LPK-{{ $laporan->id }} - LaporanKita Admin</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet"/>
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@300..600,0..1&display=swap" rel="stylesheet"/>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Inter', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            500: '#14b8a6', // Teal
                            600: '#0d9488',
                            800: '#115e59',
                            900: '#0f172a', // Slate 900
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .material-symbols-outlined { font-variation-settings: 'FILL' 0, 'wght' 400, 'GRAD' 0, 'opsz' 24; }
        .icon-filled { font-variation-settings: 'FILL' 1; }
        .custom-scrollbar::-webkit-scrollbar { height: 6px; width: 6px; }
        .custom-scrollbar::-webkit-scrollbar-track { background: transparent; }
        .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        .sidebar-transition { transition: transform 0.3s ease-in-out; }
    </style>
</head>
<body class="bg-[#FAFAFA] text-slate-900 antialiased flex h-screen overflow-hidden selection:bg-brand-500 selection:text-white relative">

    <!-- Mobile Sidebar Overlay -->
    <div id="sidebar-overlay" class="fixed inset-0 bg-slate-900/50 z-30 hidden md:hidden backdrop-blur-sm transition-opacity opacity-0" onclick="toggleSidebar()"></div>

    <!-- ==================== SIDEBAR ==================== -->
    <aside id="sidebar" class="fixed md:static inset-y-0 left-0 w-64 bg-brand-900 text-white flex flex-col h-full z-40 sidebar-transition -translate-x-full md:translate-x-0 shrink-0 shadow-2xl md:shadow-none">
        <div class="h-16 flex items-center px-6 border-b border-slate-700/50 shrink-0">
            <div class="flex items-center gap-2.5">
                <div class="bg-brand-500 text-white p-1.5 rounded flex items-center justify-center">
                    <span class="material-symbols-outlined icon-filled text-[18px]">maps_ugc</span>
                </div>
                <span class="text-lg font-bold tracking-tight text-white">Laporan<span class="text-brand-400">Kita</span></span>
                <span class="ml-2 px-1.5 py-0.5 rounded text-[9px] font-bold bg-rose-500 text-white uppercase tracking-wider">Admin</span>
            </div>
            <button class="md:hidden ml-auto p-1 text-slate-400 hover:text-white rounded" onclick="toggleSidebar()">
                <span class="material-symbols-outlined text-[20px]">close</span>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto py-6 px-4 space-y-1.5">
            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-2 mb-3">Menu Manajemen</div>
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">dashboard</span>
                Dashboard
            </a>
            <a href="{{ route('admin.laporan') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">list_alt</span>
                Semua Laporan
            </a>
            <a href="{{ route('admin.filter') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">filter_alt</span>
                Filter Laporan
            </a>
            <a href="{{ route('admin.kategori.index') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">category</span> Manajemen Kategori
            </a>
            <a href="{{ route('admin.users') }}" class="flex items-center gap-3 px-3 py-2.5 text-slate-300 hover:bg-slate-800 hover:text-white rounded-lg font-medium text-sm transition-colors group">
                <span class="material-symbols-outlined text-[20px] group-hover:text-brand-400 transition-colors">group</span>
                Manajemen User
            </a>
        </div>

        <!-- User Profile & Logout -->
        <div class="p-4 border-t border-slate-700/50 bg-slate-900/30">
            <div class="flex items-center gap-3 px-3 py-2 mb-2">
                @if(Auth::user()->foto_profil)
                    <img src="{{ str_starts_with(Auth::user()->foto_profil, 'http') ? Auth::user()->foto_profil : Storage::url(Auth::user()->foto_profil) }}" class="w-9 h-9 rounded-full border border-slate-600 object-cover" />
                @else
                    <div class="w-9 h-9 rounded-full bg-slate-700 flex items-center justify-center border border-slate-600">
                        <span class="material-symbols-outlined text-white text-[18px]">admin_panel_settings</span>
                    </div>
                @endif
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                    <p class="text-[11px] text-slate-400 truncate">Administrator</p>
                </div>
            </div>
            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="hidden">
                @csrf
            </form>
            <a href="#" onclick="if(confirm('Apakah Anda yakin ingin keluar sebagai Admin?')) { event.preventDefault(); document.getElementById('logout-form').submit(); }" class="flex items-center gap-3 px-3 py-2.5 text-rose-400 hover:bg-rose-500/10 hover:text-rose-300 rounded-lg font-medium text-sm transition-colors">
                <span class="material-symbols-outlined text-[20px]">logout</span>
                Keluar
            </a>
        </div>
    </aside>

    <!-- ==================== MAIN CONTENT AREA ==================== -->
    <main class="flex-1 flex flex-col h-full w-full overflow-hidden relative">
        <!-- Topbar Header -->
        <header class="h-16 bg-white border-b border-slate-200 flex items-center justify-between px-4 sm:px-8 shrink-0 shadow-sm z-30">
            <div class="flex items-center gap-4">
                <button class="md:hidden p-2 text-slate-500 hover:bg-slate-50 rounded-md" onclick="toggleSidebar()">
                    <span class="material-symbols-outlined">menu</span>
                </button>
                <a href="{{ route('admin.laporan') }}" class="p-1.5 text-slate-400 hover:text-brand-900 hover:bg-slate-50 rounded-lg transition-colors flex items-center">
                    <span class="material-symbols-outlined">arrow_back</span>
                </a>
                <div class="h-6 w-px bg-slate-200"></div>
                <h2 class="font-bold text-brand-900 text-sm hidden sm:block">Detail Laporan</h2>
                <span class="px-2 py-0.5 bg-slate-100 text-slate-600 text-xs font-mono font-semibold rounded">#LPK-{{ $laporan->id }}</span>
            </div>
            
            <div class="flex items-center gap-4 ml-auto">
                <!-- Search bar -->
                <div class="relative hidden lg:block w-64">
                    <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-[18px]">search</span>
                    <input type="text" placeholder="Cari ID Laporan..." class="w-full pl-9 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 transition-all" />
                </div>
                
                <!-- Notification Dropdown -->
                <div class="relative">
                    <button id="notif-toggle" class="p-2 text-slate-400 hover:text-brand-600 hover:bg-brand-50 rounded-full transition-all relative">
                        <span class="material-symbols-outlined text-[24px]">notifications</span>
                        @if(Auth::user()->unreadNotifications->count() > 0)
                        <span class="absolute top-2 right-2 w-2 h-2 bg-rose-500 rounded-full border border-white animate-pulse"></span>
                        @endif
                    </button>

                    <!-- Notif Dropdown Content -->
                    <div id="notif-dropdown" class="absolute top-full right-0 mt-3 w-[320px] bg-white rounded-2xl shadow-2xl border border-slate-100 transition-all duration-300 opacity-0 scale-95 pointer-events-none z-[110] overflow-hidden text-left">
                        <div class="p-4 border-b border-slate-50 flex items-center justify-between bg-slate-50/50">
                            <h4 class="text-sm font-bold text-slate-900">Pemberitahuan</h4>
                            <a href="#" class="text-[11px] font-bold text-brand-600 hover:text-brand-900 transition-colors">Tandai Dibaca</a>
                        </div>
                        <div class="max-h-[350px] overflow-y-auto">
                            @forelse(Auth::user()->notifications->take(5) as $notif)
                            <div class="p-4 hover:bg-slate-50 transition-colors border-b border-slate-50 group cursor-pointer">
                                <div class="flex gap-3">
                                    <div class="w-10 h-10 rounded-full @if($notif->unread()) bg-brand-50 text-brand-600 @else bg-slate-50 text-slate-400 @endif flex items-center justify-center shrink-0">
                                        <span class="material-symbols-outlined text-[18px]">@if($notif->data['type'] ?? '' === 'status') rule @else assignment_late @endif</span>
                                    </div>
                                    <div class="flex-grow min-w-0">
                                        <p class="text-xs text-slate-900 font-bold leading-snug group-hover:text-brand-900 transition-colors">
                                            {{ $notif->data['message'] ?? 'Pembaruan Laporan' }}
                                        </p>
                                        <div class="flex items-center gap-1.5 mt-1">
                                            <span class="text-[10px] font-medium text-slate-400">{{ $notif->created_at->diffForHumans() }}</span>
                                            @if($notif->unread())
                                            <span class="w-1.5 h-1.5 bg-brand-500 rounded-full"></span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @empty
                            <div class="p-10 text-center flex flex-col items-center gap-3">
                                <div class="w-12 h-12 rounded-full bg-slate-50 flex items-center justify-center">
                                    <span class="material-symbols-outlined text-slate-300 text-[24px]">notifications_off</span>
                                </div>
                                <p class="text-xs font-medium text-slate-500">Tidak ada pemberitahuan baru</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <div class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 custom-scrollbar relative">
            @if(session('success'))
                <div class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-xl flex items-center gap-3 text-emerald-800 text-sm animate-[fadeInUp_0.3s_ease-out]">
                    <span class="material-symbols-outlined text-emerald-500">check_circle</span>
                    {{ session('success') }}
                </div>
            @endif

            <div class="max-w-7xl mx-auto grid grid-cols-1 lg:grid-cols-12 gap-6 lg:gap-8">
                
                <!-- LEFT COLUMN: CONTENT -->
                <div class="lg:col-span-8 space-y-6">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <div class="p-5 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-sm font-bold text-slate-600 border border-slate-200">
                                    {{ strtoupper(substr($laporan->user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-brand-900">{{ $laporan->user->name }}</h3>
                                    <p class="text-[11px] text-slate-500 font-medium">{{ $laporan->user->email }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-3 gap-6 mb-8 p-4 bg-slate-50 rounded-xl border border-slate-100">
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Tanggal Laporan</p>
                                    <p class="text-xs font-semibold text-slate-700">{{ $laporan->created_at->format('d M Y') }}</p>
                                    <p class="text-[10px] text-slate-500">{{ $laporan->created_at->format('H:i') }} WITA</p>
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Kategori</p>
                                    @php
                                        $currentKategori = \App\Models\Kategori::where('slug', $laporan->kategori)->first();
                                        $kategoriNama = $currentKategori ? $currentKategori->nama : ucfirst($laporan->kategori ?? 'Umum');
                                    @endphp
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-semibold bg-white border border-slate-200 text-slate-600">
                                            {{ $kategoriNama }}
                                        </span>
                                        @if($laporan->is_training)
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100">
                                            <span class="material-symbols-outlined text-[10px]">check_circle</span> Training
                                        </span>
                                        @else
                                        <span class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-600 border border-amber-100">
                                            <span class="material-symbols-outlined text-[10px]">hourglass</span> Training?
                                        </span>
                                        @endif
                                    </div>
                                    @if($laporan->kategori_asli_user)
                                    <p class="text-[9px] text-rose-500 mt-1 font-medium">
                                        Dipilih user: <span class="font-semibold capitalize">{{ $laporan->kategori_asli_user }}</span>
                                    </p>
                                    @endif
                                </div>
                                <div>
                                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Lokasi</p>
                                    <p class="text-xs font-semibold text-slate-700 truncate" title="{{ $laporan->lokasi }}">{{ $laporan->lokasi }}</p>
                                </div>
                            </div>

                            <h1 class="text-xl md:text-2xl font-extrabold text-brand-900 tracking-tight mb-4 leading-snug">
                                {{ $laporan->judul }}
                            </h1>
                            
                            @if($laporan->foto && count($laporan->foto) > 0)
                            <div class="mb-6 space-y-4">
                                <div class="rounded-xl overflow-hidden border border-slate-200 bg-slate-100 shadow-sm aspect-video group relative">
                                    <img id="main-photo" src="{{ str_starts_with($laporan->foto[0], 'http') ? $laporan->foto[0] : Storage::url($laporan->foto[0]) }}" alt="Bukti Laporan" class="w-full h-full object-cover cursor-zoom-in" onclick="window.open(this.src)" />
                                    <div class="absolute inset-0 bg-black/40 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center pointer-events-none">
                                        <span class="text-white text-xs font-bold px-3 py-1.5 bg-black/50 rounded-full backdrop-blur-sm">Klik untuk Zoom</span>
                                    </div>
                                </div>
                                
                                @if(count($laporan->foto) > 1)
                                <div class="grid grid-cols-4 sm:grid-cols-6 lg:grid-cols-8 gap-2">
                                    @foreach($laporan->foto as $index => $img)
                                    <div class="aspect-square rounded-lg overflow-hidden border-2 @if($index === 0) border-brand-500 @else border-transparent @endif cursor-pointer hover:opacity-80 transition-all thumbnail-item shadow-sm" onclick="changeMainPhoto('{{ str_starts_with($img, 'http') ? $img : Storage::url($img) }}', this)">
                                        <img src="{{ str_starts_with($img, 'http') ? $img : Storage::url($img) }}" class="w-full h-full object-cover" />
                                    </div>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                            @endif

                            <div class="prose prose-sm max-w-none text-slate-600 leading-relaxed border-t border-slate-100 pt-5">
                                <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-2">Deskripsi Laporan</h4>
                                <p class="whitespace-pre-line">{{ $laporan->deskripsi }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT COLUMN: ACTIONS -->
                <div class="lg:col-span-4 space-y-6">
                    <div class="sticky top-8">
                        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                            <div class="p-5 border-b border-slate-100 bg-brand-900 text-white flex items-center gap-2">
                                <span class="material-symbols-outlined text-[20px]">admin_panel_settings</span>
                                <h3 class="font-bold text-sm">Panel Tindakan Admin</h3>
                            </div>
                            
                            <form action="{{ route('admin.laporan.update', $laporan->id) }}" method="POST" class="p-6 space-y-5">
                                @csrf
                                <div class="flex items-center justify-between mb-2">
                                    <span class="text-xs font-semibold text-slate-500">Status Saat Ini:</span>
                                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded text-[10px] font-bold uppercase tracking-wider @if($laporan->status === 'baru') text-rose-600 bg-rose-50 border-rose-100 @elseif($laporan->status === 'diproses') text-amber-600 bg-amber-50 border-amber-100 @else text-emerald-600 bg-emerald-50 border-emerald-100 @endif">
                                        {{ $laporan->status }}
                                    </span>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Kategori Laporan</label>
                                    <select name="kategori" class="w-full p-3 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 font-medium" required>
                                        @foreach($kategoris as $kat)
                                            <option value="{{ $kat->slug }}" {{ $laporan->kategori === $kat->slug ? 'selected' : '' }}>
                                                {{ $kat->nama }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-slate-700 mb-2 uppercase tracking-wide">Ubah Status</label>
                                    <select name="status" class="w-full p-3 bg-white border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 font-medium">
                                        <option value="baru" {{ $laporan->status === 'baru' ? 'selected' : '' }}>🔴 Baru (Menunggu)</option>
                                        <option value="diproses" {{ $laporan->status === 'diproses' ? 'selected' : '' }}>🟡 Sedang Diproses</option>
                                        <option value="selesai" {{ $laporan->status === 'selesai' ? 'selected' : '' }}>🟢 Selesai</option>
                                        <option value="ditolak" {{ $laporan->status === 'ditolak' ? 'selected' : '' }}>⚫ Ditolak / Spam</option>
                                    </select>
                                </div>

                                <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-xl border border-slate-100">
                                    <input type="hidden" name="is_training" value="0">
                                    <input type="checkbox" name="is_training" id="is_training" value="1" {{ $laporan->is_training ? 'checked' : '' }} class="w-4 h-4 text-brand-600 bg-white border-slate-300 rounded focus:ring-brand-500 cursor-pointer">
                                    <label for="is_training" class="text-xs font-semibold text-slate-700 cursor-pointer select-none">
                                        Data Training NB
                                        <span class="block text-[10px] text-slate-400 font-normal mt-0.5">Gunakan laporan ini untuk melatih model Naive Bayes</span>
                                    </label>
                                </div>

                                <button type="submit" class="w-full bg-brand-900 text-white py-3.5 rounded-lg text-sm font-semibold hover:bg-slate-800 shadow-md transition-all active:scale-[0.98] flex items-center justify-center gap-2">
                                    Simpan Perubahan <span class="material-symbols-outlined text-[18px]">save</span>
                                </button>
                            </form>
                        </div>

                        @if(isset($hasilNB) && $hasilNB && $hasilNB['kategori'])
                        <div class="mt-6 bg-gradient-to-br from-brand-50 to-white rounded-xl border border-brand-100 p-5">
                            <h4 class="text-xs font-bold text-brand-900 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-brand-500">model_training</span> Prediksi Naive Bayes
                            </h4>
                            <div class="flex items-center justify-between mb-3">
                                <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Kategori Prediksi</span>
                                <span class="px-2 py-0.5 bg-brand-500/10 text-brand-700 text-xs font-bold rounded border border-brand-200 capitalize">
                                    {{ $hasilNB['kategori'] }}
                                </span>
                            </div>
                            <div class="mb-3">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="text-[10px] font-semibold text-slate-500 uppercase tracking-wider">Confidence</span>
                                    <span class="text-xs font-bold {{ $hasilNB['probabilitas'] >= 70 ? 'text-emerald-600' : 'text-amber-600' }}">{{ $hasilNB['probabilitas'] }}%</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-full rounded-full {{ $hasilNB['probabilitas'] >= 70 ? 'bg-emerald-500' : 'bg-amber-500' }}" style="width: {{ $hasilNB['probabilitas'] }}%"></div>
                                </div>
                            </div>
                            @if($laporan->kategori !== $hasilNB['kategori'])
                            <div class="flex items-center gap-1.5 p-2 bg-amber-50 border border-amber-100 rounded-lg">
                                <span class="material-symbols-outlined text-[14px] text-amber-600">warning</span>
                                <span class="text-[10px] font-medium text-amber-700">NB memprediksi kategori berbeda dari laporan ini</span>
                            </div>
                            @endif
                            @if(isset($hasilNB['probabilitas_semua']))
                            <div class="mt-3 space-y-1.5">
                                <p class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">Detail Probabilitas</p>
                                @foreach($hasilNB['probabilitas_semua'] as $kat => $prob)
                                <div class="flex items-center gap-2">
                                    <span class="text-[10px] capitalize text-slate-600 w-20 font-medium">{{ $kat }}</span>
                                    <div class="flex-1 h-1 bg-slate-100 rounded-full overflow-hidden">
                                        <div class="h-full rounded-full bg-brand-400" style="width: {{ $prob }}%"></div>
                                    </div>
                                    <span class="text-[9px] font-bold text-slate-400 w-8 text-right">{{ $prob }}%</span>
                                </div>
                                @endforeach
                            </div>
                            @endif
                        </div>
                        @endif

                        <div class="mt-6 bg-slate-50 rounded-xl border border-slate-200 p-5">
                            <h4 class="text-xs font-bold text-slate-900 uppercase tracking-wider mb-4 flex items-center gap-1.5">
                                <span class="material-symbols-outlined text-[16px] text-slate-400">history</span> Log Aktivitas
                            </h4>
                            <div class="relative ml-2 border-l-2 border-slate-200 space-y-4 pb-1">
                                <div class="relative">
                                    <div class="absolute -left-[21px] top-0 w-10 h-10 bg-slate-50 flex items-center justify-center">
                                        <div class="w-2.5 h-2.5 bg-brand-500 rounded-full ring-4 ring-slate-50"></div>
                                    </div>
                                    <div class="pl-4">
                                        <p class="text-xs font-semibold text-slate-900">Laporan Masuk (Sistem)</p>
                                        <p class="text-[10px] text-slate-500 mt-0.5">{{ $laporan->created_at->format('d M Y, H:i') }} WITA</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
             // Notification Toggle Logic
            const notifToggle = document.getElementById('notif-toggle');
            const notifDropdown = document.getElementById('notif-dropdown');

            if (notifToggle && notifDropdown) {
                notifToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    const isOpen = !notifDropdown.classList.contains('opacity-0');
                    if (isOpen) {
                        notifDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                        notifDropdown.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                    } else {
                        notifDropdown.classList.remove('opacity-0', 'scale-95', 'pointer-events-none');
                        notifDropdown.classList.add('opacity-100', 'scale-100', 'pointer-events-auto');
                    }
                });

                document.addEventListener('click', (e) => {
                    if (!notifDropdown.contains(e.target) && e.target !== notifToggle) {
                        notifDropdown.classList.add('opacity-0', 'scale-95', 'pointer-events-none');
                        notifDropdown.classList.remove('opacity-100', 'scale-100', 'pointer-events-auto');
                    }
                });
            }
        });

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
                overlay.classList.remove('opacity-100');
                overlay.classList.add('opacity-0');
                setTimeout(() => { overlay.classList.add('hidden'); }, 300);
            }
        }

        function changeMainPhoto(src, thumbEl) {
            document.getElementById('main-photo').src = src;
            // Reset borders
            document.querySelectorAll('.thumbnail-item').forEach(el => {
                el.classList.remove('border-brand-500');
                el.classList.add('border-transparent');
            });
            // Set active border
            thumbEl.classList.remove('border-transparent');
            thumbEl.classList.add('border-brand-500');
        }
    </script>
</body>
</html>