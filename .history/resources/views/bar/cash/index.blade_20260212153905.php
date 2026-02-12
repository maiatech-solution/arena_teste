<x-bar-layout>
    <div class="max-w-[1600px] mx-auto px-6 py-8">

        @if (isset($caixaVencido) && $caixaVencido)
            <div
                class="mb-8 bg-red-600 border-2 border-white/20 p-6 rounded-[2rem] flex items-center justify-between animate-pulse">
                <div class="flex items-center gap-4">
                    <span class="text-3xl">⚠️</span>
                    <div>
                        <h4 class="text-white font-black uppercase italic leading-none">Atenção: Caixa do dia anterior!
                        </h4>
                        <p class="text-white/80 text-[10px] font-bold uppercase tracking-widest mt-1">
                            Este caixa foi aberto em
                            {{ \Carbon\Carbon::parse($openSession->opened_at)->format('d/m') }}. Feche-o antes de
                            iniciar o movimento de hoje.
                        </p>
                    </div>
                </div>
                <button onclick="tentarEncerrarTurno()"
                    class="bg-white text-red-600 px-6 py-2 rounded-xl font-black uppercase text-[10px] hover:scale-105 transition-all">
                    Fechar Agora
                </button>
            </div>
        @endif

        {{-- HEADER COM FILTRO DE DATA --}}
        <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-6 mb-10">
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-white text-4xl font-black uppercase italic tracking-tighter">Gestão de <span
                            class="text-green-500">Caixa</span></h1>
                    <span
                        class="px-3 py-1 bg-gray-800 text-gray-500 text-[10px] font-black rounded-lg uppercase border border-gray-700">Módulo
                        Bar</span>
                </div>

                <div class="mt-4 flex items-center gap-3">
                    <form action="{{ route('bar.cash.index') }}" method="GET" id="filterForm"
                        class="flex items-center gap-2">
                        <input type="date" name="date" value="{{ $date ?? date('Y-m-d') }}"
                            onchange="document.getElementById('filterForm').submit()"
                            class="bg-gray-900 border-2 border-gray-800 rounded-xl px-4 py-2 text-white text-xs font-black outline-none focus:border-green-500 transition-all">

                        @if (isset($date) && $date != date('Y-m-d'))
                            <a href="{{ route('bar.cash.index') }}"
                                class="text-[10px] font-black text-orange-500 uppercase underline tracking-widest ml-2">Voltar
                                para Hoje</a>
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

                    {{-- Na index.blade.php --}}
                    <button type="button" onclick="tentarEncerrarTurno()"
                        class="px-8 py-3 bg-white text-black font-black rounded-2xl uppercase text-[10px] tracking-widest hover:scale-105 transition-all shadow-xl border-b-4 border-gray-300">
                        🔒 Encerrar Turno
                    </button>

                    <script>
                        /**
                         * 🛡️ Verificação de Pré-fechamento
                         * Impede que o modal de autorização abra se houver pendências
                         */
                        function tentarEncerrarTurno() {
                            // Pega a variável injetada pelo PHP
                            const mesasAbertas = {{ $mesasAbertasCount }};

                            if (mesasAbertas > 0) {
                                // Exibe o erro e mata a execução aqui
                                alert("⚠️ OPERAÇÃO BLOQUEADA\n\nExistem " + mesasAbertas +
                                    " mesa(s) aberta(s) no sistema.\nVocê precisa finalizar todos os pagamentos antes de fechar o caixa."
                                );
                                return;
                            }

                            // Se não houver mesas, segue o fluxo normal de autorização
                            requisitarAutorizacao(() => openModalClosing());
                        }
                    </script>
                </div>
            @endif
        </div>

        {{-- LÓGICA DE EXIBIÇÃO CENTRAL --}}
        @if (!$openSession && $date == date('Y-m-d'))
            <div class="max-w-xl mx-auto mt-20 text-center animate-in fade-in slide-in-from-bottom-4 duration-500">
                <div class="bg-gray-900 rounded-[3rem] p-12 border border-gray-800 shadow-2xl shadow-green-900/5">
                    <div
                        class="w-20 h-20 bg-gray-800 rounded-3xl flex items-center justify-center mx-auto mb-6 border border-gray-700 text-4xl text-gray-400">
                        🔓</div>
                    <h2 class="text-white text-2xl font-black uppercase mb-2">Novo Turno</h2>
                    <p class="text-gray-500 mb-8 uppercase text-[10px] font-bold tracking-widest leading-relaxed px-10">
                        Não há sessões de caixa ativas no momento. <br>Inicie um novo turno para processar vendas.
                    </p>

                    <form action="{{ route('bar.cash.open') }}" method="POST" id="formOpenCash">
                        @csrf

                        {{-- 🔑 CAMPOS DE ESPELHO (Essenciais para enviar a senha do Adriano ao Controller) --}}
                        <input type="hidden" name="supervisor_email">
                        <input type="hidden" name="supervisor_password">

                        <div class="text-left mb-6">
                            <label
                                class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">
                                Troco Inicial de Gaveta
                            </label>
                            <input type="number" name="opening_balance" step="0.01" value="0.00" required
                                class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-3xl font-black text-center focus:border-green-500 outline-none transition-all shadow-inner font-mono">
                        </div>

                        {{-- 🚀 BOTÃO COM GATILHO DE AUTORIZAÇÃO --}}
                        <button type="button"
                            onclick="requisitarAutorizacao(() => enviarComAutorizacao('formOpenCash'))"
                            class="w-full py-6 bg-green-600 hover:bg-green-500 text-white font-black rounded-3xl uppercase tracking-widest shadow-lg shadow-green-900/40 transition-all active:scale-95">
                            Abrir Turno de Trabalho
                        </button>
                    </form>
                </div>
            </div>
        @elseif(!$currentSession)
            <div class="py-20 text-center opacity-20">
                <p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">Nenhum registo nesta data
                </p>
            </div>
        @else
            {{-- CARDS FINANCEIROS --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-10">
                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 relative overflow-hidden group shadow-2xl border-l-4 border-l-green-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Dinheiro
                        em Gaveta</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$
                        {{ number_format($dinheiroGeral ?? 0, 2, ',', '.') }}</span>
                </div>
                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Aportes
                        / Reforços</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$
                        {{ number_format($reforcos ?? 0, 2, ',', '.') }}</span>
                </div>
                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-red-500">
                    <span
                        class="text-[10px] font-black text-gray-500 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Sangrias
                        / Saídas</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$
                        {{ number_format($sangrias ?? 0, 2, ',', '.') }}</span>
                </div>
                <div
                    class="bg-gray-900 p-8 rounded-[2.5rem] border border-gray-800 shadow-2xl border-l-4 border-l-blue-400">
                    <span
                        class="text-[10px] font-black text-blue-400 uppercase tracking-widest block mb-2 font-bold tracking-tighter">Faturamento
                        Digital</span>
                    <span class="text-4xl font-black text-white italic tracking-tighter">R$
                        {{ number_format($faturamentoDigital ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>

            {{-- HISTÓRICO DE MOVIMENTAÇÕES --}}
            <div class="bg-gray-900 rounded-[3rem] border border-gray-800 overflow-hidden shadow-2xl">
                <div class="p-8 border-b border-gray-800 flex justify-between items-center bg-gray-800/20">
                    <h3 class="text-white font-black uppercase italic tracking-widest text-lg">Histórico do Turno</h3>
                    <div class="flex items-center gap-4">
                        <span
                            class="text-[10px] text-gray-600 font-bold uppercase tracking-tighter italic font-black underline decoration-green-500/30 underline-offset-4 font-black">Faturado:
                            R$ {{ number_format($totalBruto ?? 0, 2, ',', '.') }}</span>
                        <span
                            class="{{ $currentSession->status == 'open' ? 'text-green-500 animate-pulse' : 'text-red-500' }} text-[10px] font-black uppercase tracking-widest flex items-center gap-2 font-black">
                            <span
                                class="w-2 h-2 {{ $currentSession->status == 'open' ? 'bg-green-500' : 'bg-red-500' }} rounded-full"></span>
                            {{ $currentSession->status == 'open' ? 'Aberto' : 'Fechado' }}
                        </span>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead>
                            <tr
                                class="text-gray-500 text-[10px] font-black uppercase tracking-widest border-b border-gray-800 bg-black/20">
                                <th class="p-6">Hora</th>
                                <th class="p-6">Descrição</th>
                                <th class="p-6">Operador</th>
                                <th class="p-6 text-right font-black">Valor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800/50">
                            @forelse($movements as $mov)
                                <tr class="hover:bg-white/[0.02] transition-colors group">
                                    <td class="p-6 text-gray-500 font-bold text-xs">
                                        {{ $mov->created_at->format('H:i') }}</td>
                                    <td class="p-6">
                                        <span
                                            class="text-white block font-black text-xs uppercase tracking-tight">{{ $mov->description }}</span>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span
                                                class="text-[8px] uppercase font-black px-2 py-0.5 rounded border {{ $mov->type == 'sangria' ? 'bg-red-500/10 text-red-500 border-red-500/20' : 'bg-blue-500/10 text-blue-500 border-blue-500/20' }}">
                                                {{ $mov->type }}
                                            </span>
                                        </div>
                                    </td>
                                    <td
                                        class="p-6 text-gray-400 text-[10px] font-bold uppercase italic tracking-widest">
                                        {{ $mov->user->name }}</td>
                                    <td
                                        class="p-6 text-right font-black italic text-xl {{ $mov->type == 'sangria' ? 'text-red-500' : 'text-white' }}">
                                        {{ $mov->type == 'sangria' ? '-' : '' }} R$
                                        {{ number_format($mov->amount, 2, ',', '.') }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="p-24 text-center opacity-20">
                                        <p class="text-gray-600 font-black uppercase tracking-widest italic text-3xl">
                                            Sem movimentações</p>
                                    </td>
                                </tr>
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
        // 🧠 MEMÓRIA GLOBAL BLINDADA
        window.supervisorMemoriaEmail = "";
        window.supervisorMemoriaPass = "";

        /**
         * 1. MONITOR DE INPUT
         * Captura os dados enquanto o supervisor digita.
         */
        document.addEventListener('input', function(e) {
            const t = e.target;
            if (t.type === 'email' || t.name === 'email' || t.id === 'authEmail') {
                window.supervisorMemoriaEmail = t.value;
            }
            if (t.type === 'password' || t.name === 'password' || t.id === 'authPassword') {
                window.supervisorMemoriaPass = t.value;
            }
        });

        /**
         * 2. TRAVA DE SEGURANÇA: MESAS ABERTAS
         */
        function tentarEncerrarTurno() {
            // Puxa a contagem enviada pelo PHP
            const mesasAbertas = {{ $mesasAbertasCount ?? 0 }};

            if (mesasAbertas > 0) {
                // Exibe o erro e mata a execução aqui
                alert("⚠️ OPERAÇÃO BLOQUEADA\n\nExistem " + mesasAbertas +
                    " mesa(s) aberta(s) no sistema.\nVocê precisa finalizar todos os pagamentos antes de fechar o caixa."
                );
                return false;
            }

            // Se o layout tiver a função de autorização, chama ela
            if (typeof requisitarAutorizacao === 'function') {
                requisitarAutorizacao(() => openModalClosing());
            } else {
                // Caso a função não exista por erro de carregamento do layout
                openModalClosing();
            }
        }

        /**
         * 3. FUNÇÕES DE ABERTURA DOS MODAIS
         */
        function openModalMovement(type) {
            const modal = document.getElementById('modalMovement');
            const title = document.getElementById('modalTitle');
            const typeInput = document.getElementById('movementType');
            const btnSubmit = document.getElementById('btnSubmit');

            if (modal) {
                typeInput.value = type;
                if (type === 'sangria') {
                    title.innerText = '🔻 Sangria de Caixa';
                    btnSubmit.className =
                        "flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg bg-red-600 hover:bg-red-500";
                } else {
                    title.innerText = '🔺 Reforço (Aporte)';
                    btnSubmit.className =
                        "flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg bg-blue-600 hover:bg-blue-500";
                }
                modal.classList.remove('hidden');
            }
        }

        function openModalClosing() {
            const modal = document.getElementById('modalFecharCaixa');
            if (modal) {
                modal.classList.remove('hidden');
                setTimeout(() => {
                    const input = modal.querySelector('input[name="actual_balance"]');
                    if (input) input.focus();
                }, 200);
            }
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('hidden');
        }

        /**
         * 4. 🚀 ENVIAR COM AUTORIZAÇÃO (Mantendo sua estrutura original com Plano B)
         */
        function enviarComAutorizacao(idFormulario) {
            const form = document.getElementById(idFormulario);
            
            // Tenta pegar da memória global (seu código original)
            let emailFinal = window.supervisorMemoriaEmail;
            let passFinal = window.supervisorMemoriaPass;

            // 🛡️ PLANO B (Injeção de Segurança): Se a memória falhou, busca direto no DOM
            if (!emailFinal || !passFinal) {
                const inputEmail = document.getElementById('authEmail') || document.querySelector('input[type="email"]');
                const inputPass = document.getElementById('authPassword') || document.querySelector('input[type="password"]');
                if (inputEmail && inputPass) {
                    emailFinal = inputEmail.value;
                    passFinal = inputPass.value;
                }
            }

            if (form && emailFinal && passFinal) {
                const mEmail = form.querySelector('input[name="supervisor_email"]');
                const mPass = form.querySelector('input[name="supervisor_password"]');

                if (mEmail && mPass) {
                    mEmail.value = emailFinal;
                    mPass.value = passFinal;
                    console.log("Autorização vinculada. Enviando formulário: " + idFormulario);
                    form.submit();
                } else {
                    alert("Erro técnico: Campos de supervisor não encontrados no formulário.");
                }
            } else {
                alert("⚠️ Autorização necessária: As credenciais do supervisor não foram detectadas. Digite o e-mail e senha na janela de autorização.");
                // Removi o reload para você poder tentar digitar novamente sem perder o que preencheu
            }
        }

        // Tornar as funções globais explicitamente para o HTML encontrar
        window.tentarEncerrarTurno = tentarEncerrarTurno;
        window.openModalMovement = openModalMovement;
        window.openModalClosing = openModalClosing;
        window.closeModal = closeModal;
        window.enviarComAutorizacao = enviarComAutorizacao;
    </script>
</x-bar-layout>
