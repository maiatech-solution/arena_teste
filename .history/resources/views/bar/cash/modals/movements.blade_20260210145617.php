{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="p-8 border-b border-gray-800 text-center">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic italic">Título do Modal</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registo de movimentação manual do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8" id="formMovement">
            @csrf
            {{-- CAMPOS DE ESPELHO --}}
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
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 outline-none text-center font-medium">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')" class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest">Cancelar</button>
                <button type="button" onclick="enviarComAutorizacao('formMovement')" id="btnSubmit" class="flex-1 py-4 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg active:scale-95">Confirmar Lançamento</button>
            </div>
        </form>
    </div>
</div>

<script>
    // 🔍 VARIÁVEIS GLOBAIS DE DEBUG
    window.gestorEmail = "";
    window.gestorSenha = "";

    function openModalMovement(type) {
        // Debug: Tenta capturar do modal de supervisor assim que o de sangria abre
        const inputE = document.getElementById('authEmail');
        const inputP = document.getElementById('authPassword');

        if (inputE && inputP) {
            window.gestorEmail = inputE.value;
            window.gestorSenha = inputP.value;
            console.log("🛠️ Debug: Credenciais capturadas na abertura:", window.gestorEmail ? "E-mail OK" : "E-mail Vazio");
        } else {
            console.warn("⚠️ Debug: Inputs 'authEmail' ou 'authPassword' não encontrados no DOM.");
        }

        const modal = document.getElementById('modalMovement');
        const title = document.getElementById('modalTitle');
        const typeInput = document.getElementById('movementType');
        const btnSubmit = document.getElementById('btnSubmit');

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

    function closeModal(id) {
        document.getElementById(id).classList.add('hidden');
    }

    function enviarComAutorizacao(idFormulario) {
        const form = document.getElementById(idFormulario);

        // 1. Tenta pegar o valor em tempo real dos inputs
        let email = document.getElementById('authEmail')?.value;
        let pass = document.getElementById('authPassword')?.value;

        // 2. Se estiverem vazios (modal fechou e limpou), usa a variável global
        if (!email || email === "") email = window.gestorEmail;
        if (!pass || pass === "") pass = window.gestorSenha;

        console.log("🚀 Debug: Tentando enviar form com e-mail:", email);

        if (form && email && pass && email !== "" && pass !== "") {
            document.getElementById('mirror_email').value = email;
            document.getElementById('mirror_password').value = pass;
            console.log("✅ Debug: Form preenchido, enviando...");
            form.submit();
        } else {
            alert("Erro: Credenciais de autorização não encontradas. Por favor, tente reabrir a opção.");
            console.error("❌ Debug: Falha no envio. Email:", email, "Pass:", pass ? "******" : "Vazio");
        }
    }
</script>
