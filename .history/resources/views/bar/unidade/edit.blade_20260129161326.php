<x-bar-layout>
    <div class="py-12 bg-black min-h-screen">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">

            <div class="flex items-center justify-between mb-8 px-4 sm:px-0">
                <div class="flex items-center gap-4">
                    <a href="{{ route('bar.dashboard') }}"
                       class="bg-gray-800 hover:bg-gray-700 text-orange-500 p-3 rounded-2xl transition-all border border-gray-700 shadow-lg group"
                       title="Voltar ao Painel">
                        <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
                    </a>
                    <div>
                        <h1 class="text-3xl font-black text-white uppercase tracking-tighter">
                            Dados da <span class="text-orange-600">Empresa</span>
                        </h1>
                        <p class="text-gray-500 font-bold uppercase text-[10px] tracking-widest mt-1 italic">Configurações gerais do estabelecimento</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-900 overflow-hidden shadow-[0_20px_50px_rgba(0,0,0,0.5)] rounded-[2.5rem] border border-gray-800 p-8">

                @if (session('success'))
                    <div class="mb-6 p-4 bg-green-900/50 border border-green-500 text-green-200 rounded-xl font-bold flex items-center gap-3">
                        <span>✅</span> {{ session('success') }}
                    </div>
                @endif

                <form method="POST" action="{{ route('bar.company.update') }}" enctype="multipart/form-data" class="space-y-8">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2 group">
                            <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Nome da Unidade / Bar</label>
                            <input type="text" name="name" value="{{ old('name', $company->name) }}" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner transition-all">
                        </div>

                        <div class="space-y-2 group">
                            <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">CNPJ (Opcional)</label>
                            <input type="text" name="cnpj" value="{{ old('cnpj', $company->cnpj) }}"
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner transition-all">
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div class="space-y-2 group">
                            <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Telefone / WhatsApp</label>
                            <input type="tel" name="phone" value="{{ old('phone', $company->phone) }}" required
                                oninput="this.value = this.value.replace(/[^0-9]/g, '')"
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner transition-all">
                        </div>

                        <div class="space-y-2 md:col-span-2 group">
                            <label class="text-gray-500 font-black uppercase text-[10px] tracking-widest ml-2 transition-colors group-focus-within:text-orange-500">Endereço Completo</label>
                            <input type="text" name="address" value="{{ old('address', $company->address) }}" required
                                class="w-full bg-gray-800 border-none rounded-2xl p-4 text-white font-bold focus:ring-2 focus:ring-orange-500 shadow-inner transition-all">
                        </div>
                    </div>

                    <div class="p-6 bg-gray-800/30 rounded-[2rem] border border-gray-800 flex flex-col md:flex-row items-center gap-8">
                        <div class="w-32 h-32 rounded-3xl bg-gray-800 border-2 border-dashed border-gray-700 flex items-center justify-center overflow-hidden">
                            @if($company->logo)
                                <img src="{{ asset('storage/' . $company->logo) }}" class="w-full h-full object-cover">
                            @else
                                <span class="text-3xl">🖼️</span>
                            @endif
                        </div>
                        <div class="flex-grow space-y-2">
                            <label class="text-orange-500 font-black uppercase text-[10px] tracking-widest">Alterar Logotipo</label>
                            <input type="file" name="logo" class="w-full text-xs text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[10px] file:font-black file:bg-orange-600 file:text-white hover:file:bg-orange-500">
                            <p class="text-[9px] text-gray-600 uppercase font-bold">Recomendado: 512x512px (PNG ou JPG)</p>
                        </div>
                    </div>

                    <div class="pt-6">
                        <button type="submit"
                            class="w-full bg-orange-600 hover:bg-orange-500 text-white font-black py-4 rounded-2xl transition-all uppercase text-xs tracking-widest shadow-lg shadow-orange-600/20 active:scale-95 flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            Atualizar Informações
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-bar-layout>
