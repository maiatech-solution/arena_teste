<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Bar Manager - {{ config('app.name') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="bg-gray-950 text-gray-100 antialiased selection:bg-orange-500 selection:text-white">
    <div class="min-h-screen flex flex-col">

        {{-- NAVIGATION ISOLADA --}}
        @include('layouts.navigation_bar')

        {{-- TOASTS DE NOTIFICAÇÃO --}}
        <div class="fixed top-5 right-5 z-[100] space-y-3 w-full max-sm:px-4 max-w-sm">
            @if (session('success'))
            <div id="toast-success" class="flex items-center p-4 text-gray-100 bg-gray-900 rounded-[1.5rem] shadow-2xl border-l-4 border-emerald-500 transform transition-all duration-500 translate-x-0" role="alert">
                <div class="ml-3 text-xs font-black uppercase tracking-tight">{{ session('success') }}</div>
            </div>
            @endif

            @if (session('error'))
            <div id="toast-error" class="flex items-center p-4 text-gray-100 bg-gray-900 rounded-[1.5rem] shadow-2xl border-l-4 border-red-500" role="alert">
                <div class="ml-3 text-xs font-black uppercase tracking-tight">{{ session('error') }}</div>
            </div>
            @endif
        </div>

        {{-- CONTEÚDO --}}
        <main class="flex-grow py-12">
            {{ $slot }}
        </main>

        {{-- FOOTER MAIATECH SOLUTION --}}
        <footer class="py-8 mt-auto print:hidden border-t border-gray-900">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex flex-col md:flex-row justify-between items-center gap-4 opacity-60 hover:opacity-100 transition-opacity duration-500">
                    <div class="text-[10px] font-black uppercase text-gray-500 tracking-[0.2em] italic">
                        © {{ date('Y') }} • Gestão de Arenas Profissional
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-[9px] font-bold text-gray-500 uppercase italic">Desenvolvido por</span>
                        <a href="https://www.maiatechsolution.com.br/" target="_blank" class="flex items-center gap-1.5 group transition-transform hover:scale-105">
                            <span class="text-xs font-black text-indigo-400 tracking-tighter uppercase group-hover:text-indigo-500">Maiatech</span>
                            <span class="px-1.5 py-0.5 bg-indigo-500 text-white text-[8px] font-black rounded uppercase italic shadow-sm group-hover:bg-indigo-400 transition-colors">Solution</span>
                        </a>
                    </div>
                </div>
            </div>
        </footer>
    </div>

    {{-- 🔐 MODAL GLOBAL DE AUTORIZAÇÃO (ESTILO DARK BAR) --}}
    <div id="modalAutorizacao" class="hidden fixed inset-0 bg-black/95 backdrop-blur-md z-[10000] flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 p-8 rounded-[3rem] max-w-sm w-full text-center shadow-2xl border-t-orange-500/50">
            <div class="text-5xl mb-4 text-orange-500">🔐</div>
            <h2 class="text-xl font-black text-white uppercase italic mb-2 tracking-tighter">Autorização Requerida</h2>
            <p class="text-gray-400 text-[10px] uppercase tracking-[0.2em] mb-8 font-bold leading-relaxed">
                Ação restrita. Um <span class="text-orange-500 italic">Supervisor</span> deve validar.
            </p>

            <div class="space-y-4">
                <input type="email" id="supervisor_email" placeholder="E-MAIL DO SUPERVISOR"
                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white text-xs font-black placeholder-gray-600 focus:ring-2 focus:ring-orange-500 uppercase">

                <input type="password" id="supervisor_password" placeholder="SENHA SECRETA"
                    class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white text-xs font-black placeholder-gray-600 focus:ring-2 focus:ring-orange-500 uppercase">

                <button onclick="processarAutorizacao()" id="btnConfirmarAuth"
                    class="w-full py-5 bg-orange-600 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-orange-500 transition-all shadow-lg shadow-orange-900/20">
                    Confirmar Autorização
                </button>

                <button onclick="fecharModalAuth()"
                    class="w-full py-4 bg-transparent text-gray-500 font-black rounded-2xl uppercase text-[9px] tracking-widest hover:text-white transition-all">
                    Cancelar Operação
                </button>
            </div>
        </div>
    </div>

    @stack('scripts')

    <script>
        /** 1. LÓGICA DE LIMPEZA DE TOASTS **/
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

        /** 2. LÓGICA DE AUTORIZAÇÃO (VERSÃO CORRIGIDA) **/
        let acaoPendente = null;

        function requisitarAutorizacao(callback) {
            const userRole = "{{ auth()->user()->role }}";

            // Se já for admin/gestor, executa na hora e para aqui
            if (userRole === 'gestor' || userRole === 'admin') {
                return callback();
            }

            // Se for colaborador, guarda a função e abre o modal
            acaoPendente = callback;
            document.getElementById('modalAutorizacao').classList.remove('hidden');
            document.getElementById('supervisor_email').focus();
        }

        async function processarAutorizacao() {
            const email = document.getElementById('supervisor_email').value;
            const password = document.getElementById('supervisor_password').value;
            const btn = document.getElementById('btnConfirmarAuth');

            if (!email || !password) return;

            btn.innerText = "VALIDANDO...";
            btn.disabled = true;

            try {
                const response = await fetch("{{ route('admin.autorizar_acao') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        email,
                        password
                    })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // 🛡️ O SEGREDO ESTÁ AQUI:
                    // Primeiro salvamos a função em uma variável temporária
                    const acaoParaExecutar = acaoPendente;

                    // Fechamos o modal (que limpa o acaoPendente global)
                    fecharModalAuth();

                    // Agora sim, executamos a função salva (o .submit() do form)
                    if (typeof acaoParaExecutar === 'function') {
                        acaoParaExecutar();
                    }
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
            acaoPendente = null; // Limpa para a próxima autorização
        }
    </script>
</body>

</html>