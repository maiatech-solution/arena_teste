{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="p-8 border-b border-gray-800 text-center">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic italic">Título do Modal</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registo de movimentação manual
                do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8" id="formMovement">
            @csrf
            {{-- CAMPOS DE ESPELHO --}}
            <input type="hidden" name="supervisor_email" id="mirror_email">
            <input type="hidden" name="supervisor_password" id="mirror_password">
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Valor
                        (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all shadow-inner font-mono">
                </div>

                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Forma
                        de Movimentação</label>
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
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Descrição
                        / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 outline-none text-center font-medium">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')"
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest">Cancelar</button>
                <button type="button" onclick="enviarComAutorizacao('formMovement')" id="btnSubmit"
                    class="flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg active:scale-95">Confirmar
                    Lançamento</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 🧠 Memória global para os dados do gestor
    window.gestorEmail = "";
    window.gestorSenha = "";

    function openModalMovement(type) {
        // 🔍 ESTRATÉGIA DE BUSCA: Tenta ID, depois Name, depois Type
        const emailInput = document.getElementById('authEmail') ||
            document.querySelector('input[name="email"]') ||
            document.querySelector('input[type="email"]');

        const passInput = document.getElementById('authPassword') ||
            document.querySelector('input[name="password"]') ||
            document.querySelector('input[type="password"]');

        if (emailInput && passInput) {
            window.gestorEmail = emailInput.value;
            window.gestorSenha = passInput.value;
            console.log("✅ Debug: Credenciais capturadas com sucesso:", window.gestorEmail);
        } else {
            console.warn(
                "⚠️ Debug: Não foi possível localizar os campos de login. Verifique se o modal de supervisor está aberto."
                );
            // Lista todos os inputs da página no console para sabermos os nomes reais
            console.log("Lista de inputs na página:");
            document.querySelectorAll('input').forEach(i => console.log(
                `Name: ${i.name} | ID: ${i.id} | Type: ${i.type}`));
        }

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

    function closeModal(id) {
        const modal = document.getElementById(id);
        if (modal) modal.classList.add('hidden');
    }

    function enviarComAutorizacao(idFormulario) {
        const form = document.getElementById(idFormulario);

        // 🛡️ Prioridade: 1. Valor atual do input | 2. Valor salvo na memória global
        const emailInput = document.getElementById('authEmail') || document.querySelector('input[name="email"]');
        const passInput = document.getElementById('authPassword') || document.querySelector('input[name="password"]');

        const emailFinal = emailInput?.value || window.gestorEmail;
        const passFinal = passInput?.value || window.gestorSenha;

        console.log("🚀 Tentando enviar form com e-mail:", emailFinal);

        if (form && emailFinal && passFinal && emailFinal !== "" && passFinal !== "") {
            // Preenche os campos de espelho que estão no seu form
            const mirrorEmail = form.querySelector('#mirror_email');
            const mirrorPass = form.querySelector('#mirror_password');

            if (mirrorEmail && mirrorPass) {
                mirrorEmail.value = emailFinal;
                mirrorPass.value = passFinal;
                console.log("✅ Dados anexados. Enviando formulário...");
                form.submit();
            } else {
                console.error("❌ Erro: Campos mirror_email ou mirror_password não encontrados no formulário.");
            }
        } else {
            alert("Erro: Credenciais de autorização não capturadas. Por favor, tente novamente.");
            console.error("❌ Falha no envio. Email:", emailFinal, "Senha:", passFinal ? "OK" : "Vazia");
        }
    }

    // Fecha o modal ao clicar fora dele
    window.onclick = function(event) {
        if (event.target.id === 'modalMovement' || event.target.id === 'modalFecharCaixa') {
            closeModal(event.target.id);
        }
    }
</script>
