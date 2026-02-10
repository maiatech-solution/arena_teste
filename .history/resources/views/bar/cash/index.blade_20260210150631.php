<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        {{-- HEADER COM FILTRO DE DATA --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-10">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-white text-4xl font-black uppercase italic tracking-tighter">Gestão de <span class="text-green-500">Caixa</span></h1>
                    <span class="px-3 py-1 bg-gray-800 text-gray-500 text-[10px] font-black rounded-lg uppercase border border-gray-700">Módulo Bar</span>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <form action="{{ route('bar.cash.index') }}" method="GET" id="filterForm" class="flex items-center gap-2">
                        <input type="date" name="date" value="{{ $date ?? date('Y-m-d') }}"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-gray-900 border-2 border-gray-800 rounded-xl px-4 py-2 text-white text-xs font-black outline-none focus:border-green-500 transition-all">

                        @if (isset($date) && $date != date('Y-m-d'))
                            <a href="{{ route('bar.cash.index') }}" class="text-[10px] font-black text-orange-500 uppercase underline tracking-widest ml-2">Voltar para Hoje</a>
                        @endif
                    </form>
                </div>
            </div>

            {{-- BOTÕES DE AÇÃO COM TRAVA DE SEGURANÇA --}}
            @if ($openSession)
                <div class="flex flex-wrap gap-3">
                    <button onclick="requisitarAutorizacao(() => openModalMovement('sangria'))"
                        class="px-6 py-3 bg-red-600/10 border border-red-600/20 text-red-500 font-bold rounded-2xl uppercase text-xs hover:bg-red-600 hover:text-white transition-all shadow-lg">
                        🔻 Sangria
                    </button>

                    <button onclick="requisitarAutorizacao(() => openModalMovement('reforco'))"
                        class="px-6 py-3 bg-blue-600/10 border border-blue-600/20 text-blue-500 font-bold rounded-2xl uppercase text-xs hover:bg-blue-600 hover:text-white transition-all shadow-lg">
                        🔺 Reforço
                    </button>

                    <button onclick="requisitarAutorizacao(() => openModalClosing())"
                        class="px-8 py-3 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:scale-105 transition-all shadow-xl border-b-4 border-gray-300">
                        🔒 Encerrar Turno
                    </button>
                </div>
            @endif
        </div>

        {{-- LÓGICA DE EXIBIÇÃO CENTRAL --}}
        @if (!$openSession && $date == date('Y-m-d'))
            <div class="max-w-xl mx-auto mt-20 text-center animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-gray-900 rounded-[3rem] p-12 border border-gray-800 shadow-2xl shadow-green-900/5">
                    <div class="w-20 h-20 bg-gray-800 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-gray-700 text-4xl text-gray-400">🔓</div>
                    <h2 class="text-white text-2xl font-black uppercase mb-2">Novo Turno</h2>
                    <p class="text-gray-500 mb-8 uppercase text-[10px] font-bold tracking-widest leading-relaxed px-10">
                        Não há sessões de caixa ativas no momento. <br>Inicie um novo turno para processar vendas.
                    </p>

                    <form action="{{ route('bar.cash.open') }}" method="POST">
                        @csrf
                        <div class="text-left mb-6">
                            <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Troco Inicial de Gaveta</label>
                            <input type="number" name="opening_balance" step="0.01" value="0.00" required
                                class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-3xl font-black text-center focus:border-green-500 outline-none transition-all shadow-inner">
                        </div>
                        <button type="submit" class="w-full py-6 bg-green-600 hover:bg-green-500 text-white font-black rounded-3xl uppercase tracking-widest shadow-lg shadow-green-900/40 transition-all active:scale-95">
                            Abrir Turno de Trabalho
                        </button>
                    </form>
                </div>
            </div>
        @elseif(!$currentSession)
            <div class="py-20 text-center opacity-20">
                <p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">Nenhum registo nesta data</p>
            </div>
        @else
            {{-- CARDS FINANCEIROS --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 relative overflow-hidden group shadow-2xl border-l-4 border-l-green-500">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Dinheiro em Gaveta</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$ {{ number_format($dinheiroGeral ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-500">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Aportes / Reforços</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$ {{ number_format($reforcos ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-red-500">
                    <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Sangrias / Saídas</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$ {{ number_format($sangrias ?? 0, 2, ',', '.') }}</span>
                </div>
                <div class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-400">
                    <span class="text-[10px] font-black text-blue-400 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Faturamento Digital</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$ {{ number_format($faturamentoDigital ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- HISTÓRICO DE MOVIMENTAÇÕES --}}
            <div class="bg-gray-900 rounded-[3rem] border border-gray-800 overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-800 flex justify-between items-center bg-gray-800/20">
                    <h3 class="text-white font-black uppercase italic tracking-widest text-lg">Histórico do Turno</h3>
                    <div class="flex items-center gap-4">
                        <span class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter italic font-black underline decoration-green-500/30 underline-offset-4 font-black">Faturado: R$ {{ number_format($totalBruto ?? 0, 2, ',', '.') }}</span>
                        <span class="{{ $currentSession->status == 'open' ? 'text-green-500 animate-pulse' : 'text-red-500' }} text-[10px] font-black uppercase tracking-widest flex items-center gap-2 font-black">
                            <span class="w-2 h-2 {{ $currentSession->status == 'open' ? 'bg-green-500' : 'bg-red-500' }} rounded-full"></span> {{ $currentSession->status == 'open' ? 'Aberto' : 'Fechado' }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/20">
                                <th class="p-6">Hora</th>
                                <th class="p-6">Descrição</th>
                                <th class="p-6">Operador</th>
                                <th class="p-6 text-right font-black">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @forelse($movements as $mov)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="p-6 text-gray-500 font-bold text-xs">{{ $mov->created_at->format('H:i') }}</td>
                                    <td class="p-6">
                                        <span class="text-white block font-black text-xs uppercase tracking-tight">{{ $mov->description }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-[8px] uppercase font-black px-2 py-0.5 rounded border {{ $mov->type == 'sangria' ? 'bg-red-500/10 text-red-500 border-red-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20' }}">
                                                {{ $mov->type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="p-6 text-gray-400 text-[10px] font-bold uppercase italic tracking-widest">{{ $mov->user->name }}</td>
                                    <td class="p-6 text-right font-black italic text-xl {{ $mov->type == 'sangria' ? 'text-red-500' : 'text-white' }}">
                                        {{ $mov->type == 'sangria' ? '-' : '' }} R$ {{ number_format($mov->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="p-24 text-center opacity-20"><p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">Sem movimentações</p></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    @include('bar.cash.modals.movements')
    @include('bar.cash.modals.closing')

    <script>
    // 🧠 MEMÓRIA GLOBAL DO CAIXA
    // Usamos 'var' para permitir re-declaração sem erro se o layout já as tiver
    window.supervisorMemoriaEmail = window.supervisorMemoriaEmail || "";
    window.supervisorMemoriaPass = window.supervisorMemoriaPass || "";

    // Se 'acaoPendente' já existe no layout, apenas garantimos que está acessível
    if (typeof acaoPendente === 'undefined') {
        var acaoPendente = null;
    }

    /**
     * 1. MONITOR DE TECLADO:
     * Captura os dados no exato momento da digitação.
     */
    document.addEventListener('input', function (e) {
        if (e.target.id === 'authEmail' || e.target.name === 'email') {
            window.supervisorMemoriaEmail = e.target.value;
        }
        if (e.target.id === 'authPassword' || e.target.name === 'password') {
            window.supervisorMemoriaPass = e.target.value;
        }
    });

    /**
     * 2. REQUISITAR AUTORIZAÇÃO:
     */
    function requisitarAutorizacao(callback) {
        const userRole = "{{ auth()->user()->role }}";

        if (userRole === 'admin' || userRole === 'gestor') {
            callback();
            return;
        }

        acaoPendente = callback;
        const modalAuth = document.getElementById('modalAuthSupervisor');
        if (modalAuth) {
            modalAuth.classList.remove('hidden');
            const emailField = document.getElementById('authEmail');
            if(emailField) emailField.focus();
        }
    }

    /**
     * 3. ENVIAR COM AUTORIZAÇÃO:
     */
    function enviarComAutorizacao(idFormulario) {
        const form = document.getElementById(idFormulario);

        // Pega da memória capturada pelo monitor de input
        const emailFinal = window.supervisorMemoriaEmail || document.getElementById('authEmail')?.value;
        const passFinal = window.supervisorMemoriaPass || document.getElementById('authPassword')?.value;

        console.log("🚀 Debug Caixa - Enviando Form:", idFormulario, " | Autorizador:", emailFinal);

        if (form && emailFinal && passFinal) {
            const mEmail = form.querySelector('input[name="supervisor_email"]') || form.querySelector('#mirror_email');
            const mPass = form.querySelector('input[name="supervisor_password"]') || form.querySelector('#mirror_password');

            if (mEmail && mPass) {
                mEmail.value = emailFinal;
                mPass.value = passFinal;
                form.submit();
            } else {
                alert("Erro técnico: Campos de espelho não encontrados no formulário.");
            }
        } else {
            alert("⚠️ Credenciais não capturadas. Por favor, digite novamente no modal de autorização.");
        }
    }
    </script>
</x-bar-layout>
