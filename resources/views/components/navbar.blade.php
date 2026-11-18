<header class="sticky top-0 z-50 glass-panel border-b border-slate-800">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand Logo -->
            <div class="flex items-center gap-3">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-indigo-600 via-indigo-500 to-sky-400 flex items-center justify-center shadow-lg shadow-indigo-500/25 group-hover:scale-105 transition-transform duration-200">
                        <svg class="w-6 h-6 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
                            <path d="M2 12h20"/>
                        </svg>
                    </div>
                    <span class="text-xl font-bold tracking-tight bg-gradient-to-r from-white via-slate-100 to-slate-400 bg-clip-text text-transparent">
                        Tour<span class="text-indigo-400">Box</span>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-medium">
                <a href="{{ route('home') }}" class="text-slate-300 hover:text-white transition-colors">Beranda</a>
                <a href="{{ route('demo') }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full bg-indigo-500/10 text-indigo-400 hover:bg-indigo-500/20 border border-indigo-500/20 transition-all">
                    <span class="w-2 h-2 rounded-full bg-indigo-400 animate-pulse"></span>
                    Coba Demo 360
                </a>

                @auth
                    <a href="{{ route('profile') }}" class="text-slate-300 hover:text-white transition-colors">Profil Saya</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-slate-400 hover:text-rose-400 transition-colors">Keluar</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="text-slate-300 hover:text-white transition-colors">Masuk</a>
                    <a href="{{ route('register') }}" class="px-4 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-semibold shadow-lg shadow-indigo-600/30 transition-all">
                        Daftar Gratis
                    </a>
                @endauth
            </nav>

            <!-- Mobile menu toggle button -->
            <div class="flex md:hidden">
                <button @click="mobileMenuOpen = !mobileMenuOpen" type="button" class="p-2 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800">
                    <svg x-show="!mobileMenuOpen" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenuOpen" x-cloak class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Dropdown Menu -->
    <div x-show="mobileMenuOpen" x-cloak class="md:hidden glass-panel border-b border-slate-800 px-4 pt-2 pb-4 space-y-2">
        <a href="{{ route('home') }}" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Beranda</a>
        <a href="{{ route('demo') }}" class="block px-3 py-2 rounded-lg text-indigo-400 bg-indigo-500/10 font-semibold">Coba Demo 360</a>
        @auth
            <a href="{{ route('profile') }}" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Profil Saya</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-left px-3 py-2 rounded-lg text-rose-400 hover:bg-slate-800">Keluar</button>
            </form>
        @else
            <a href="{{ route('login') }}" class="block px-3 py-2 rounded-lg text-slate-200 hover:bg-slate-800">Masuk</a>
            <a href="{{ route('register') }}" class="block text-center px-4 py-2.5 rounded-xl bg-indigo-600 text-white font-semibold">Daftar Akun Baru</a>
        @endauth
    </div>
</header>
