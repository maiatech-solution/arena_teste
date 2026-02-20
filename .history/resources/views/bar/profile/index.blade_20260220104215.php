<x-bar-layout>
    <div class="max-w-7xl mx-auto px-4 py-10">

        {{-- Cabeçalho Estilo PDV --}}
        <div class="flex items-center gap-4 mb-10">
            <a href="{{ route('bar.dashboard') }}"
               class="p-3 bg-gray-800 hover:bg-gray-700 text-white rounded-2xl transition border border-gray-700 shadow-lg group">
                <span class="group-hover:-translate-x-1 transition-transform duration-200 inline-block text-xl">◀</span>
            </a>
            <div>
                <h1 class="text-4xl font-black text-white uppercase tracking-tighter italic">
                    Configurações de <span class="text-orange-500">Perfil</span>
                </h1>
                <p class="text-gray-500 font-bold text-[10px] uppercase tracking-[0.2em] mt-1">Gestão de conta do operador de bar</p>
            </div>
        </div>

        <div class="space-y-12">

            {{-- SEÇÃO: INFO PESSOAL --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-black text-white uppercase italic">Informações da Conta</h3>
                    <p class="text-gray-500 text-xs font-bold uppercase mt-2 leading-relaxed">Atualize o nome e o e-mail associado ao seu acesso no PDV.</p>
                </div>

                <div class="md:col-span-2 bg-gray-900 border border-gray-800 p-8 rounded-[2.5rem] shadow-2xl overflow-hidden relative group">
                    <div class="max-w-xl">
                        @include('profile.partials.update-profile-information-form')
                    </div>
                </div>
            </div>

            <div class="border-t border-gray-800/50"></div>

            {{-- SEÇÃO: SENHA --}}
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div>
                    <h3 class="text-lg font-black text-white uppercase italic">Segurança</h3>
                    <p class="text-gray-500 text-xs font-bold uppercase mt-2 leading-relaxed">Mantenha sua senha segura e evite compartilhá-la com outros operadores.</p>
                </div>

                <div class="md:col-span-2 bg-gray-900 border border-gray-800 p-8 rounded-[2.5rem] shadow-2xl overflow-hidden">
                    <div class="max-w-xl">
                        @include('profile.partials.update-password-form')
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- CSS Inline para blindar o tema Dark contra estilos do Blade original --}}
    <style>
        input {
            background-color: rgba(0,0,0,0.5) !important;
            border-color: #1f2937 !important;
            color: #fff !important;
            border-radius: 0.75rem !important;
            font-weight: 700 !important;
        }
        input:focus { border-color: #f97316 !important; ring-color: #f97316 !important; }

        label {
            color: #6b7280 !important;
            text-transform: uppercase !important;
            font-weight: 900 !important;
            font-size: 10px !important;
            letter-spacing: 1px !important;
        }

        button[type="submit"] {
            background-color: #f97316 !important;
            border-radius: 0.75rem !important;
            font-weight: 900 !important;
            text-transform: uppercase !important;
            letter-spacing: 1px !important;
            padding: 0.75rem 1.5rem !important;
            font-size: 11px !important;
        }

        .text-gray-600 { color: #9ca3af !important; }
    </style>
</x-bar-layout>
