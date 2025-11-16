<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Minhas Reservas') }}
        </h2>
    </x-slot>

    <style>
        /* Estilos CSS adicionais para o histórico */
        .arena-bg {
            background: linear-gradient(135deg, #1e3a8a 0%, #10b981 100%);
        }
    </style>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-2xl sm:rounded-xl p-6 lg:p-10">

                {{-- Botão de Retorno --}}
                <a href="{{ route('reserva.index') }}"
                   class="inline-flex items-center text-indigo-600 hover:text-indigo-800 transition duration-150 mb-6 font-semibold">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    Voltar para Agendamento
                </a>

                <h1 class="text-3xl font-extrabold text-gray-900 mb-6 border-b pb-4">
                    Histórico de Agendamentos ({{ Auth::user()->name }})
                </h1>

                @if (session('success'))
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('success') }}</p>
                    </div>
                @endif
                @if (session('error'))
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md" role="alert">
                        <p class="font-medium">{{ session('error') }}</p>
                    </div>
                @endif

                {{-- MENSAGEM DE REGRA DE NEGÓCIO (Não pode cancelar) --}}
                <div class="p-4 mb-6 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-lg shadow-md">
                    <p class="font-semibold text-sm">
                        ⚠️ **Regra da Arena:** Cancelamentos de reservas só podem ser realizados pelo Gestor. Se precisar cancelar, por favor, entre em contato via WhatsApp.
                    </p>
                </div>


                {{-- Lista de Reservas --}}
                <div class="space-y-4">
                    @forelse ($reservations as $reserva)
                        @php
                            // Lógica para colorir o status na tabela
                            $statusColor = [
                                'pending' => 'bg-orange-100 text-orange-800 border-orange-500',
                                'confirmed' => 'bg-green-100 text-green-800 border-green-500',
                                'cancelled' => 'bg-red-100 text-red-800 border-red-500',
                                'rejected' => 'bg-gray-100 text-gray-800 border-gray-500',
                            ][$reserva->status] ?? 'bg-gray-100 text-gray-800 border-gray-500';

                            // Link para WhatsApp (O cliente deve usar o WhatsApp da gestão)
                            // 🛑 SUBSTITUA "SEU_NUMERO_WHATSAPP_GESTÃO" PELO NÚMERO REAL
                            $waNumber = "5511999990000";
                            $waMessage = urlencode("Olá, sou o cliente *".Auth::user()->name."* (ID: ".Auth::id().") e preciso de ajuda para gerenciar minha reserva #{$reserva->id} para {$reserva->date->format('d/m/Y')} às {$reserva->start_time}.");
                            $waLink = "https://wa.me/{$waNumber}?text={$waMessage}";

                        @endphp

                        <div class="p-5 border-l-4 rounded-xl shadow-md flex justify-between items-start {{ $statusColor }}">

                            {{-- Detalhes da Reserva --}}
                            <div class="flex-1">
                                <p class="text-xs font-semibold uppercase mb-1
                                    {{ $reserva->is_recurrent ? 'text-indigo-700' : 'text-gray-500' }}">
                                    {{ $reserva->is_recurrent ? 'SÉRIE RECORRENTE' : 'RESERVA PONTUAL' }}
                                    @if ($reserva->is_recurrent)
                                        <span class="ml-1 text-[10px] font-normal text-indigo-500">(Membro da série ID: {{ $reserva->recurrent_series_id ?? $reserva->id }})</span>
                                    @endif
                                </p>
                                <p class="text-2xl font-extrabold text-gray-900 mb-1">
                                    {{ $reserva->date->format('d/m/Y') }}
                                </p>
                                <p class="text-lg font-semibold text-indigo-700">
                                    {{ \Carbon\Carbon::parse($reserva->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($reserva->end_time)->format('H:i') }}
                                    <span class="ml-3 text-sm font-bold text-green-700">
                                        R$ {{ number_format($reserva->price, 2, ',', '.') }}
                                    </span>
                                </p>
                                @if ($reserva->notes)
                                    <p class="mt-2 text-xs italic text-gray-600">Obs: {{ \Illuminate\Support\Str::limit($reserva->notes, 50) }}</p>
                                @endif
                            </div>

                            {{-- Status e Ações --}}
                            <div class="text-right flex flex-col space-y-2">
                                <span class="px-3 py-1 text-xs font-bold rounded-full uppercase shadow-sm {{ $statusColor }}">
                                    {{ $reserva->statusText }}
                                </span>

                                {{-- BOTÃO DE CONTATO (Substitui o cancelamento) --}}
                                @if ($reserva->status === 'confirmed' || $reserva->status === 'pending')
                                    <a href="{{ $waLink }}" target="_blank"
                                       class="px-3 py-1 text-xs bg-green-500 text-white font-semibold rounded-md shadow hover:bg-green-600 transition">
                                        Falar com Gestor
                                    </a>
                                @endif

                                {{-- Exibe o motivo se for cancelada --}}
                                @if ($reserva->cancellation_reason)
                                    <span title="{{ $reserva->cancellation_reason }}" class="text-[10px] italic text-red-600 underline cursor-help">
                                        Motivo do Cancel.
                                    </span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="p-8 bg-gray-50 rounded-xl text-center border border-gray-200">
                            <p class="text-xl font-semibold text-gray-600">
                                Você ainda não tem reservas cadastradas.
                            </p>
                            <a href="{{ route('reserva.index') }}" class="mt-4 inline-block text-indigo-600 hover:text-indigo-800 underline">
                                Clique aqui para agendar sua quadra!
                            </a>
                        </div>
                    @endforelse

                    {{-- Paginação --}}
                    <div class="mt-8">
                        {{ $reservations->links() }}
                    </div>
                </div>

            </div>
        </div>
    </div>
</x-app-layout>
