{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="p-8 border-b border-gray-800 text-center">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic">Título do Modal</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registo de movimentação manual do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8" id="formMovement">
            @csrf
            {{-- 🔑 CAMPOS DE ESPELHO --}}
            <input type="hidden" name="supervisor_email" id="mirror_email">
            <input type="hidden" name="supervisor_password" id="mirror_password">
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Valor (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all shadow-inner font-mono">
                </div>

                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Forma de Movimentação</label>
                    <div class="relative">
                        <select name="payment_method" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-black text-xs uppercase tracking-widest outline-none focus:ring-2 focus:ring-orange-500 cursor-pointer appearance-none text-center">
                            <option value="dinheiro" selected>💵 Dinheiro (Gaveta)</option>
                            <option value="pix">📱 PIX</option>
                            <option value="debito">💳 Débito</option>
                            <option value="credito">💳 Crédito</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Descrição / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 outline-none font-medium text-center">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')"
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest">Cancelar</button>
                <button type="button" onclick="finalizarMovimentacao()" id="btnSubmit"
                    class="flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg active:scale-95">Confirmar Lançamento</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 🧠 MEMÓRIA GLOBAL: Guardamos aqui o que foi digitado no modal de supervisor
    let credenciaisSupervisor = {
        email: '',
        password: ''
    };

    function openModalMovement(type) {
        // 💾 No momento que esse modal abre, a gente "pesca" o que está no modal de supervisor que ACABOU de ser preenchido
        const emailInput = document.getElementById('authEmail') || document.querySelector('input[name="email"]');
        const passInput = document.getElementById('authPassword') || document.querySelector('input[name="password"]');

        if (emailInput && passInput && emailInput.value !== "") {
            credenciaisSupervisor.email = emailInput.value;
            credenciaisSupervisor.password = passInput.value;
            console.log("✅ Credenciais salvas na memória:", credenciaisSupervisor.email);
        }

        const modal = document.getElementById('modalMovement');
        const title = document.getElementById('modalTitle');
        const typeInput = document.getElementById('movementType');
        const btnSubmit = document.getElementById('btnSubmit');

        if (modal && typeInput) {
            typeInput.value = type;
            if (type === 'sangria') {
                title.innerText = '🔻 Sangria de Caixa';
                btnSubmit.className = "flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg bg-red-600 hover:bg-red-500";
            } else {
                title.innerText = '🔺 Reforço (Aporte)';
                btnSubmit.className = "flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg bg-blue-600 hover:bg-blue-500";
            }
            modal.classList.remove('hidden');
        }
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('hidden');
    }

    function finalizarMovimentacao() {
        const form = document.getElementById('formMovement');

        // Tentativa 1: Pegar do input (se ainda estiver lá)
        // Tentativa 2: Pegar da nossa memória global (se o layout já limpou o input)
        const emailFinal = (document.getElementById('authEmail') || document.querySelector('input[name="email"]'))?.value || credenciaisSupervisor.email;
        const passFinal = (document.getElementById('authPassword') || document.querySelector('input[name="password"]'))?.value || credenciaisSupervisor.password;

        console.log("🛠️ Enviando formulário. E-mail final:", emailFinal);

        if (form && emailFinal !== "" && passFinal !== "") {
            document.getElementById('mirror_email').value = emailFinal;
            document.getElementById('mirror_password').value = passFinal;
            form.submit();
        } else {
            alert("⚠️ Erro: Não conseguimos capturar o login do supervisor. Por favor, reinicie a operação.");
            console.error("Dados faltantes no envio:", { emailFinal, passFinal });
        }
    }

    window.onclick = function(event) {
        if (event.target.id === 'modalMovement' || event.target.id === 'modalFecharCaixa') {
            closeModal(event.target.id);
        }
    }
</script>
