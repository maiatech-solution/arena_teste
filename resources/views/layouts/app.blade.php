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

            <footer class="py-8 mt-auto print:hidden">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="flex flex-col md:flex-row justify-between items-center gap-4 opacity-60 hover:opacity-100 transition-opacity duration-500">
                        <div class="text-[10px] font-black uppercase text-gray-400 tracking-[0.2em] italic">
                            © {{ date('Y') }} • Gestão de Arenas Profissional
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <span class="text-[9px] font-bold text-gray-400 uppercase italic">Desenvolvido por</span>
                            <a href="https://www.maiatechsolution.com.br/" target="_blank" class="flex items-center gap-1.5 group transition-transform hover:scale-105">
                                <span class="text-xs font-black text-indigo-600 dark:text-indigo-400 tracking-tighter uppercase group-hover:text-indigo-500"> Maiatech </span>
                                <span class="px-1.5 py-0.5 bg-indigo-600 dark:bg-indigo-500 text-white text-[8px] font-black rounded uppercase italic shadow-sm group-hover:bg-indigo-400 transition-colors"> Solution </span>
                            </a>
                        </div>
                    </div>
                </div>
            </footer>
        </div>

        {{-- 🔐 MODAL DE AUTORIZAÇÃO --}}
        <div id="modalAutorizacao" class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[10000] flex items-center justify-center p-4">
            <div class="bg-gray-900 border border-gray-800 p-8 rounded-[3rem] max-w-sm w-full text-center shadow-2xl border-t-orange-500/50">
                <div class="text-5xl mb-4">🔐</div>
                <h2 class="text-xl font-black text-white uppercase italic mb-2">Autorização Requerida</h2>
                <p class="text-gray-400 text-[10px] uppercase tracking-[0.2em] mb-8 font-bold leading-relaxed">
                    Ação restrita. Um <span class="text-orange-500">Gestor</span> deve autorizar.
                </p>

                <div class="space-y-4">
                    <input type="email" id="supervisor_email" placeholder="E-MAIL DO SUPERVISOR" 
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white text-xs font-black placeholder-gray-500 focus:ring-2 focus:ring-orange-500 uppercase">
                    
                    <input type="password" id="supervisor_password" placeholder="SENHA SECRETA" 
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white text-xs font-black placeholder-gray-500 focus:ring-2 focus:ring-orange-500 uppercase">
                    
                    <button onclick="processarAutorizacao()" id="btnConfirmarAuth"
                        class="w-full py-5 bg-orange-600 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-orange-500 transition-all shadow-lg shadow-orange-900/20">
                        Confirmar Autorização
                    </button>

                    <button onclick="fecharModalAuth()" 
                        class="w-full py-4 bg-transparent text-gray-500 font-black rounded-2xl uppercase text-[9px] tracking-widest hover:text-white transition-all">
                        Cancelar
                    </button>
                </div>
            </div>
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

            /** 🛡️ LÓGICA DE AUTORIZAÇÃO CORRIGIDA (TIMING SEGURO) **/
            let acaoPendente = null;

            function requisitarAutorizacao(callback) {
                const userRole = "{{ auth()->user()->role ?? 'cliente' }}";

                if (userRole === 'gestor' || userRole === 'admin') {
                    callback();
                    return;
                }

                acaoPendente = callback;
                document.getElementById('modalAutorizacao').classList.remove('hidden');
                document.getElementById('supervisor_email').focus();
            }

            async function processarAutorizacao() {
                const email = document.getElementById('supervisor_email').value;
                const password = document.getElementById('supervisor_password').value;
                const btn = document.getElementById('btnConfirmarAuth');

                if (!email || !password) return alert("Preencha as credenciais.");

                btn.innerText = "VERIFICANDO...";
                btn.disabled = true;

                try {
                    const response = await fetch("{{ route('admin.autorizar_acao') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({ email, password })
                    });

                    const data = await response.json();

                    if (response.ok && data.success) {
                        // 🚀 Preserva a ação antes de limpar o modal
                        const execFunc = acaoPendente;
                        fecharModalAuth();
                        if (typeof execFunc === 'function') execFunc();
                    } else {
                        alert(data.message || "Acesso negado.");
                        btn.innerText = "CONFIRMAR AUTORIZAÇÃO";
                        btn.disabled = false;
                    }
                } catch (error) {
                    alert("Erro de conexão.");
                    btn.innerText = "CONFIRMAR AUTORIZAÇÃO";
                    btn.disabled = false;
                }
            }

            function fecharModalAuth() {
                document.getElementById('modalAutorizacao').classList.add('hidden');
                document.getElementById('supervisor_email').value = '';
                document.getElementById('supervisor_password').value = '';
                acaoPendente = null;
            }
        </script>
    </body>
</html>