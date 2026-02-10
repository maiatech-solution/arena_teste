{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <div id="modalHeader" class="p-8 border-b border-gray-800">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic italic">Título do Modal</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registo de movimentação manual de espécie</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8">
            @csrf
            {{-- Campo Hidden para o Tipo (sangria ou reforco) --}}
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                {{-- VALOR --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block">Valor do Lançamento (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all">
                </div>

                {{-- DESCRIÇÃO / MOTIVO --}}
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block">Descrição / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor Gelo"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 outline-none">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')" 
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-gray-700 transition-all">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmit" class="flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg">
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
        const header = document.getElementById('modalHeader');

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

    // Fecha ao clicar fora do modal
    window.onclick = function(event) {
        const modal = document.getElementById('modalMovement');
        if (event.target == modal) {
            closeModal('modalMovement');
        }
    }
</script>