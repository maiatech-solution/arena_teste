<?php
// [START OF FILE]

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use App\Models\Schedule;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

// --- IMPORTS ---
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AdminController extends Controller
{
    /**
     * Exibe o dashboard principal do gestor.
     * 🚨 CORRIGIDO: Remove a lógica de eventos, que agora é feita via API.
     */
    public function dashboard()
    {
        // >>> A lógica de coleta de $reservas e $events foi REMOVIDA daqui. <<<

        // >>> ESTA LINHA CALCULA A CONTAGEM DE PENDÊNCIAS <<<
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        // 🚨 O método retorna APENAS a contagem de pendências. O calendário carrega os eventos via API.
        return view('dashboard', compact('reservasPendentesCount'));
    }

    // =========================================================================
    // 🗓️ MÉTODO API: RESERVAS CONFIRMADAS PARA FULLCALENDAR
    // (Conectado à rota '/api/reservas/confirmadas')
    // =========================================================================
    /**
     * Retorna as reservas confirmadas em formato JSON para o FullCalendar.
     */
    public function getConfirmedReservasApi(Request $request)
    {
        // O FullCalendar envia os parâmetros 'start' e 'end' para filtrar o período
        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();

        // Busca apenas reservas confirmadas e que se enquadram no período de visualização
        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->whereDate('date', '>=', $start->toDateString())
                            ->whereDate('date', '<=', $end->toDateString())
                            ->with('user')
                            ->get();

        $events = $reservas->map(function ($reserva) {
            // A sua tabela Reserva usa colunas separadas para data, start_time e end_time.
            $bookingDate = $reserva->date->toDateString();

            // É fundamental garantir que o parse seja feito com o campo correto, que aqui é TIME
            $start = Carbon::parse($bookingDate . ' ' . $reserva->start_time);
            $end = $reserva->end_time ? Carbon::parse($bookingDate . ' ' . $reserva->end_time) : $start->copy()->addHour();

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            // Monta o título do evento
            $title = 'Reservado: ' . $clientName;
            if (isset($reserva->price)) {
                $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            }

            return [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'color' => '#4f46e5', // Indigo para reservas confirmadas
                'className' => 'fc-event-booked', // Classe CSS
                'extendedProps' => [
                    'status' => 'booked',
                    'client_contact' => $reserva->client_contact,
                ]
            ];
        });

        return response()->json($events);
    }
    // =========================================================================

    // =========================================================================
    // 🚀 NOVO MÉTODO API: Agendamento Rápido via Calendário
    // (Localizado após as APIs do calendário e antes dos métodos de CRUD)
    // =========================================================================
    /**
     * Armazena uma reserva de cliente criada manualmente via API de Agendamento Rápido.
     * Esta reserva é confirmada imediatamente.
     */
    public function storeQuickReservaApi(Request $request)
    {
        // 1. Validação (Valores básicos do cliente e do slot)
        $validated = $request->validate([
            'client_name' => ['required', 'string', 'max:255'],
            'client_contact' => ['required', 'string', 'max:255'],
            'schedule_id' => ['required', 'integer', 'exists:schedules,id'],
            'date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'price' => ['required', 'numeric', 'min:0.01'],
        ]);

        $date = $validated['date'];
        $startTime = $validated['start_time'];
        $endTime = $validated['end_time'];

        // Constrói o DATETIME completo para os campos 'start_time' e 'end_time' da Reserva.
        // Isso é crucial para a lógica de sobreposição e para consistência dos dados.
        $startDatetime = Carbon::parse($date . ' ' . $startTime);
        $endDatetime = Carbon::parse($date . ' ' . $endTime);

        // 2. Checagem de Conflito (Garantia de que o slot não foi reservado no meio tempo)
        // Checa conflito contra QUALQUER reserva (fixa ou pontual) confirmada/pendente na data
        $conflictReserva = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
             ->where(function ($query) use ($startDatetime, $endDatetime) {
                 // Verifica sobreposição de períodos de tempo (datetimes)
                 // Se o novo horário (start) é antes do fim de um existente, E
                 // o novo horário (end) é depois do início de um existente, HÁ CONFLITO.
                 $query->where('start_time', '<', $endDatetime->toDateTimeString())
                       ->where('end_time', '>', $startDatetime->toDateTimeString());
             })
             ->exists();

        if ($conflictReserva) {
             // Retorna um erro JSON se houver conflito
            return response()->json([
                'success' => false,
                'message' => 'Conflito! O horário acabou de ser reservado ou existe um conflito de horário. Recarregue a página.',
            ], 409); // 409 Conflict
        }

        // 3. Criação da Reserva (Imediatamente Confirmada)
        try {
            Reserva::create([
                'user_id' => null, // Cliente avulso/manual
                'schedule_id' => $validated['schedule_id'], // Linka ao slot de disponibilidade (Schedule)
                'date' => $date, // Mantém a data no campo de data avulsa
                'start_time' => $startDatetime->toDateTimeString(), // Salva o datetime completo
                'end_time' => $endDatetime->toDateTimeString(),     // Salva o datetime completo
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => 'Agendamento Rápido via Gestor',
                'status' => Reserva::STATUS_CONFIRMADA,
                'is_fixed' => false,
                'day_of_week' => $startDatetime->dayOfWeek,
                'manager_id' => Auth::id(), // Registra o gestor que criou
            ]);

            // 🚨 CORREÇÃO: Usar um retorno de resposta mais explícito para evitar corrupção JSON
            $responseArray = [
                'success' => true,
                'message' => 'Reserva rápida criada e confirmada com sucesso! O calendário será atualizado.',
            ];

            return response(json_encode($responseArray), 200)
                ->header('Content-Type', 'application/json');

        } catch (\Exception $e) {
            // Retorna erro 500 para qualquer falha de DB ou lógica
            Log::error("Erro ao criar reserva rápida (Admin): " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao salvar a reserva: ' . $e->getMessage(),
            ], 500);
        }
    }
    // =========================================================================
    // FIM DO NOVO MÉTODO API
    // =========================================================================


    // =========================================================================
    // MÉTODO HELPER (CORRIGIDO PARA O CASO DE EXCEÇÕES FIXAS)
    // =========================================================================
    protected function checkOverlap(string $date, string $startTime, string $endTime, bool $isFixed, ?int $ignoreReservaId = null): bool
    {
        $dayOfWeek = Carbon::parse($date)->dayOfWeek;

        // Query Base:
        // 1. Ignora a reserva atual (se estiver em edição)
        // 2. Considera apenas status que bloqueiam (PENDENTE/CONFIRMADA)
        // 3. Checa a sobreposição de tempo (início antes do fim do outro E fim depois do início do outro)
        $baseQuery = Reserva::whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->when($ignoreReservaId, function ($query) use ($ignoreReservaId) {
                return $query->where('id', '!=', $ignoreReservaId);
            })
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime);
            });

        Log::debug(">>> checkOverlap INICIADO (Data: {$date}, Horário: {$startTime}-{$endTime}, Dia Semana: {$dayOfWeek}, Fixo: " . ($isFixed ? 'SIM' : 'NÃO') . ", Ignorar ID: {$ignoreReservaId})");

        if ($isFixed) {
            // Se estamos checando uma reserva FIXA (Fixo vs Fixo):
            // Checamos apenas contra OUTRAS fixas no mesmo dia da semana e horário.
            $queryFixoVsFixo = (clone $baseQuery)
                ->where('is_fixed', true)
                ->where('day_of_week', $dayOfWeek);

            $conflito = $queryFixoVsFixo->exists();

            if ($conflito) {
                 Log::error("!!! CONFLITO FIXO detectado. Query: " . $queryFixoVsFixo->toSql() . " | Params: " . json_encode($queryFixoVsFixo->getBindings()));
            }

            return $conflito;

        } else {
            // Se estamos checando uma reserva PONTUAL (Pontual vs Tudo):
            // Checamos contra QUALQUER reserva (fixa ou pontual) que esteja CONFIRMADA/PENDENTE
            // NA DATA ESPECÍFICA.
            $queryVsTudoNaData = (clone $baseQuery)
                ->where('date', $date);

            $conflito = $queryVsTudoNaData->exists();

            if ($conflito) {
                Log::error("!!! CONFLITO PONTUAL/FIXO NA DATA detectado. Query: " . $queryVsTudoNaData->toSql() . " | Params: " . json_encode($queryVsTudoNaData->getBindings()));
            }

            return $conflito;
        }
    }


    // --- Métodos de Listagem, Ação e Status de Reservas ---

    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
                            ->with('user')
                            ->orderBy('created_at', 'desc')
                            ->paginate(10);
        $pageTitle = 'Pré-Reservas Pendentes';
        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe o índice de reservas confirmadas, ordenadas por data crescente.
     */
    public function confirmed_index(Request $request)
    {
        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                            ->where(function($q) {
                                // Filtra reservas que ainda não passaram
                                // Consideramos que a reserva passou se a data for anterior a hoje.
                                $q->whereDate('date', '>=', Carbon::today()->toDateString());
                            })
                            ->with('user');

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
            // Filtra por reservas criadas/confirmadas pelo gestor logado:
            $query->where('manager_id', Auth::id());
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas (Próximos Agendamentos)';
        }

        // CORREÇÃO APLICADA NA ÚLTIMA RESPOSTA: Ordenar por data crescente (ASC) e hora crescente (ASC)
        $reservas = $query->orderBy('date', 'asc')
                            ->orderBy('start_time', 'asc')
                            ->paginate(15);

        return view('admin.reservas.confirmed_index', compact('reservas', 'pageTitle', 'isOnlyMine'));
    }

    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user');
        return view('admin.reservas.show', compact('reserva'));
    }

    public function confirmarReserva(Reserva $reserva)
    {
        try {
            $dateString = $reserva->date->toDateString();
            $isFixed = $reserva->is_fixed;
            $ignoreId = $reserva->id;

            // 1. Checagem de Conflito
            if ($this->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                 return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento (Pendente ou Confirmado) no mesmo horário.');
            }

            // 2. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_CONFIRMADA,
                'manager_id' => Auth::id(), // O gestor que confirma
            ]);

            return redirect()->route('dashboard')
                              ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');
        } catch (\Exception $e) {
            Log::error("Erro ao confirmar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    public final function rejeitarReserva(Reserva $reserva)
    {
        try {
            // 1. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_REJEITADA,
                'manager_id' => Auth::id(), // Atribui o gestor que rejeitou
            ]);

            return redirect()->route('admin.reservas.index')
                              ->with('success', 'Reserva rejeitada com sucesso e removida da lista de pendentes.');
        } catch (\Exception $e) {
            Log::error("Erro ao rejeitar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * CORRIGIDO: Este método estava quebrado devido a código de debug.
     * Agora ele atualiza o status para CANCELADA e registra o manager_id.
     */
    public function cancelarReserva(Reserva $reserva)
    {
        try {
            // 1. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'manager_id' => Auth::id(), // Atribui o gestor que cancelou
            ]);

            return redirect()->route('admin.reservas.confirmed_index')
                              ->with('success', 'Reserva cancelada com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao cancelar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao cancelar a reserva: ' . $e->getMessage());
        }
    }

    public function updateStatusReserva(Request $request, Reserva $reserva)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in([
                Reserva::STATUS_CONFIRMADA,
                Reserva::STATUS_PENDENTE,
                Reserva::STATUS_REJEITADA,
                Reserva::STATUS_CANCELADA,
            ])],
        ]);
        $newStatus = $validated['status'];

        $updateData = ['status' => $newStatus];

        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            try {
                $dateString = $reserva->date->toDateString();
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($this->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                    return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento (Pendente ou Confirmado) neste horário.');
                }
                // Se for confirmada, define o gestor atual como o responsável pela ação
                $updateData['manager_id'] = Auth::id();
            } catch (\Exception $e) {
                return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        // Também registra o manager_id se estivermos cancelando ou rejeitando, por segurança de auditoria
        if (in_array($newStatus, [Reserva::STATUS_REJEITADA, Reserva::STATUS_CANCELADA]) && !isset($updateData['manager_id'])) {
            $updateData['manager_id'] = Auth::id();
        }

        try {
            $reserva->update($updateData);
            return redirect()->route('admin.reservas.show', $reserva)
                              ->with('success', "Status da reserva alterado para '{$newStatus}' com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da reserva: ' . $e->getMessage());
        }
    }

    public function destroyReserva(Reserva $reserva)
    {
        try {
            $reserva->delete();
            return redirect()->route('admin.reservas.index')
                              ->with('success', 'Reserva excluída permanentemente com sucesso.');
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir a reserva: ' . $e->getMessage());
        }
    }

    // ==========================================================
    // FUNÇÃO 'makeRecurrent' (CRIA SÉRIE RECORRENTE)
    // ==========================================================
    public function makeRecurrent(Request $request)
    {
        // 1. Validação dos Dados
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string|max:255',
        ]);

        $startDate = Carbon::parse($validated['start_date']);
        $endDate = Carbon::parse($validated['end_date']);
        $dayOfWeek = $startDate->dayOfWeek;

        $reservasCriadas = 0;
        $conflitos = 0;

        // 2. Usar Transação
        DB::beginTransaction();
        try {
            // 3. CRIAR O REGISTRO PAI (A SÉRIE) PRIMEIRO
            $recurrentSeriesId = DB::table('recurrent_series')->insertGetId([
                'user_id' => $validated['user_id'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 4. Loop Semanal para Gerar as Reservas (FILHAS)
            $currentDate = $startDate->copy();

            while ($currentDate->lessThanOrEqualTo($endDate)) {
                if ($currentDate->dayOfWeek === $dayOfWeek) {
                    // 5. Verificação de Conflito (Usa 'false' para checar conflitos contra TUDO - pontuais e fixas)
                    $conflitoExistente = $this->checkOverlap(
                        $currentDate->toDateString(),
                        $validated['start_time'],
                        $validated['end_time'],
                        false // Checa contra qualquer reserva já existente nessa data (fixa ou pontual)
                    );

                    if (!$conflitoExistente) {
                        // 6. Criação da Reserva (FILHA)
                        Reserva::create([
                            'user_id' => $validated['user_id'],
                            'schedule_id' => null,
                            'date' => $currentDate->toDateString(),
                            'start_time' => $validated['start_time'],
                            'end_time' => $validated['end_time'],
                            'price' => $validated['price'],
                            'client_name' => User::find($validated['user_id'])->name ?? 'Cliente Fixo',
                            'client_contact' => 'Recorrente',
                            'notes' => $validated['notes'],
                            'status' => Reserva::STATUS_CONFIRMADA,
                            'recurrent_series_id' => $recurrentSeriesId,
                            'is_fixed' => true, // Esta é uma série fixa
                            'day_of_week' => $dayOfWeek,
                            'manager_id' => Auth::id(),
                        ]);
                        $reservasCriadas++;
                    } else {
                        $conflitos++;
                    }
                }
                // Adiciona uma semana para pular para a próxima ocorrência (mais eficiente que addDay())
                $currentDate->addWeek();
            }

            // 7. Sucesso!
            DB::commit();

            // 8. Retorno ao Gestor
            $message = "Série de horários fixos criada. Total de reservas geradas: {$reservasCriadas}.";
            if ($conflitos > 0) {
                $message .= " Atenção: {$conflitos} datas foram puladas devido a conflitos de horário.";
            }

            return redirect()->route('admin.reservas.confirmed_index')->with('success', $message);

        } catch (\Exception $e) {
            // 9. Falha!
            DB::rollBack();
            Log::error("Erro ao criar série recorrente (makeRecurrent): " . $e->getMessage());
            return back()->with('error', 'Erro de DB ao criar a série de reservas: ' . $e->getMessage())->withInput();
        }
    }

    // =================================================================
    // MÉTODOS DE CRIAÇÃO MANUAL DE RESERVA (GESTOR)
    // =================================================================

    public function createReserva()
    {
        // 1. Coleta os Schedules que estão bloqueados por serem usados como horário fixo (recorrente)
        $fixedReservaSlots = Reserva::where('is_fixed', true)
                                           ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                           ->select('day_of_week', 'start_time', 'end_time')
                                           ->get();
        $fixedReservaMap = $fixedReservaSlots->map(function ($reserva) {
            return "{$reserva->day_of_week}-{$reserva->start_time}-{$reserva->end_time}";
        })->toArray();

        // 2. Filtra schedules recorrentes baseados no FixedReservaMap
        $availableRecurringSchedules = Schedule::whereNotNull('day_of_week')
                                                     ->whereNull('date')
                                                     ->where('is_active', true)
                                                     ->get()
                                                     ->filter(function ($schedule) use ($fixedReservaMap) {
                                                         $scheduleKey = "{$schedule->day_of_week}-{$schedule->start_time}-{$schedule->end_time}";
                                                         // Retorna TRUE se o schedule não estiver no mapa de slots fixos
                                                         return !in_array($scheduleKey, $fixedReservaMap);
                                                     });

        $availableDayOfWeeks = $availableRecurringSchedules->pluck('day_of_week')->unique()->map(fn($day) => (int)$day)->toArray();

        // 3. Monta lista de dias disponíveis (sem alterações)
        $hoje = Carbon::today();
        $diasParaVerificar = 180;
        $adHocDates = Schedule::whereNotNull('date')
                            ->where('is_active', true)
                            ->where('date', '>=', $hoje->toDateString())
                            ->where('date', '<=', $hoje->copy()->addDays($diasParaVerificar)->toDateString())
                            ->pluck('date')
                            ->unique()
                            ->toArray();
        $diasDisponiveisNoFuturo = [];
        $period = CarbonPeriod::create($hoje, $hoje->copy()->addDays($diasParaVerificar));
        foreach ($period as $date) {
            $currentDateString = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek;
            $isRecurringAvailable = in_array($dayOfWeek, $availableDayOfWeeks);
            $isAdHocAvailable = in_array($currentDateString, $adHocDates);
            if ($isRecurringAvailable || $isAdHocAvailable) {
                $diasDisponiveisNoFuturo[] = $currentDateString;
            }
        }
        return view('admin.reservas.create', [
            'diasDisponiveisJson' => json_encode(array_values(array_unique($diasDisponiveisNoFuturo))),
        ]);
    }

    // =========================================================================
    // FUNÇÃO 'storeReserva' (CRIA RESERVA MANUALMENTE)
    // =========================================================================
    public function storeReserva(Request $request)
    {
        // 1. Validação
        $validator = Validator::make($request->all(), [
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            // schedule_id é opcional (pode ser null para reservas manuais avulsas)
            'schedule_id' => 'nullable|integer|exists:schedules,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'client_name.required' => 'O nome do cliente é obrigatório.',
            'client_contact.required' => 'O contato do cliente é obrigatório.',
            'date.required' => 'A data é obrigatória.',
            'date.after_or_equal' => 'A data da reserva deve ser hoje ou uma data futura.',
            'start_time.required' => 'O horário de início é obrigatório (selecione um slot).',
            'end_time.required' => 'O horário de fim é obrigatório (selecione um slot).',
            'end_time.after' => 'A hora de fim deve ser depois da hora de início.',
            'price.required' => 'O preço é obrigatório (selecione um slot).',
            'schedule_id.exists' => 'O slot de horário selecionado não é válido.',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                        ->withErrors($validator)
                        ->withInput();
        }

        $validatedData = $validator->validated();
        $isFixed = $request->has('is_fixed');
        $date = $validatedData['date'];
        $startTime = $validatedData['start_time'];
        $endTime = $validatedData['end_time'];

        $managerId = Auth::id();


        // ==========================================================
        // CASO 1: RESERVA PONTUAL (is_fixed = false)
        // ==========================================================
        if (!$isFixed) {
            // Checagem de Conflito contra qualquer tipo de reserva (pontual ou fixa)
            Log::debug("Tentativa de Reserva Pontual. Checando conflito...");
            // A função checkOverlap foi corrigida para olhar APENAS a data,
            // ignorando registros cancelados, o que resolve o problema de exceção.
            if ($this->checkOverlap($date, $startTime, $endTime, false)) {
                return redirect()->back()
                    ->with('error', 'Conflito! O horário já está ocupado por uma reserva pontual ou fixa existente.')
                    ->withInput();
            }
            $dayOfWeek = Carbon::parse($date)->dayOfWeek;
            try {
                Reserva::create([
                    'user_id' => null, // Cliente manual (avulso) não tem user_id
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $date,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    'client_contact' => $validatedData['client_contact'],
                    'notes' => $validatedData['notes'] ?? 'Reserva criada manualmente pelo gestor.',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => false,
                    'day_of_week' => $dayOfWeek,
                    'recurrent_series_id' => null,
                    'manager_id' => $managerId,
                ]);
                return redirect()->route('admin.reservas.confirmed_index')
                                 ->with('success', 'Reserva pontual confirmada com sucesso!');
            } catch (\Exception $e) {
                $errorMessage = $e->getMessage();
                Log::error("Erro ao criar reserva pontual (Admin): " . $errorMessage);
                return redirect()->back()
                    ->with('error', 'Erro do servidor (Pontual): ' . $errorMessage)
                    ->withInput();
            }
        }

        // ==========================================================
        // CASO 2: RESERVA FIXA (is_fixed = true) - Criação de Série
        // ==========================================================

        // 1. Checagem de conflito: Checa APENAS contra OUTRAS fixas no mesmo dia/horário.
        Log::debug("Tentativa de Reserva Fixa. Checando conflito com outras fixas...");
        if ($this->checkOverlap($date, $startTime, $endTime, true)) {
            return redirect()->back()
                ->with('error', 'Conflito Fixo! Este dia da semana/horário já está reservado por outra reserva fixa.')
                ->withInput();
        }

        // 2. Preparar dados
        $startDate = Carbon::parse($date);
        $dayOfWeek = $startDate->dayOfWeek;
        $totalWeeks = 52; // Cria reservas para um ano (52 semanas)
        $reservasCriadas = 0;
        $reservasFalhadas = 0;
        $datasPuladas = [];

        // 3. Usar Transação de DB
        DB::beginTransaction();

        try {
            // 4. CRIAR O REGISTRO PAI (A SÉRIE) PRIMEIRO
            $seriesId = DB::table('recurrent_series')->insertGetId([
                'user_id' => null, // Cliente avulso
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // 5. Loop para criar as 52 reservas FILHAS
            for ($i = 0; $i < $totalWeeks; $i++) {
                $currentDate = $startDate->copy()->addWeeks($i);
                $currentDateString = $currentDate->toDateString();

                // 6. Checagem de conflito: Checa APENAS contra reservas pontuais (is_fixed=false) nesta data.
                $isBlockedByPunctual = Reserva::where('is_fixed', false)
                    ->whereDate('date', $currentDateString) // Checa apenas nesta data
                    ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                    ->where(function ($query) use ($startTime, $endTime) {
                        // Checagem de overlap
                        $query->where('start_time', '<', $endTime)
                              ->where('end_time', '>', $startTime);
                    })
                    ->exists();


                if ($isBlockedByPunctual) {
                    $reservasFalhadas++;
                    $datasPuladas[] = $currentDate->format('d/m/Y');
                    continue;
                }

                // 7. Criar a reserva da semana (FILHA)
                Reserva::create([
                    'user_id' => null,
                    'schedule_id' => $validatedData['schedule_id'],
                    'date' => $currentDateString,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'price' => $validatedData['price'],
                    'client_name' => $validatedData['client_name'],
                    'client_contact' => $validatedData['client_contact'],
                    'notes' => $validatedData['notes'] ?? 'Reserva fixa criada pelo gestor.',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => true,
                    'day_of_week' => $dayOfWeek,
                    'recurrent_series_id' => $seriesId,
                    'manager_id' => $managerId,
                ]);
                $reservasCriadas++;
            }

            // 8. Sucesso!
            DB::commit();

            // 9. Preparar mensagens de feedback
            $successMessage = "Série de {$reservasCriadas} reservas fixas criada com sucesso!";
            $warningMessage = null;
            if ($reservasFalhadas > 0) {
                $warningMessage = "{$reservasFalhadas} datas foram puladas por já estarem ocupadas por reservas pontuais: " . implode(', ', $datasPuladas);
            }
            return redirect()->route('admin.reservas.create')
                             ->with('success', $successMessage)
                             ->with('warning', $warningMessage);

        } catch (\Exception $e) {
            // 10. Falha!
            DB::rollBack();
            $errorMessage = $e->getMessage();
            $errorFile = pathinfo($e->getFile(), PATHINFO_BASENAME);
            $errorLine = $e->getLine();
            $debugMessage = "Erro de DB: \"{$errorMessage}\" em {$errorFile} (Linha: {$errorLine}). Rollback executado.";
            Log::error("Erro ao criar série de reservas fixas (Admin): " . $debugMessage);
            return redirect()->back()
                ->with('error', $debugMessage)
                ->withInput();
        }
    }


    // =========================================================================
    // FUNÇÃO 'getAvailableTimes' (MANTIDA, MAS RECOMENDADO MOVER PARA ReservaController)
    // =========================================================================
    /**
     * Calcula e retorna os horários disponíveis para uma data específica,
     * e inclui log detalhado para debug de conflitos.
     */
    public function getAvailableTimes(Request $request)
    {
        // Este método deve ser movido para ReservaController, onde ele já estava.
        // Se a rota aponta para ReservaController, este método aqui pode ser apagado.
        // Mantendo apenas para referência no estado atual.

        // 1. Validação
        $request->validate([
             'date' => 'required|date_format:Y-m-d',
        ]);

        $dateString = $request->input('date');
        $selectedDate = Carbon::parse($dateString);
        $dayOfWeek = $selectedDate->dayOfWeek;
        $isToday = $selectedDate->isToday();
        $now = Carbon::now();

        // 2. Schedules (slots) definidos para este dia (Recorrentes ou Avulsos)
        $allSchedules = Schedule::where('is_active', true)
            ->where(function ($query) use ($dayOfWeek, $dateString) {
                // Slots recorrentes (para este dia da semana)
                $query->whereNotNull('day_of_week')
                      ->whereNull('date')
                      ->where('day_of_week', $dayOfWeek);
                // Slots avulsos (para esta data específica)
                $query->orWhere(function ($query) use ($dateString) {
                    $query->whereNotNull('date')
                          ->where('date', $dateString);
                });
            })
            ->orderBy('start_time')
            ->get();

        // 3. Reservas Confirmadas/Pendentes para a data
        $occupiedReservas = Reserva::whereDate('date', $dateString)
                                           ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
                                           ->get();

        // --- LOG DE DEBUG ---
        Log::info("DEBUG AGENDAMENTO para data: {$dateString} ({$dayOfWeek})");
        // --- FIM DO LOG DE DEBUG ---

        // 4. Filtrar Schedules Ocupados (Usando Lógica de Sobreposição)
        $availableTimes = $allSchedules->filter(function ($schedule) use ($isToday, $now, $selectedDate, $occupiedReservas) {

            // A. Checagem de slots passados (apenas se for hoje)
            $scheduleStartDateTime = Carbon::parse($selectedDate->toDateString() . ' ' . $schedule->start_time);
            if ($isToday && $scheduleStartDateTime->lt($now)) {
                return false;
            }

            // B. Checagem de Conflito de Horário
            $isBooked = $occupiedReservas->contains(function ($reservation) use ($schedule) {
                // Checa se há sobreposição de horário:
                $overlap = $reservation->start_time < $schedule->end_time && $reservation->end_time > $schedule->start_time;

                return $overlap;
            });

            // Se estiver reservado (isBooked é true), o Schedule não está disponível (retorna false)
            return !$isBooked;

        })->map(function ($schedule) {
            // Formata os dados para o JavaScript
            return [
                'id' => $schedule->id,
                'time_slot' => Carbon::parse($schedule->start_time)->format('H:i') . ' - ' . Carbon::parse($schedule->end_time)->format('H:i'),
                'price' => number_format($schedule->price, 2, ',', '.'),
                'start_time' => Carbon::parse($schedule->start_time)->format('H:i'),
                'end_time' => Carbon::parse($schedule->end_time)->format('H:i'),
                'raw_price' => $schedule->price,
                'schedule_id' => $schedule->id,
            ];
        })->values();

        return response()->json($availableTimes);
    }


    // --- Métodos de CRUD de Usuários ---

    public function indexUsers()
    {
        $users = User::orderBy('name', 'asc')->get();
        return view('admin.users.index', compact('users'));
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|confirmed|min:8',
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor'])],
        ]);
        User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
        ]);
        return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso!');
    }
}
