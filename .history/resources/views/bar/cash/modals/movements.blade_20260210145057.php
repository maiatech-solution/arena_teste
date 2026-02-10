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

            {{-- 🔑 CAMPOS DE ESPELHO --}}
            <input type="hidden" name="supervisor_email" id="mirror_email">
            <input type="hidden" name="supervisor_password" id="mirror_password">

            {{-- Campo Hidden para o Tipo --}}
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Valor
                        do Lançamento (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all shadow-inner">
                </div>

                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Forma
                        de Movimentação</label>
                    <div class="relative">
                        <select name="payment_method" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-black text-xs uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer appearance-none text-center">
                            <option value="dinheiro" selected>💵 Dinheiro (Gaveta)</option>
                            <option value="pix">📱 PIX / Transferência Digital</option>
                            <option value="debito">💳 Cartão de Débito (Conta)</option>
                            <option value="credito">💳 Cartão de Crédito</option>
                        </select>
                        <div class="absolute right-6 top-1/2 -translate-y-1/2 pointer-events-none text-gray-500">▼</div>
                    </div>
                </div>

                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Descrição
                        / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor Gelo"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-2 focus:ring-orange-500 outline-none font-medium text-center">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')"
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-gray-700 transition-all">
                    Cancelar
                </button>

                <button type="button" onclick="enviarComAutorizacao('formMovement')" id="btnSubmit"
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

        if (!modal || !typeInput) return;

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
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('hidden');
    }

    function enviarComAutorizacao(idFormulario) {
        // 1. Tenta buscar pelos IDs padrão
        let emailInput = document.getElementById('authEmail');
        let passInput = document.getElementById('authPassword');

        // 2. Se não achar por ID, tenta buscar pelo nome (alguns layouts usam 'email' e 'password')
        if (!emailInput) emailInput = document.querySelector('input[name="email"]');
        if (!passInput) passInput = document.querySelector('input[name="password"]');

        const form = document.getElementById(idFormulario);

        if (form && emailInput && passInput) {
            const mirrorEmail = form.querySelector('#mirror_email');
            const mirrorPassword = form.querySelector('#mirror_password');

            if (mirrorEmail && mirrorPassword) {
                // Copia os valores digitados
                mirrorEmail.value = emailInput.value;
                mirrorPassword.value = passInput.value;

                // Log para conferência no seu console (F12)
                console.log("✅ Credenciais capturadas: " + emailInput.value);

                form.submit();
            } else {
                console.error("Erro: Campos mirror_email ou mirror_password não existem no formulário.");
            }
        } else {
            // Se cair aqui, é porque o e-mail ou a senha não foram encontrados na tela
            alert(
                "Erro: Não conseguimos capturar o login do supervisor. Certifique-se de que o modal de senha está aberto.");
        }
    }

    window.onclick = function(event) {
        const modalMovement = document.getElementById('modalMovement');
        const modalFecharCaixa = document.getElementById('modalFecharCaixa');

        if (event.target == modalMovement) closeModal('modalMovement');
        if (event.target == modalFecharCaixa) closeModal('modalFecharCaixa');
    }
</script>
