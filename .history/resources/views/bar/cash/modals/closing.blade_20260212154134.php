{{-- resources/views/bar/cash/modals/closing.blade.php --}}
<div id="modalFecharCaixa"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/95 backdrop-blur-md p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-2xl rounded-[2.5rem] shadow-2xl overflow-hidden shadow-orange-900/20">

        {{-- HEADER COMPACTO --}}
        <div class="p-6 border-b border-gray-800 flex items-center justify-between bg-black/20">
            <div class="flex items-center gap-4">
                <div
                    class="w-10 h-10 bg-orange-600/20 rounded-xl flex items-center justify-center border border-orange-600/30">
                    <span class="text-xl">🔒</span>
                </div>
                <h3 class="text-white text-xl font-black uppercase italic tracking-tighter">Encerrar Turno</h3>
            </div>
            <div class="text-right">
                <span class="text-[8px] font-black text-gray-500 uppercase block tracking-widest">Responsável</span>
                <span class="text-gray-300 text-[10px] font-bold uppercase italic">{{ auth()->user()->name }}</span>
            </div>
        </div>

        <div class="p-8">
            {{-- 📊 DASHBOARD DE FECHAMENTO (LAYOUT OTIMIZADO) --}}
            <div class="space-y-4 mb-8">
                <div class="grid grid-cols-2 gap-4">
                    {{-- GAVETA --}}
                    <div class="bg-black/40 p-5 rounded-3xl border border-gray-800">
                        <span
                            class="text-[9px] font-black text-gray-500 uppercase block mb-1 tracking-widest leading-tight">Esperado
                            em Dinheiro<br>(Gaveta)</span>
                        <span class="text-white font-black text-2xl italic">R$
                            {{ number_format($openSession->expected_balance ?? 0, 2, ',', '.') }}</span>
                    </div>
                    {{-- DIGITAL --}}
                    <div class="bg-black/40 p-5 rounded-3xl border border-gray-800">
                        <span
                            class="text-[9px] font-black text-gray-500 uppercase block mb-1 tracking-widest leading-tight">Faturamento
                            Digital<br>(PIX/Cartões)</span>
                        <span class="text-blue-400 font-black text-2xl italic">R$
                            {{ number_format($faturamentoDigital ?? 0, 2, ',', '.') }}</span>
                    </div>
                </div>

                {{-- BRUTO TOTAL (DESTAQUE CENTRAL) --}}
                <div
                    class="bg-orange-600/10 p-5 rounded-3xl border border-orange-600/20 flex justify-between items-center px-8">
                    <div>
                        <span
                            class="text-[10px] font-black text-orange-500 uppercase block tracking-[0.2em]">Faturamento
                            Bruto Total</span>
                        <p class="text-[8px] text-gray-500 font-bold uppercase italic mt-1">(Total vendido sem o fundo
                            de reserva)</p>
                    </div>
                    <span class="text-green-500 font-black text-4xl italic tracking-tighter">
                        R$
                        {{ number_format(($openSession->expected_balance ?? 0) + ($faturamentoDigital ?? 0) - ($openSession->opening_balance ?? 0), 2, ',', '.') }}
                    </span>
                </div>
            </div>

            <form action="{{ route('bar.cash.close') }}" method="POST" id="formCloseCash">
                @csrf

                {{-- 🔑 CAMPOS DE ESPELHO (Injetamos seu e-mail automaticamente se for Gestor) --}}
                <input type="hidden" name="supervisor_email"
                    value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
                <input type="hidden" name="supervisor_password" id="mirror_password_closing">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- VALOR CONTADO --}}
                    <div>
                        <label
                            class="text-gray-500 uppercase text-[10px] font-black ml-2 mb-2 block tracking-widest">Contagem
                            Real na Gaveta</label>
                        <input type="number" name="actual_balance" step="0.01" min="0" required
                            placeholder="0,00"
                            class="w-full bg-black border-2 border-gray-800 rounded-2xl p-6 text-white text-4xl font-black text-center focus:border-orange-600 outline-none transition-all shadow-inner font-mono">
                    </div>

                    {{-- OBSERVAÇÕES --}}
                    <div>
                        <label
                            class="text-gray-500 uppercase text-[10px] font-black ml-2 mb-2 block tracking-widest">Observações
                            do Turno</label>
                        <textarea name="notes" rows="3" placeholder="Ex: Diferença de troco..."
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white placeholder-gray-600 focus:ring-1 focus:ring-orange-600 outline-none text-xs h-[100px]"></textarea>
                    </div>
                </div>

                {{-- 🛡️ CAMPO DE SENHA DIRETO NO MODAL (Aparece para você que é Gestor) --}}
                @if (in_array(auth()->user()->role, ['admin', 'gestor']))
                    <div class="mt-6 p-5 bg-orange-600/5 border border-orange-600/20 rounded-[2rem] text-center">
                        <span
                            class="text-[9px] font-black text-orange-500 uppercase block mb-3 tracking-[0.2em]">Confirmação
                            de Segurança</span>
                        <input type="password" id="password_direta_gestor" placeholder="Sua senha de Gestor"
                            class="w-full max-w-xs bg-black border border-gray-800 rounded-xl p-3 text-white text-center text-sm outline-none focus:border-orange-600 transition-all font-mono">
                        <p class="text-[8px] text-gray-600 mt-2 uppercase font-bold italic">Você está logado como
                            {{ auth()->user()->name }}. Confirme sua senha para assinar o encerramento.</p>
                    </div>
                @endif

                <div class="grid grid-cols-2 gap-4 mt-8">
                    <button type="button" onclick="closeModalClosing()"
                        class="py-4 text-gray-500 font-black rounded-2xl uppercase text-[10px] tracking-widest hover:bg-gray-800 hover:text-white transition-all">
                        Cancelar
                    </button>
                    {{-- 🚀 CHAMADA PARA A NOVA FUNÇÃO DE ENVIO --}}
                    <button type="button" onclick="enviarFechamentoFinal()"
                        class="py-4 bg-orange-600 hover:bg-orange-500 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest transition-all shadow-lg shadow-orange-900/40 active:scale-95">
                        Encerrar Turno
                    </button>
                </div>
            </form>
        </div>
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
