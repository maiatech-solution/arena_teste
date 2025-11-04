<x-guest-layout>
    <div class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center pt-6 sm:pt-0">
        <div class="w-full sm:max-w-4xl mt-6 px-6 py-4 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-lg">

            <h1 class="text-3xl font-bold text-gray-900 dark:text-gray-100 mb-6 border-b pb-2">
                Agendamento de Horários
            </h1>

            {{-- Mensagem de Sucesso/Erro --}}
            @if (session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            @if (session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('error') }}</span>
                </div>
            @endif

            {{-- Mensagem de WhatsApp (Disparada após a pré-reserva) --}}
            @if (session('whatsapp_link'))
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded relative mb-4 text-center" role="alert">
                    <p class="font-bold mb-2">Finalize sua pré-reserva!</p>
                    <p class="mb-3">Clique no botão abaixo para finalizar o processo de confirmação via WhatsApp com o gestor.</p>
                    <p class="mb-3">Seu horário só será confirmado com comprovante de pagamento do sinal, que pode ser enviado no botão abaixo.</p>
                    <a href="{{ session('whatsapp_link') }}" target="_blank"
                       class="px-4 py-2 bg-red-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                        Abrir WhatsApp para Confirmação
                    </a>
                </div>
            @endif

            <p class="text-gray-600 dark:text-gray-400 mb-6">
                Selecione uma data e horário disponíveis para agendar sua sessão.
                Os horários marcados como "Pendente" ou "Confirmado" não aparecem aqui.
            </p>

            {{-- Grade de Horários (CORRIGIDO: Usa $weeklySchedule) --}}
            @if (empty($weeklySchedule))
                <p class="text-center py-10 text-lg text-gray-500">
                    Não há horários disponíveis nas próximas duas semanas. Por favor, volte mais tarde.
                </p>
            @else
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($weeklySchedule as $dateString => $slots)
                        @php
                            $date = \Carbon\Carbon::parse($dateString);
                            $dayName = $date->isoFormat('dddd');
                            $formattedDate = $date->isoFormat('D [de] MMMM');
                        @endphp

                        <div class="bg-gray-50 dark:bg-gray-700 p-4 rounded-lg shadow-md">
                            <h3 class="text-xl font-semibold text-indigo-600 dark:text-indigo-400 border-b pb-1 mb-3">
                                {{ $dayName }}
                                <span class="text-sm font-normal text-gray-500 dark:text-gray-300 ml-1">({{ $formattedDate }})</span>
                            </h3>

                            <div class="space-y-2">
                                @forelse ($slots as $slot)
                                    <button
                                        type="button"
                                        class="open-modal w-full text-left p-3 rounded-md transition duration-150
                                            bg-white dark:bg-gray-900 border border-gray-300 dark:border-gray-600 hover:bg-indigo-50 dark:hover:bg-indigo-900
                                            text-gray-800 dark:text-gray-100 hover:text-indigo-600 dark:hover:text-indigo-300"
                                        data-date="{{ $dateString }}"
                                        data-start="{{ $slot['start_time'] }}"
                                        data-end="{{ $slot['end_time'] }}"
                                        data-price="{{ $slot['price'] }}"
                                    >
                                        <span class="font-medium">{{ $slot['start_time'] }} - {{ $slot['end_time'] }}</span>
                                        <span class="float-right font-bold text-sm text-green-600 dark:text-green-400">
                                            R$ {{ number_format($slot['price'], 2, ',', '.') }}
                                        </span>
                                    </button>
                                @empty
                                    <p class="text-sm text-gray-500 dark:text-gray-400">Nenhum horário disponível.</p>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Modal de Confirmação de Dados --}}
            <div id="booking-modal" class="fixed inset-0 bg-gray-600 bg-opacity-75 hidden items-center justify-center z-50">
                <div class="bg-white dark:bg-gray-800 p-6 rounded-lg shadow-xl w-full max-w-md">
                    <h4 class="text-2xl font-bold mb-4 text-gray-900 dark:text-gray-100">Confirmar Reserva</h4>
                    <p class="text-gray-600 dark:text-gray-400 mb-4">Você está prestes a reservar o horário:</p>

                    <div class="mb-4 p-3 bg-indigo-50 dark:bg-indigo-900/30 rounded-md">
                        <p class="font-medium text-lg text-indigo-700 dark:text-indigo-300">
                            Data: <span id="modal-date"></span>
                        </p>
                        <p class="font-medium text-lg text-indigo-700 dark:text-indigo-300">
                            Horário: <span id="modal-time"></span>
                        </p>
                        <p class="font-bold text-xl text-green-700 dark:text-green-400 mt-2">
                            Valor: R$ <span id="modal-price"></span>
                        </p>
                    </div>

                    <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                        @csrf

                        <input type="hidden" name="date" id="form-date">
                        <input type="hidden" name="start_time" id="form-start">
                        <input type="hidden" name="end_time" id="form-end">
                        <input type="hidden" name="price" id="form-price">

                        <div class="mb-4">
                            <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seu Nome Completo</label>
                            <input type="text" name="client_name" id="client_name" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="mb-6">
                            <label for="client_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seu WhatsApp (Ex: 5561999999999)</label>
                            <input type="text" name="client_contact" id="client_contact" required
                                class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>

                        <div class="flex justify-end space-x-3">
                            <button type="button" id="close-modal" class="px-4 py-2 bg-red-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-blue-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                                Enviar Pré-Reservar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>

    {{-- Scripts para o Modal --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const modal = document.getElementById('booking-modal');
            const form = document.getElementById('booking-form');
            const closeModalButton = document.getElementById('close-modal');

            // Função para formatar data (opcional, pode ser melhorado com bibliotecas)
            function formatarDataBrasileira(dateString) {
                const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
                // Garante que o Carbon do PHP funcione no JS (usa UTC para evitar problemas de fuso)
                const date = new Date(dateString + 'T00:00:00Z');
                return date.toLocaleDateString('pt-BR', options);
            }

            // Abrir Modal
            document.querySelectorAll('.open-modal').forEach(button => {
                button.addEventListener('click', () => {
                    const date = button.dataset.date;
                    const start = button.dataset.start;
                    const end = button.dataset.end;
                    const price = parseFloat(button.dataset.price).toFixed(2).replace('.', ',');

                    // Atualiza os dados visuais do modal
                    document.getElementById('modal-date').textContent = formatarDataBrasileira(date);
                    document.getElementById('modal-time').textContent = `${start} - ${end}`;
                    document.getElementById('modal-price').textContent = price;

                    // Atualiza os campos escondidos do formulário
                    document.getElementById('form-date').value = date;
                    document.getElementById('form-start').value = start;
                    document.getElementById('form-end').value = end;
                    document.getElementById('form-price').value = button.dataset.price;

                    modal.classList.remove('hidden');
                    modal.classList.add('flex');
                });
            });

            // Fechar Modal
            closeModalButton.addEventListener('click', () => {
                modal.classList.add('hidden');
                modal.classList.remove('flex');
            });

            // Fechar Modal clicando fora
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.classList.add('hidden');
                    modal.classList.remove('flex');
                }
            });

            // Lidar com a mensagem de WhatsApp na abertura (se houver)
            @if (session('whatsapp_link'))
                // Se houver link de WhatsApp na sessão, abra-o automaticamente
                // (Opcional: Apenas se o usuário não clicar no botão da mensagem de sucesso)
                // window.open('{{ session('whatsapp_link') }}', '_blank');
            @endif
        });
    </script>
</x-guest-layout>
