<div id="modalFecharCaixa" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden shadow-orange-900/20">
        
        <div class="p-10 border-b border-gray-800 text-center">
            <div class="w-16 h-16 bg-orange-600/10 rounded-2xl flex items-center justify-center mx-auto mb-4 border border-orange-600/20">
                <span class="text-3xl">🔒</span>
            </div>
            <h3 class="text-white text-2xl font-black uppercase italic">Encerrar Turno</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1 text-balance">Confirme o valor físico presente na gaveta para encerrar</p>
        </div>

        <form action="{{ route('bar.cash.close') }}" method="POST" class="p-10">
            @csrf
            <div class="space-y-8">
                {{-- VALOR CONTADO --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Total em Espécie (Dinheiro Vivo)</label>
                    <input type="number" name="actual_balance" step="0.01" min="0" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-8 text-white text-5xl font-black text-center focus:border-orange-600 focus:outline-none transition-all shadow-inner">
                </div>

                {{-- OBSERVAÇÕES --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Notas / Observações (Opcional)</label>
                    <textarea name="notes" rows="2" placeholder="Ex: Faltou troco para cliente X, diferença de R$ 0,50"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-600 outline-none text-sm"></textarea>
                </div>
            </div>

            <div class="flex flex-col gap-4 mt-10">
                <button type="submit" class="w-full py-6 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-3xl uppercase text-xs tracking-widest transition-all shadow-lg shadow-orange-900/40">
                    Finalizar e Trancar Caixa
                </button>
                <button type="button" onclick="closeModal('modalFecharCaixa')" 
                    class="w-full py-4 text-gray-500 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:text-white transition-all">
                    Voltar ao Painel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openModalClosing() {
        document.getElementById('modalFecharCaixa').classList.remove('hidden');
    }
</script>