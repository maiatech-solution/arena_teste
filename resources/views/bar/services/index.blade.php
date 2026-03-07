<x-bar-layout>
    <div class="p-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-black text-white italic uppercase tracking-tighter">
                    Serviços & Taxas
                </h1>
                <p class="text-gray-500 text-sm font-medium">Gerencie aluguéis de espaços e taxas administrativas da Arena.</p>
            </div>

            <button onclick="document.getElementById('modalService').classList.remove('hidden')" 
                class="bg-emerald-600 hover:bg-emerald-500 text-white px-6 py-3 rounded-2xl font-bold flex items-center gap-2 transition-all transform hover:scale-105 shadow-lg shadow-emerald-900/20">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                </svg>
                NOVO SERVIÇO
            </button>
        </div>

        {{-- TABELA DE SERVIÇOS --}}
        <div class="bg-gray-900 border border-gray-800 rounded-[2.5rem] overflow-hidden shadow-2xl">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-gray-800/50 text-gray-400 uppercase text-[10px] font-black tracking-widest">
                            <th class="px-8 py-5">Serviço / Taxa</th>
                            <th class="px-8 py-5">Preço Base</th>
                            <th class="px-8 py-5">Status</th>
                            <th class="px-8 py-5 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-800">
                        @forelse($services as $service)
                        <tr class="hover:bg-gray-800/30 transition-colors group">
                            <td class="px-8 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="h-10 w-10 bg-gray-800 rounded-xl flex items-center justify-center text-gray-500 group-hover:text-emerald-400 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                                        </svg>
                                    </div>
                                    <div>
                                        <span class="block text-white font-bold italic">{{ $service->name }}</span>
                                        <span class="text-[10px] text-gray-500 uppercase font-bold">{{ $service->description ?? 'Sem descrição' }}</span>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <span class="text-white font-mono font-bold text-lg">
                                    R$ {{ number_format($service->price, 2, ',', '.') }}
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $service->status ? 'bg-emerald-500/10 text-emerald-500' : 'bg-red-500/10 text-red-500' }}">
                                    {{ $service->status ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <div class="flex justify-end gap-2">
                                    <button onclick='editService(@json($service))' class="p-2 hover:bg-gray-700 rounded-lg text-gray-400 hover:text-white transition-all">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                        </svg>
                                    </button>
                                    
                                    <form action="{{ route('bar.services.destroy', $service) }}" method="POST" onsubmit="return confirm('Excluir este serviço permanentemente?')">
                                        @csrf @method('DELETE')
                                        <button class="p-2 hover:bg-red-500/10 rounded-lg text-gray-500 hover:text-red-500 transition-all">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-8 py-20 text-center text-gray-500 italic font-medium">
                                Nenhum serviço cadastrado. Comece adicionando o Aluguel da Churrasqueira!
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- MODAL ADICIONAR / EDITAR --}}
    <div id="modalService" class="fixed inset-0 bg-black/80 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
        <div class="bg-gray-900 border border-gray-800 w-full max-w-md rounded-[2.5rem] p-8 shadow-2xl relative">
            <button onclick="closeModal()" class="absolute top-6 right-6 text-gray-500 hover:text-white transition-colors">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>

            <h2 id="modalTitle" class="text-2xl font-black text-white italic uppercase mb-6 tracking-tighter">Novo Serviço</h2>
            
            <form id="serviceForm" action="{{ route('bar.services.store') }}" method="POST">
                @csrf
                <div id="methodField"></div> {{-- Para o @method('PUT') dinâmico --}}

                <div class="space-y-6">
                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1 tracking-widest">Nome do Serviço</label>
                        <input type="text" name="name" id="service_name" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-emerald-500 transition-all font-bold italic placeholder-gray-600"
                            placeholder="Ex: Aluguel Churrasqueira">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1 tracking-widest">Preço (R$)</label>
                        <input type="number" step="0.01" name="price" id="service_price" required
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-emerald-500 transition-all font-mono font-bold text-lg placeholder-gray-600"
                            placeholder="0.00">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-gray-400 uppercase mb-2 ml-1 tracking-widest">Descrição (Opcional)</label>
                        <textarea name="description" id="service_desc" rows="2"
                            class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white focus:ring-2 focus:ring-emerald-500 transition-all text-sm placeholder-gray-600"
                            placeholder="Detalhes sobre o serviço..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-emerald-600 hover:bg-emerald-500 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-emerald-900/40 uppercase italic tracking-widest">
                        Salvar Serviço
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editService(service) {
            const modal = document.getElementById('modalService');
            const form = document.getElementById('serviceForm');
            const title = document.getElementById('modalTitle');
            const methodField = document.getElementById('methodField');

            title.innerText = "Editar Serviço";
            form.action = `/bar/servicos/${service.id}`;
            methodField.innerHTML = '<input type="hidden" name="_method" value="PUT">';

            document.getElementById('service_name').value = service.name;
            document.getElementById('service_price').value = service.price;
            document.getElementById('service_desc').value = service.description;

            modal.classList.remove('hidden');
        }

        function closeModal() {
            const modal = document.getElementById('modalService');
            modal.classList.add('hidden');
            // Resetar formulário para o estado de "Novo"
            document.getElementById('serviceForm').action = "{{ route('bar.services.store') }}";
            document.getElementById('methodField').innerHTML = '';
            document.getElementById('modalTitle').innerText = "Novo Serviço";
            document.getElementById('serviceForm').reset();
        }

        // Fechar ao clicar fora do modal
        document.getElementById('modalService').onclick = function(e) {
            if (e.target === this) closeModal();
        };
    </script>
</x-bar-layout>