{{-- MODAL GENÉRICO DE MOVIMENTAÇÃO --}}
<div id="modalMovement" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm p-4">
    <div class="bg-gray-900 border border-gray-800 w-full max-w-lg rounded-[3rem] shadow-2xl overflow-hidden">

        <div class="p-8 border-b border-gray-800 text-center">
            <h3 id="modalTitle" class="text-white text-2xl font-black uppercase italic">Movimentação</h3>
            <p class="text-gray-500 text-[10px] font-bold uppercase tracking-widest mt-1">Registro de movimentação manual do caixa</p>
        </div>

        <form action="{{ route('bar.cash.movement') }}" method="POST" class="p-8 space-y-6" id="formMovement">
            @csrf
            {{-- 🔑 Campos que o Controller exige --}}
            <input type="hidden" name="type" id="movementType">
            <input type="hidden" name="supervisor_email" value="{{ in_array(auth()->user()->role, ['admin', 'gestor']) ? auth()->user()->email : '' }}">
            <input type="hidden" name="supervisor_password" id="mirror_password_movement">

            <div class="space-y-4">
                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black block text-center mb-2">Valor (R$)</label>
                    <input type="number" name="amount" step="0.01" min="0.01" required placeholder="0,00"
                        class="w-full bg-black border-2 border-gray-800 rounded-3xl p-6 text-white text-4xl font-black text-center focus:border-orange-500 outline-none font-mono">
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-gray-500 uppercase text-[10px] font-black block text-center mb-2">Método</label>
                        <select name="payment_method" class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-black text-xs uppercase outline-none focus:ring-2 focus:ring-orange-500">
                            <option value="dinheiro">💵 Dinheiro</option>
                            <option value="pix">📱 PIX</option>
                            <option value="debito">💳 Débito</option>
                            <option value="credito">💳 Crédito</option>
                        </select>
                    </div>
                    <div>
                        <label class="text-gray-500 uppercase text-[10px] font-black block text-center mb-2">Autorização</label>
                        {{-- 🔒 Campo de senha VISÍVEL no modal --}}
                        <input type="password" id="password_direta_movimento" placeholder="Senha Gestor"
                            class="w-full bg-black border-2 border-gray-800 rounded-2xl p-4 text-white text-center text-sm outline-none focus:border-orange-500 font-mono">
                    </div>
                </div>

                <div>
                    <label class="text-gray-500 uppercase text-[10px] font-black block text-center mb-2">Descrição</label>
                    <input type="text" name="description" required placeholder="Ex: Pagamento Fornecedor"
                        class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white text-center outline-none">
                </div>
            </div>

            <div class="flex gap-4 mt-6">
                <button type="button" onclick="closeModal('modalMovement')" class="flex-1 py-4 bg-gray-800 text-gray-400 font-black rounded-2xl uppercase text-[10px]">Cancelar</button>
                <button type="button" onclick="enviarComAutorizacao('formMovement')" class="flex-1 py-4 bg-orange-600 text-white font-black rounded-2xl uppercase text-[10px] shadow-lg">Confirmar</button>
            </div>
        </form>
    </div>
</div>
