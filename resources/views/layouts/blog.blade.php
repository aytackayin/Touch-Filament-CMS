<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth" x-data="{ 
    darkMode: localStorage.getItem('darkMode') === 'true'
}" :class="{ 'dark': darkMode }" x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('site.title', 'Filament CMS Blog') }}</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
        href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,400&display=swap"
        rel="stylesheet">

    <!-- Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://cdn.tailwindcss.com?plugins=typography,forms"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                        display: ['Outfit', 'sans-serif'],
                    },
                    colors: {
                        indigo: {
                            50: '#f5f7ff',
                            600: '#4f46e5',
                        }
                    }
                }
            }
        }
    </script>

    <!-- Alpine.js Plugins -->
    <script defer src="https://unpkg.com/@alpinejs/intersect@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        h1,
        h2,
        h3,
        h4,
        h5,
        h6 {
            font-family: 'Outfit', sans-serif;
        }

        .glass-nav {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .dark .glass-nav {
            background: rgba(0, 0, 0, 0.3);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.03);
            backdrop-filter: blur(8px);
            -webkit-backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark .glass-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .premium-gradient {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        }

        .text-gradient {
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(135deg, #6366f1 0%, #a855f7 50%, #ec4899 100%);
        }
    </style>
</head>

<body class="antialiased bg-slate-50 dark:bg-slate-950 text-slate-900 dark:text-slate-100 min-h-screen">

    <!-- Navigation -->
    <nav x-data="{ scrolled: false }" x-init="window.addEventListener('scroll', () => scrolled = window.scrollY > 20)"
        :class="scrolled ? 'glass-nav py-4 shadow-2xl' : 'bg-transparent py-8'"
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-700 ease-[cubic-bezier(0.4,0,0.2,1)]">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16">
            <div class="flex items-center justify-between relative">
                <!-- Left: Menu Button -->
                <div class="flex items-center flex-1">
                    <button @click="$dispatch('toggle-mobile-menu')"
                        class="group flex items-center justify-center w-12 h-12 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 opacity-50 hover:opacity-100 hover:scale-105 hover:shadow-xl transition-all duration-300">
                        <svg class="w-6 h-6 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 6h16M4 12h16m-7 6h7"></path>
                        </svg>
                    </button>
                </div>

                <!-- Center: Logo -->
                <div class="absolute left-1/2 -translate-x-1/2 flex flex-col items-center">
                    <a href="{{ route('home') }}" class="group">
                        <div class="flex flex-col items-center">
                            <span
                                class="text-3xl font-black tracking-tighter text-gradient group-hover:scale-105 transition-transform duration-300">
                                BLOG.
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Right: Dark-Mode Only -->
                <div class="flex items-center justify-end flex-1">
                    <!-- Dark Mode Toggle -->
                    <button @click="darkMode = !darkMode"
                        class="p-3 rounded-2xl bg-slate-900 dark:bg-white text-white dark:text-slate-900 opacity-50 hover:opacity-100 hover:scale-105 hover:shadow-xl transition-all duration-300">
                        <svg x-show="!darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z">
                            </path>
                        </svg>
                        <svg x-show="darkMode" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                            x-cloak>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364-6.364l-.707.707M6.343 17.657l-.707.707M16.242 17.657l.707-.707M6.343 6.343l.707-.707M12 8a4 4 0 100 8 4 4 0 000-8z">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Overlay Menu -->
    <div x-data="{ open: false }" 
        @toggle-mobile-menu.window="open = !open" 
        x-init="$watch('open', value => value ? document.body.classList.add('overflow-hidden') : document.body.classList.remove('overflow-hidden'))"
        x-show="open"
        x-transition:enter="transition ease-out duration-500" x-transition:enter-start="opacity-0 translate-x-full"
        x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-300"
        x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-full"
        class="fixed inset-0 z-[100] bg-slate-50/95 dark:bg-slate-950/95 backdrop-blur-xl flex flex-col justify-center items-center" x-cloak>
        
        <button @click="open = false" class="absolute top-8 right-8 p-4 rounded-full bg-white/10 hover:bg-white/20 text-slate-900 dark:text-white transition-all hover:rotate-90 duration-300">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>

        <div class="flex flex-col items-center space-y-8 w-full max-w-sm px-6 text-center">
            
            <!-- Navigation Links -->
            <a href="{{ route('home') }}" class="group relative inline-block">
                <span class="text-4xl md:text-5xl font-black tracking-tighter uppercase text-slate-900 dark:text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-indigo-500 group-hover:to-purple-500 transition-all duration-300">Home</span>
                <span class="absolute -bottom-2 left-1/2 w-0 h-1 bg-indigo-500 group-hover:w-full group-hover:left-0 transition-all duration-300"></span>
            </a>

            <a href="{{ route('blog.index') }}" class="group relative inline-block">
                <span class="text-4xl md:text-5xl font-black tracking-tighter uppercase text-slate-900 dark:text-white group-hover:text-transparent group-hover:bg-clip-text group-hover:bg-gradient-to-r group-hover:from-indigo-500 group-hover:to-purple-500 transition-all duration-300">Blog</span>
                <span class="absolute -bottom-2 left-1/2 w-0 h-1 bg-indigo-500 group-hover:w-full group-hover:left-0 transition-all duration-300"></span>
            </a>
            
            <div class="w-24 h-px bg-slate-200 dark:bg-slate-800 my-8"></div>

            @auth
                <a href="{{ route('dashboard') }}" class="text-2xl font-bold tracking-widest uppercase text-slate-500 hover:text-slate-900 dark:hover:text-white transition-colors">Dashboard</a>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xl font-bold tracking-widest uppercase text-red-500/70 hover:text-red-500 transition-colors mt-4">
                        Log Out
                    </button>
                </form>
            @else
                <a href="{{ route('login') }}" class="text-3xl font-bold tracking-widest uppercase text-slate-900 dark:text-white hover:text-indigo-500 transition-colors">Login</a>
                @if (Route::has('register'))
                    <a href="{{ route('register') }}" class="text-xl font-bold tracking-widest uppercase text-slate-400 hover:text-indigo-500 transition-colors">Register</a>
                @endif
            @endauth
        </div>
    </div>

    <!-- Page Content -->
    <main>
        {{ $slot }}
    </main>

    <!-- Footer -->
    <footer class="bg-slate-900 text-slate-100 py-20">
        <div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-16">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-12 border-b border-slate-800 pb-12 mb-12">
                <div>
                    <span class="text-2xl font-black tracking-tighter text-white mb-6 block">BLOG.</span>
                    <p class="text-slate-400 max-w-sm">
                        Discover amazing stories and latest updates from our community.
                    </p>
                </div>
                <div>
                    <h4 class="text-white font-bold uppercase tracking-widest text-sm mb-6">Quick Links</h4>
                    <ul class="space-y-4 text-slate-400">
                        <li><a href="{{ route('home') }}" class="hover:text-white transition-colors">Home</a></li>
                        <li><a href="{{ route('blog.index') }}" class="hover:text-white transition-colors">Blog</a></li>
                    </ul>
                </div>
                <div>
                    @php
                        $settings = app(\App\Settings\GeneralSettings::class);
                        $instagram = $settings->getCustomSetting('instagram');
                    @endphp
                    @if($instagram)
                        <h4 class="text-white font-bold uppercase tracking-widest text-sm mb-6">Follow Us</h4>
                        <a href="{{ $instagram }}" target="_blank"
                            class="flex items-center space-x-2 text-slate-400 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 24 24">
                                <path
                                    d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z">
                                </path>
                            </svg>
                            <span>@instagram</span>
                        </a>
                    @endif
                </div>
            </div>
            <div class="flex flex-col md:flex-row justify-between items-center text-slate-500 text-sm">
                <p>&copy; {{ date('Y') }} BLOG. All rights reserved.</p>
                <div class="flex space-x-6 mt-4 md:mt-0">
                    <p>Powered by Laravel v{{ Illuminate\Foundation\Application::VERSION }}</p>
                </div>
            </div>
        </div>
    </footer>

    @livewireScripts
</body>

</html>