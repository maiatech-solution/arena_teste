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

            {{-- 📊 RESUMO DE AUDITORIA UNIFICADO --}}
            <div class="grid grid-cols-1 gap-4 mt-6">
                {{-- CARD DE DESTAQUE: VALOR TOTAL DO BAR --}}
                <div class="bg-orange-600/5 p-6 rounded-[2rem] border border-orange-600/20 text-center">
                    <span class="text-[10px] font-black text-gray-500 uppercase block mb-1 leading-tight tracking-[0.2em]">
                        Total Geral Esperado na Gaveta
                    </span>
                    <span class="text-white font-black text-4xl italic tracking-tighter">
                        R$ {{ number_format($openSession->expected_balance ?? 0, 2, ',', '.') }}
                    </span>
                    <p class="text-[8px] text-orange-500 font-bold uppercase mt-2 italic tracking-widest">
                        (Soma de todos os operadores + Fundo de troco)
                    </p>
                </div>

                {{-- INFORMAÇÃO COMPLEMENTAR: INDIVIDUAL --}}
                <div class="bg-black/40 p-3 rounded-2xl border border-gray-800 text-center">
                    <span class="text-[9px] font-black text-gray-600 uppercase block mb-1">
                        Seu faturamento individual:
                        <span class="text-gray-400">R$ {{ number_format($dinheiroGeral, 2, ',', '.') }}</span>
                    </span>
                </div>
            </div>
        </div>

        <form action="{{ route('bar.cash.close') }}" method="POST" class="p-10" id="formCloseCash">
            @csrf

            {{-- 🔑 CAMPOS DE ESPELHO (Essenciais para o Controller) --}}
            <input type="hidden" name="supervisor_email" id="mirror_email_closing">
            <input type="hidden" name="supervisor_password" id="mirror_password_closing">

            <div class="space-y-8">
                {{-- VALOR CONTADO --}}
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">
                        Total Físico na Gaveta (Contagem Real)
                    </label>
                    <input type="number" name="actual_balance" step="0.01" min="0" required
                        placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-8 text-white text-5xl font-black text-center focus:border-orange-600 focus:outline-none transition-all shadow-inner font-mono">
                    <p class="text-[9px] text-gray-600 text-center mt-3 uppercase font-bold italic px-6">
                        Atenção: Informe o valor total em dinheiro que está fisicamente no caixa.
                    </p>
                </div>

                {{-- OBSERVAÇÕES --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Notas
                        / Observações</label>
                    <textarea name="notes" rows="2" placeholder="Descreva qualquer divergência ou observação importante aqui..."
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-600 outline-none text-sm text-center"></textarea>
                </div>
            </div>

            <div class="flex flex-col gap-4 mt-10">
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
    function openModalClosing() {
        const modal = document.getElementById('modalFecharCaixa');
        if (modal) {
            modal.classList.remove('hidden');
            setTimeout(() => {
                const input = modal.querySelector('input[name="actual_balance"]');
                if (input) input.focus();
            }, 100);
        }
    }

    function closeModalClosing() {
        const modal = document.getElementById('modalFecharCaixa');
        if (modal) {
            modal.classList.add('hidden');
        }
    }
</script>
