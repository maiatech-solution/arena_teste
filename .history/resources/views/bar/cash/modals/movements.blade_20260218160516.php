{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement"
    class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div
        class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden animate-in fade-in zoom-in duration-200">

        <div class="p-8 border-b border-gray-800 text-center">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic">Movimentação</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registro de movimentação manual
                do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8" id="formMovement">
            @csrf
            {{-- 🔑 CAMPOS EXIGIDOS PELA CONTROLLER --}}
            <input type="hidden" name="supervisor_email"
                value="{{ auth()->user()->role === 'admin' || auth()->user()->role === 'gestor' ? auth()->user()->email : '' }}">
            <input type="hidden" name="supervisor_password" id="mirror_password_mov">
            <input type="hidden" name="type" id="movementType">

            <div class="space-y-6">
                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Valor
                        (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 focus:outline-none transition-all shadow-inner font-mono">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label
                            class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Método</label>
                        <select name="payment_method" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-black text-xs uppercase outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="dinheiro" selected>💵 Dinheiro</option>
                            <option value="pix">📱 PIX</option>
                        </select>
                    </div>
                    <div>
                        <label
                            class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center italic text-orange-500">Senha
                            Gestor</label>
                        <input type="password" id="pass_auth_mov" placeholder="******"
                            class="w-full bg-black border-2 border-gray-800 rounded-2xl p-4 text-white text-center text-sm outline-none focus:border-orange-500 font-mono">
                    </div>
                </div>

                <div>
                    <label
                        class="text-gray-500 uppercase text-[10px] font-black ml-4 mb-2 block tracking-widest text-center">Descrição
                        / Motivo</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-orange-500 outline-none font-medium text-center text-sm">
                </div>
            </div>

            <div class="flex gap-4 mt-10">
                <button type="button" onclick="closeModal('modalMovement')"
                    class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px] tracking-widest">Cancelar</button>
                <button type="button" onclick="dispararMovimento()"
                    class="flex-1 py-4 bg-orange-600 text-white font-black rounded-2xl uppercase text-[10px] tracking-widest shadow-lg active:scale-95">Confirmar</button>
            </div>
        </form>
    </div>
</div>
