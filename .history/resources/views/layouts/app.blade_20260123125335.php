<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100 dark:bg-gray-900">
            @include('layouts.navigation')

            <div class="fixed top-5 right-5 z-[100] space-y-3 w-full max-sm:px-4 max-w-sm">
                @if (session('success'))
                    <div id="toast-success" class="flex items-center p-4 text-gray-800 bg-white dark:bg-gray-800 rounded-[1.5rem] shadow-2xl border-l-4 border-emerald-500 transform transition-all duration-500 translate-x-0" role="alert">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-emerald-500 bg-emerald-100 rounded-full dark:bg-emerald-900 dark:text-emerald-300">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                        </div>
                        <div class="ml-3 text-xs font-black uppercase tracking-tight">{{ session('success') }}</div>
                    </div>
                @endif

                @if (session('error'))
                    <div id="toast-error" class="flex items-center p-4 text-gray-800 bg-white dark:bg-gray-800 rounded-[1.5rem] shadow-2xl border-l-4 border-red-500" role="alert">
                        <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 text-red-500 bg-red-100 rounded-full">
                            <span class="font-bold">!</span>
                        </div>
                        <div class="ml-3 text-xs font-black uppercase tracking-tight">{{ session('error') }}</div>
                    </div>
                @endif
            </div>

            @isset($header)
                <header class="bg-white dark:bg-gray-800 shadow">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                {{ $slot }}
            </main>

            {{-- FOOTER MAIATECH SOLUTION --}}
            <footer class="py-8 mt-auto print:hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 opacity-60 hover:opacity-100 transition-opacity duration-500">
                        <div class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] italic">
                            © {{ date('Y') }} • Gestão de Arenas Profissional
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-bold text-gray-400 uppercase italic">Desenvolvido por</span>
                            <a href="https://www.maiatechsolution.com.br/" target="_blank" class="flex items-center gap-1.5 group transition-transform hover:scale-105">
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 tracking-tighter uppercase group-hover:text-indigo-500">
                                    Maiatech
                                </span>
                                <span class="px-1.5 py-0.5 bg-indigo-600 dark:bg-indigo-500 text-white text-[8px] font-black rounded uppercase italic shadow-sm group-hover:bg-indigo-500 transition-colors">
                                    Solution
                                </span>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        @stack('scripts')

        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const toasts = ['toast-success', 'toast-error'];
                toasts.forEach(id => {
                    const el = document.getElementById(id);
                    if (el) {
                        setTimeout(() => {
                            el.style.opacity = '0';
                            el.style.transform = 'translateX(100px)';
                            setTimeout(() => el.remove(), 500);
                        }, 4000);
                    }
                });
            });
        </script>
    </body>
</html>
