{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

        <div id="modalHeader" class="p-8 border-b border-gray-800">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic">Título do Modal</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registo de movimentação manual
                do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8" id="formMovement">
            @csrf

            {{-- 🔑 CAMPO DE AUTORIZAÇÃO: Este campo receberá o seu e-mail de gestor via JS --}}
            <input type="hidden" name="authorized_by_user" id="authorized_by_user">

            {{-- Campo Hidden para o Tipo (sangria ou reforco) --}}
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                {{-- VALOR --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Valor
                        do Lançamento (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all shadow-inner">
                </div>

                {{-- FORMA DE MOVIMENTAÇÃO --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Forma
                        de Movimentação</label>
                    <div class="relative">
                        <select name="payment_method" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-black text-xs uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer appearance-none">
                            <option value="dinheiro" selected>💵 Dinheiro (Gaveta)</option>
                            <option value="pix">📱 PIX / Transferência Digital</option>
                            <option value="debito">💳 Cartão de Débito (Conta)</option>
                            <option value="credito">💳 Cartão de Crédito</option>
                        </select>
                        <div class="absolute right-4 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">▼</div>
                    </div>
                </div>

                {{-- DESCRIÇÃO / MOTIVO --}}
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest">Descrição
                        / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor Gelo"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 outline-none font-medium">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')"
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-gray-700 transition-all">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmit"
                    class="flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg active:scale-95">
                    Confirmar Lançamento
                </button>
            </div>
        </form>
    </div>
</div>

{{-- SCRIPT PARA CONTROLE DOS MODAIS --}}
<script>
    function openModalMovement(type) {
        const modal = document.getElementById('modalMovement');
        const title = document.getElementById('modalTitle');
        const typeInput = document.getElementById('movementType');
        const btnSubmit = document.getElementById('btnSubmit');

        typeInput.value = type;

        if (type === 'sangria') {
            title.innerText = '🔻 Sangria de Caixa';
            btnSubmit.classList.remove('bg-blue-600', 'hover:bg-blue-500');
            btnSubmit.classList.add('bg-red-600', 'hover:bg-red-500');
            btnSubmit.innerText = 'Confirmar Retirada';
        } else {
            title.innerText = '🔺 Reforço (Aporte)';
            btnSubmit.classList.remove('bg-red-600', 'hover:bg-red-500');
            btnSubmit.classList.add('bg-blue-600', 'hover:bg-blue-500');
            btnSubmit.innerText = 'Confirmar Entrada';
        }

        modal.classList.remove('hidden');
    }

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    window.onclick = function(event) {
        const modal = document.getElementById('modalMovement');
        if (event.target == modal) {
            closeModal('modalMovement');
        }
    }
</script>
