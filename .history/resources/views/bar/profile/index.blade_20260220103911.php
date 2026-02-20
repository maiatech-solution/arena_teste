<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 py-10">

        {{-- Cabeçalho --}}
        <div class="flex items-center gap-4 mb-10">
            <a href="{{ route('bar.pos.index') }}"
               class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group">
                <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block">◀</span>
            </a>
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Meu <span class="text-orange-500">Perfil</span>
                </h1>
                <p class="text-gray-500 font-bold text-xs uppercase tracking-widest mt-1">Gerencie suas credenciais de acesso ao PDV</p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">

            {{-- Coluna da Esquerda: Info --}}
            <div class="md:col-span-1">
                <h3 class="text-lg font-black text-white uppercase italic">Informações Pessoais</h3>
                <p class="text-gray-500 text-sm mt-2 font-bold">Atualize seu nome de guerra e e-mail de acesso.</p>
            </div>

            {{-- Coluna da Direita: Formulário Info --}}
            <div class="md:col-span-2">
                <div class="bg-gray-900 border border-gray-800 p-8 rounded-[2.5rem] shadow-2xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="col-span-full border-b border-gray-800 my-4"></div>

            {{-- Coluna da Esquerda: Senha --}}
            <div class="md:col-span-1">
                <h3 class="text-lg font-black text-white uppercase italic">Segurança</h3>
                <p class="text-gray-500 text-sm mt-2 font-bold">Garanta que sua senha seja forte e exclusiva.</p>
            </div>

            {{-- Coluna da Direita: Formulário Senha --}}
            <div class="md:col-span-2">
                <div class="bg-gray-900 border border-gray-800 p-8 rounded-[2.5rem] shadow-2xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

        </div>
    </div>

    {{-- Script para adaptar o estilo dos inputs originais do Breeze para o tema Dark --}}
    <style>
        input {
            background-color: rgba(0,0,0,0.3) !important;
            border-color: #374151 !important;
            color: white !important;
            border-radius: 0.75rem !important;
        }
        label { color: #9CA3AF !important; font-weight: 800 !important; text-transform: uppercase !important; font-size: 0.75rem !important; }
        button[type="submit"] {
            background-color: #EA580C !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            border-radius: 0.75rem !important;
        }
    </style>
</x-bar-layout>
