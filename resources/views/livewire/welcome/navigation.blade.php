<div class="flex items-center lg:items-center">
    @auth
        <a
            href="{{ url('/dashboard') }}"
            class="group flex items-center gap-3 px-4 py-3 lg:px-5 lg:py-2.5 rounded-lg text-gray-300 lg:text-white lg:bg-blue-600 lg:hover:bg-blue-700 hover:bg-white/10 hover:text-white transition-all shadow-sm"
        >
            <svg class="w-5 h-5 lg:w-4 lg:h-4 opacity-70 lg:opacity-100 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/></svg>
            <span class="font-medium lg:text-sm">Dashboard</span>
        </a>
    @else
        <a
            href="{{ route('login') }}"
            class="group flex items-center gap-3 px-4 py-3 lg:px-5 lg:py-2.5 rounded-lg text-gray-300 lg:text-white lg:bg-blue-600 lg:hover:bg-blue-700 hover:bg-white/10 hover:text-white transition-all shadow-sm"
        >
            <svg class="w-5 h-5 lg:w-4 lg:h-4 opacity-70 lg:opacity-100 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
            <span class="font-medium lg:text-sm">Ingreso programa</span>
        </a>
    @endauth
</div>


