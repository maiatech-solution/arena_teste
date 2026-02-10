{{-- resources/views/bar/cash/modals/closing.blade.php --}}
<div id="modalFecharCaixa"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden shadow-orange-900/20">

        <div class="p-10 border-b border-gray-800 text-center">
            <div
                class="w-16 h-16 bg-orange-600/10 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-orange-600/20">
                <span class="text-3xl">🔒</span>
            </div>
            <h3 class="text-white text-2xl font-black uppercase italic">Encerrar Turno</h3>

            {{-- 📊 RESUMO DE AUDITORIA PERSONALIZADO --}}
            <div class="grid grid-cols-2 gap-4 mt-6">
                {{-- O que a Blenda vendeu --}}
                <div class="bg-black/40 p-3 rounded-2xl border border-gray-800 text-center">
                    <span class="text-[8px] font-black text-gray-500 uppercase block mb-1 leading-tight">Seu
                        Faturamento<br>({{ auth()->user()->name }})</span>
                    <span class="text-orange-500 font-black text-lg italic">R$
                        {{ number_format($dinheiroGeral, 2, ',', '.') }}</span>
                </div>
                {{-- O que tem no bar no total --}}
                <div class="bg-black/40 p-3 rounded-2xl border border-gray-800 text-center">
                    <span class="text-[8px] font-black text-gray-500 uppercase block mb-1 leading-tight">Total
                        Geral<br>do Bar</span>
                    <span class="text-white font-black text-lg italic">R$
                        {{ number_format($openSession->expected_balance ?? 0, 2, ',', '.') }}</span>
                </div>
            </div>
        </div>

        <form action="{{ route('bar.cash.close') }}" method="POST" class="p-10" id="formCloseCash">
            @csrf

            {{-- 🔑 CAMPOS DE ESPELHO (Essenciais para o Controller) --}}
            <input type="hidden" name="supervisor_email">
            <input type="hidden" name="supervisor_password">

            <div class="space-y-8">
                {{-- VALOR CONTADO --}}
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Total
                        Físico na Gaveta (Dinheiro Vivo)</label>
                    <input type="number" name="actual_balance" step="0.01" min="0" required
                        placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-8 text-white text-5xl font-black text-center focus:border-orange-600 focus:outline-none transition-all shadow-inner font-mono">
                    <p class="text-[9px] text-gray-600 text-center mt-3 uppercase font-bold italic">Some o seu valor com
                        o dos outros operadores que não fecharam.</p>
                </div>

                {{-- OBSERVAÇÕES --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Notas
                        / Observações</label>
                    <textarea name="notes" rows="2" placeholder="Ex: Valor acumulado com o turno anterior."
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-600 outline-none text-sm"></textarea>
                </div>
            </div>

            <div class="flex flex-col gap-4 mt-10">
                {{-- 🚀 BOTÃO CORRIGIDO: Agora usa a ponte que já tem os dados na memória --}}
                <button type="button" onclick="enviarComAutorizacao('formCloseCash')"
                    class="w-full py-6 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-3xl uppercase text-xs tracking-widest transition-all shadow-lg shadow-orange-900/40 active:scale-95">
                    Validar e Trancar Caixa
                </button>

                <button type="button" onclick="closeModalClosing()"
                    class="w-full py-4 text-gray-500 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:text-white transition-all">
                    Voltar ao Painel
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    /**
     * Função para abrir o modal de fechamento
     * Vinculada ao botão principal da index
     */
    function openModalClosing() {
        const modal = document.getElementById('modalFecharCaixa');
        if (modal) {
            modal.classList.remove('hidden');
            // Foca automaticamente no input de valor
            setTimeout(() => {
                modal.querySelector('input[name="actual_balance"]').focus();
            }, 100);
        }
    }

    /**
     * Função para fechar o modal
     */
    function closeModalClosing() {
        const modal = document.getElementById('modalFecharCaixa');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
