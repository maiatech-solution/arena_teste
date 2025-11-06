<x-guest-layout>

    <div class="w-full lg:max-w-screen-2xl mx-auto mt-12 mb-32 px-4 sm:px-6 py-12 bg-white dark:bg-gray-800 shadow-3xl shadow-indigo-200/50 dark:shadow-indigo-900/50 overflow-hidden rounded-3xl">

        <h1 class="text-5xl font-extrabold text-gray-900 dark:text-gray-100 mb-10 border-b border-indigo-100 dark:border-gray-700 pb-4 text-center tracking-tight">
            ✨ ELITE SOCCER
        </h1>

        {{-- --- Mensagens de Status --- --}}

        @if (session('success'))
            <div class="bg-green-100 dark:bg-green-900/30 border-l-4 border-green-500 text-green-700 dark:text-green-300 p-4 rounded-lg relative mb-6 flex items-center shadow-md" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
                <span class="font-semibold">SUCESSO: {{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg relative mb-6 flex items-center shadow-md" role="alert">
                <svg class="w-6 h-6 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                <span class="font-semibold">ERRO: {{ session('error') }}</span>
            </div>
        @endif

        @if ($errors->any() && !old('date'))
            <div class="bg-red-100 dark:bg-red-900/30 border-l-4 border-red-500 text-red-700 dark:text-red-300 p-4 rounded-lg relative mb-8 shadow-md" role="alert">
                <p class="font-bold flex items-center"><svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path></svg> Erro na Submissão!</p>
                <p>Por favor, selecione um horário e complete os dados no modal corretamente.</p>
            </div>
        @endif

        @if (session('whatsapp_link'))
            <div class="bg-green-50 dark:bg-green-900/30 border border-green-300 dark:border-green-700 p-8 rounded-2xl relative mb-12 text-center shadow-xl shadow-green-300/50 dark:shadow-green-900/50" role="alert">
                <p class="font-extrabold mb-3 text-3xl text-green-700 dark:text-green-300">✅ RESERVA PRÉ-APROVADA!</p>
                <p class="mb-6 text-lg text-gray-700 dark:text-gray-300">
                    Sua vaga foi reservada por 30 minutos. **Clique abaixo imediatamente** para confirmar o pagamento do sinal via WhatsApp.
                </p>
                <a href="{{ session('whatsapp_link') }}" target="_blank"
                    class="mt-2 inline-flex items-center px-10 py-4 bg-green-600 text-white font-extrabold rounded-full shadow-2xl shadow-green-600/50 hover:bg-green-700 transition duration-300 transform hover:scale-[1.05] active:scale-[0.98] uppercase tracking-wider">
                    <svg class="w-6 h-6 mr-3" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path d="M17.432 2.156A9.957 9.957 0 0010 0C4.477 0 0 4.477 0 10c0 1.956.685 3.766 1.83 5.216l-1.636 4.757a.75.75 0 00.974.974l4.757-1.636A9.957 9.097 0 0010 20c5.523 0 10-4.477 10-10 0-3.328-1.626-6.297-4.17-8.156zM8 14H6V8h2v6zm4 0h-2V8h2v6zm4 0h-2V8h2v6z"></path></svg>
                    ENVIAR COMPROVANTE VIA WHATSAPP
                </a>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-4 italic">O horário será liberado se o comprovante não for enviado.</p>
            </div>
        @endif


        {{-- ✅ Seleção de Data centralizada --}}
        <h2 class="text-4xl font-extrabold text-gray-900 dark:text-gray-100 mb-6 text-center tracking-tight">
            🗓️ Seleção de Data e Horário
        </h2>

        <p class="text-gray-600 dark:text-gray-400 mb-8 text-center text-xl font-medium">
            Selecione o dia e o bloco de 1 hora disponível:
        </p>

        {{-- Grade de Horários (Estilização Aprimorada para Telas Grandes) --}}
        @if (empty($weeklySchedule))
            <p class="text-center py-12 text-2xl text-gray-500 dark:text-gray-400 bg-gray-100 dark:bg-gray-700 rounded-xl shadow-inner border border-gray-200 dark:border-gray-700">
                Parece que todos os horários estão ocupados. 😔
            </p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-5 gap-6">
                @foreach ($weeklySchedule as $dateString => $slots)
                    @php
                        $date = \Carbon\Carbon::parse($dateString);
                        $dayName = $date->isoFormat('dddd');
                        $formattedDate = $date->isoFormat('D/MM');
                    @endphp

                    {{-- Card de Dia da Semana --}}
                    <div class="bg-white dark:bg-gray-800 p-6 rounded-2xl shadow-xl hover:shadow-indigo-400/30 dark:hover:shadow-indigo-700/50 transition-all duration-300 border-t-6 border-indigo-600 dark:border-indigo-500 flex flex-col h-full">

                        {{-- Cabeçalho do Dia --}}
                        <h3 class="text-2xl font-black text-indigo-800 dark:text-indigo-300 pb-3 mb-4 uppercase tracking-wider border-b border-indigo-100 dark:border-indigo-900">
                            {{ $dayName }}
                            <span class="text-base font-medium text-gray-500 dark:text-gray-400 ml-1">({{ $formattedDate }})</span>
                        </h3>

                        {{-- Slots de Horário --}}
                        <div class="space-y-4 flex-grow">
                            @forelse ($slots as $slot)
                                {{-- Botão com Estilo de Destaque --}}
                                <button
                                    type="button"
                                    class="open-modal w-full flex flex-col items-start sm:flex-row sm:justify-between sm:items-center p-4 rounded-xl transition duration-300
                                        bg-indigo-100 dark:bg-indigo-900/40 border border-indigo-300 dark:border-indigo-700
                                        hover:bg-indigo-500 hover:text-white dark:hover:bg-indigo-600 transform hover:scale-[1.03] active:scale-[0.98]
                                        text-gray-900 dark:text-gray-100 font-bold shadow-md hover:shadow-lg"
                                    data-date="{{ $dateString }}"
                                    data-start="{{ $slot['start_time'] }}"
                                    data-end="{{ $slot['end_time'] }}"
                                    data-price="{{ $slot['price'] }}"
                                >
                                    {{-- Horário --}}
                                    <span class="text-xl font-extrabold text-indigo-700 dark:text-indigo-300 group-hover:text-white transition duration-300 mb-1 sm:mb-0">
                                        {{ $slot['start_time'] }} - {{ $slot['end_time'] }}
                                    </span>
                                    {{-- Preço --}}
                                    <span class="font-extrabold text-sm bg-white dark:bg-gray-900 px-3 py-1 rounded-full border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 shadow-sm">
                                        R$ {{ number_format($slot['price'], 2, ',', '.') }}
                                    </span>
                                </button>
                            @empty
                                <div class="text-center py-5 text-base text-gray-500 dark:text-gray-400 bg-gray-50 dark:bg-gray-700 rounded-xl italic border border-dashed border-gray-300 dark:border-gray-600">
                                    <p>Dia Lotado! Sem horários.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </div>
        @endif




        {{-- --- Modal de Confirmação de Dados --- --}}
        <div id="booking-modal" class="fixed inset-0 bg-gray-900 bg-opacity-80 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
            <div class="bg-white dark:bg-gray-800 p-8 rounded-3xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto transform transition-all duration-300 scale-100 border-t-8 border-indigo-600">

                {{-- INSTRUÇÃO DE PAGAMENTO (Estilo mais urgente) --}}
                <div class="mb-8 p-4 bg-red-50 dark:bg-red-900/30 border-l-4 border-red-600 text-red-800 rounded-lg shadow-md dark:border-red-400 dark:text-red-200">
                    <div class="flex items-center mb-2">
                        <svg class="w-6 h-6 mr-3 text-red-600 flex-shrink-0 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <p class="font-black text-lg uppercase tracking-wider">Atenção!</p>
                    </div>
                    <p class="mt-2 text-sm leading-relaxed font-semibold">
                        Sua vaga é garantida **apenas** após o **envio imediato do comprovante do sinal** via WhatsApp.
                    </p>
                </div>

                <h4 class="text-3xl font-extrabold mb-6 text-gray-900 dark:text-gray-100 border-b pb-3">Confirme Seus Dados</h4>

                {{-- Detalhes da Reserva (Visual Aprimorado) --}}
                <div class="mb-8 p-5 bg-indigo-50 dark:bg-indigo-900/30 rounded-xl border border-indigo-200 dark:border-indigo-700">
                    <p class="font-semibold text-xl text-indigo-800 dark:text-indigo-300 flex justify-between mb-2">
                        Data: <span id="modal-date" class="font-extrabold text-gray-900 dark:text-gray-100"></span>
                    </p>
                    <p class="font-semibold text-2xl text-indigo-800 dark:text-indigo-300 flex justify-between mb-4">
                        Horário: <span id="modal-time" class="font-extrabold text-gray-900 dark:text-gray-100"></span>
                    </p>
                    <hr class="border-indigo-200 dark:border-indigo-700">
                    <p class="font-extrabold text-4xl text-green-700 dark:text-green-400 mt-4 flex justify-between items-end">
                        Total: <span class="font-extrabold">R$ <span id="modal-price"></span></span>
                    </p>
                </div>

                <form id="booking-form" method="POST" action="{{ route('reserva.store') }}">
                    @csrf

                    {{-- Campos Hidden... --}}
                    <input type="hidden" name="date" id="form-date" value="{{ old('date') }}">
                    <input type="hidden" name="start_time" id="form-start" value="{{ old('start_time') }}">
                    <input type="hidden" name="end_time" id="form-end" value="{{ old('end_time') }}">
                    <input type="hidden" name="price" id="form-price" value="{{ old('price') }}">

                    <div class="mb-5">
                        <label for="client_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seu Nome Completo</label>
                        <input type="text" name="client_name" id="client_name" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('client_name') border-red-500 @enderror"
                            value="{{ old('client_name') }}">
                        @error('client_name')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="mb-8">
                        <label for="client_contact" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Seu WhatsApp (apenas números)</label>
                        <input type="tel" name="client_contact" id="client_contact" required
                            class="mt-1 block w-full border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 rounded-lg shadow-md focus:border-indigo-500 focus:ring-indigo-500 @error('client_contact') border-red-500 @enderror"
                            value="{{ old('client_contact') }}">
                        @error('client_contact')
                            <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                        @enderror
                    </div>


                    <div class="flex flex-col sm:flex-row justify-end space-y-3 sm:space-y-0 sm:space-x-4 pt-4 border-t dark:border-gray-700">
                        {{-- Botão Cancelar --}}
                        <button type="button" id="close-modal" class="order-2 sm:order-1 px-6 py-3 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-semibold rounded-full hover:bg-gray-300 dark:hover:bg-gray-600 transition">
                            Voltar / Cancelar
                        </button>
                        {{-- Botão Enviar --}}
                        <button type="submit" class="order-1 sm:order-2 px-8 py-3 bg-indigo-600 text-white font-extrabold rounded-full hover:bg-indigo-700 transition shadow-xl shadow-indigo-500/50 transform hover:scale-[1.03] active:scale-[0.97]">
                            <svg class="w-5 h-5 inline mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-3-3v6m-9 5h18a2 2 0 002-2V5a2 2 0 00-2-2H3a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Confirmar Pré-Reserva
                        </button>
                    </div>
                </form>
            </div>
        </div>

    </div>

{{-- Scripts... (mantidos) --}}
<script>
    function maskWhatsapp(value) {
        if (!value) return "";
        const digits = value.replace(/\D/g, "").substring(0, 11);
        let result = digits;

        if (digits.length > 2) {
            result = "(" + digits.substring(0, 2) + ") " + digits.substring(2);
        }

        if (digits.length > 6) {
            if (digits.length > 10) {
                 result = result.replace(/(\d{5})(\d{4})$/, "$1-$2");
            } else {
                 result = result.replace(/(\d{4})(\d{4})$/, "$1-$2");
            }
        }

        return result;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('booking-modal');
        const closeModalButton = document.getElementById('close-modal');
        const contactInput = document.getElementById('client_contact');

        if (contactInput) {
            contactInput.addEventListener('input', (e) => {
                e.target.value = maskWhatsapp(e.target.value);
            });
            if (contactInput.value) {
                contactInput.value = maskWhatsapp(contactInput.value);
            }
        }

        function formatarDataBrasileira(dateString) {
            const date = new Date(dateString + 'T00:00:00');
            const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
            return date.toLocaleDateString('pt-BR', options);
        }

        document.querySelectorAll('.open-modal').forEach(button => {
            button.addEventListener('click', () => {
                const date = button.dataset.date;
                const start = button.dataset.start;
                const end = button.dataset.end;
                const price = parseFloat(button.dataset.price).toFixed(2).replace('.', ',');

                document.getElementById('modal-date').textContent = formatarDataBrasileira(date);
                document.getElementById('modal-time').textContent = `${start} - ${end}`;
                document.getElementById('modal-price').textContent = price;

                document.getElementById('form-date').value = date;
                document.getElementById('form-start').value = start;
                document.getElementById('form-end').value = end;
                document.getElementById('form-price').value = button.dataset.price;

                if (!'{{ old('client_name') }}') {
                    document.getElementById('client_name').value = '';
                }
                if (!'{{ old('client_contact') }}') {
                    document.getElementById('client_contact').value = '';
                }

                modal.classList.remove('hidden');
                modal.classList.add('flex');
            });
        });

        // Reabrir Modal se a validação falhou e houver dados antigos
        @if ($errors->any() && old('date') && old('start_time'))
            const oldDate = '{{ old('date') }}';
            const oldStart = '{{ old('start_time') }}';
            const oldEnd = '{{ old('end_time') }}';
            const oldPrice = parseFloat('{{ old('price') }}').toFixed(2).replace('.', ',');

            document.getElementById('modal-date').textContent = formatarDataBrasileira(oldDate);
            document.getElementById('modal-time').textContent = `${oldStart} - ${oldEnd}`;
            document.getElementById('modal-price').textContent = oldPrice;

            if (contactInput && contactInput.value) {
                contactInput.value = maskWhatsapp(contactInput.value);
            }

            modal.classList.remove('hidden');
            modal.classList.add('flex');
        @endif

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
    });
</script>

</x-guest-layout>
