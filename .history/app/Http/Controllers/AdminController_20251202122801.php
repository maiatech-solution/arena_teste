<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Necessário para a função DB::raw()
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon; // Necessário para Carbon::today()
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon as BaseCarbon;
use App\Models\FinancialTransaction;


class AdminController extends Controller
{
    /**
     * @var ReservaController
     */
    protected $reservaController;

    // 🛑 CRÍTICO: Injeção de Dependência para acessar os helpers do ReservaController
    public function __construct(ReservaController $reservaController)
    {
        $this->reservaController = $reservaController;
    }

    // ------------------------------------------------------------------------
    // MÓDULO: DASHBOARDS E LISTAGENS
    // ------------------------------------------------------------------------

    /**
     * Exibe o Dashboard administrativo (FullCalendar).
     */
    public function dashboard()
    {
        // 🛑 DELEGA para o método do ReservaController
        return $this->reservaController->dashboard();
    }

    /**
     * Exibe o painel de botões de gerenciamento de reservas.
     */
    public function indexReservasDashboard()
    {
        // O código de contagem não é mais necessário aqui, a view é estática ou usa contagens simples
        return view('admin.reservas.index-dashboard');
    }

    /**
     * Exibe a lista de Reservas Pendentes.
     */
    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->where('is_fixed', false)
            ->orderBy('date')
            ->orderBy('start_time')
            ->paginate(20);

        return view('admin.reservas.index', [
            'reservas' => $reservas,
            'pageTitle' => 'Pré-Reservas Pendentes',
        ]);
    }

    /**
     * Exibe a lista de Reservas Confirmadas.
     */
    public function confirmed_index(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $isOnlyMine = $request->input('only_mine') === 'true'; // Mantendo a variável, mesmo que o filtro tenha sido simplificado

        $reservas = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            ->where('is_fixed', false)
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%');
                });
            })
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            // O filtro 'only_mine' foi removido do front, mas o código de filtro está aqui para fins de demonstração
            ->when($isOnlyMine, function ($query) {
                return $query->where('manager_id', Auth::id());
            })
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.reservas.confirmed_index', [
            'reservas' => $reservas,
            'pageTitle' => 'Reservas Confirmadas',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isOnlyMine' => $isOnlyMine,
        ]);
    }

    /**
     * ✅ NOVO: Exibe a lista de TODAS as reservas (clientes e slots fixos).
     */
    public function indexTodas(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $filterStatus = $request->input('filter_status');
        $isOnlyMine = $request->input('only_mine') === 'true';

        // 1. Inicia a query com todos os tipos de reservas (clientes e fixas)
        $reservas = Reserva::query();

        // 2. Filtro de Status
        if ($filterStatus) {
            $reservas->where('status', $filterStatus);
        }

        // 3. Filtros de Data
        $reservas
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            });

        // 4. Filtro de Busca (por cliente ou contato, aplica a todos)
        $reservas->when($search, function ($query, $search) {
            return $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('client_contact', 'like', '%' . $search . '%');
            });
        });

        // 5. Ordenação e Paginação (Mostra as reservas mais antigas/próximas primeiro)
        $reservas = $reservas
            ->orderBy('date', 'asc') // ORDEM CRESCENTE (ASC)
            ->orderBy('start_time', 'asc') // ORDEM CRESCENTE (ASC)
            ->paginate(20)
            ->appends($request->except('page'));

        // 6. Retorna a view 'admin.reservas.todas' (que foi criada no Canvas)
        return view('admin.reservas.todas', [
            'reservas' => $reservas,
            'pageTitle' => 'Todas as Reservas (Inventário e Clientes)',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterStatus' => $filterStatus,
            'isOnlyMine' => $isOnlyMine,
        ]);
    }

    // O método 'canceled_index' foi removido, pois a rota não será mais usada.
    // O histórico de cancelamento/rejeição agora é mantido no DB sem a necessidade de deletar.

    /**
     * Exibe o formulário para criação manual de reserva.
     */
    public function createReserva()
    {
        $users = User::where('role', 'cliente')->get();
        // 🛑 CORREÇÃO: O AdminController agora tem um método storeReserva (substituindo o storeManualReserva do seu código)
        return view('admin.reservas.create', compact('users'));
    }

    /**
     * Exibe os detalhes de uma reserva.
     */
    public function showReserva(Reserva $reserva)
    {
        return view('admin.reservas.show', compact('reserva'));
    }

    /**
     * Cria uma nova reserva manual (Admin) - Consome o slot FREE se existir.
     * (Este método substitui o storeManualReserva do seu código)
     */
    public function storeReserva(Request $request)
    {
        // Validação básica (usando lógica já presente)
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        // Normaliza as horas para o formato H:i:s
        $startTimeNormalized = Carbon::createFromFormat('H:i', $validated['start_time'])->format('H:i:s');
        $endTimeNormalized = Carbon::createFromFormat('H:i', $validated['end_time'])->format('H:i:s');

        // Checa se o horário está ocupado por outra reserva real (usando helper do ReservaController)
        if ($this->reservaController->checkOverlap($validated['date'], $validated['start_time'], $validated['end_time'], false)) {
            return redirect()->back()->withInput()->with('error', 'O horário selecionado já está ocupado por outra reserva confirmada ou pendente.');
        }

        // Tenta encontrar um slot fixo livre (STATUS_FREE) para consumo
        $fixedSlot = Reserva::where('is_fixed', true)
            ->where('date', $validated['date'])
            ->where('start_time', $startTimeNormalized)
            ->where('end_time', $endTimeNormalized)
            ->where('status', Reserva::STATUS_FREE) // 🛑 CRÍTICO: Busca por STATUS_FREE
            ->first();

        DB::beginTransaction();
        try {
            if ($fixedSlot) {
                // Consome o slot fixo disponível
                $fixedSlot->delete();
            } else {
                // Aviso se o slot fixo não existia, mas permite a criação
                Log::warning("Reserva manual criada sem consumir slot fixo disponível: {$validated['date']} {$startTimeNormalized}.");
            }

            // Cria a nova reserva confirmada
            Reserva::create([
                'user_id' => $validated['user_id'] ?? null,
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $validated['price'],
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA, // Reserva de cliente confirmada pelo Admin
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva criada e confirmada manualmente com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar reserva manual.", ['exception' => $e, 'data' => $validated]);
            return redirect()->back()->withInput()->with('error', 'Erro interno ao criar reserva. Tente novamente.');
        }
    }


    // ------------------------------------------------------------------------
    // MÓDULO: AÇÕES DE STATUS E CANCELAMENTO
    // ------------------------------------------------------------------------

    /**
     * Confirma uma reserva pendente e registra o sinal financeiro.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
    if ($reserva->status !== Reserva::STATUS_PENDENTE) {
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
        }
        return redirect()->back()->with('error', 'A reserva não está mais pendente.');
    }

    // 2. Validação do Input (Sinal e Recorrência)
    $validated = $request->validate([
        'signal_value' => 'nullable|numeric|min:0',
        // ✅ NOVO: Validação para o campo de recorrência (pode vir como array ou string)
        'is_recurrent' => ['nullable', 'sometimes'],
    ]);

    $sinal = (float)($validated['signal_value'] ?? 0.00);
    $managerId = Auth::id();
    $price = (float)$reserva->price;

    // ✅ NOVA LÓGICA: Checagem robusta da flag de recorrência
    // (A flag pode vir como '1' se for checkbox marcado, ou ausente/ '0' se não estiver)
    $isRecurrent = count(array_filter((array)$request->input('is_recurrent'), function($value) {
        return $value === '1' || $value === true;
    })) > 0;

    $recurrentCount = 0;
    $conflictedOrSkippedCount = 0;

    DB::beginTransaction();
    try {
        // 3. Atualiza a Reserva Mestra (a atual)
        $reserva->status = Reserva::STATUS_CONFIRMADA;
        $reserva->manager_id = $managerId;
        $reserva->signal_value = $sinal;
        $reserva->total_paid = $sinal;
        $reserva->is_recurrent = $isRecurrent; // ✅ ATUALIZA A FLAG

        // Calcular status de pagamento
        if ($sinal > 0) {
            $reserva->payment_status = ($sinal >= $price) ? 'paid' : 'partial';
        } else {
            $reserva->payment_status = 'pending';
        }

        // Se for recorrente, ela se tornará a reserva Mestra
        if ($isRecurrent) {
            $reserva->save(); // Salva antes de usar o ID
            $reserva->recurrent_series_id = $reserva->id;
            $reserva->save();
        } else {
            $reserva->save();
        }

        // 4. 🛑 CONSUMIR O SLOT FIXO ORIGINAL (remover do calendário público)
        if ($reserva->fixed_slot_id) {
            $fixedSlot = Reserva::find($reserva->fixed_slot_id);
            if ($fixedSlot && $fixedSlot->is_fixed && $fixedSlot->status === Reserva::STATUS_FREE) {
                $fixedSlot->delete();
                Log::info("Slot fixo ID: {$reserva->fixed_slot_id} consumido ao confirmar reserva ID: {$reserva->id}");
            }
        }

        // 5. ✅ LÓGICA CRÍTICA: CRIAÇÃO DA SÉRIE RECORRENTE (6 meses)
        if ($isRecurrent) {

            // Início da Lógica de Recorrência (6 meses, pulando a primeira semana)
            $masterReserva = $reserva;
            // Garante que a data é um objeto Carbon
            $masterDate = Carbon::parse($masterReserva->date->format('Y-m-d'));
            $startDate = $masterDate->copy()->addWeek();
            $endDate = $masterDate->copy()->addMonths(6);

            $dayOfWeek = $masterReserva->day_of_week;
            $startTime = $masterReserva->start_time;
            $endTime = $masterReserva->end_time;
            $price = $masterReserva->price;
            $clientName = $masterReserva->client_name;
            $clientContact = $masterReserva->client_contact;
            $userId = $masterReserva->user_id;
            $masterId = $reserva->id;

            $newReservasToCreate = [];
            $currentDate = $startDate->copy();

            while ($currentDate->lessThanOrEqualTo($endDate)) {
                $dateString = $currentDate->toDateString();
                $isConflict = false;

                // Checagem de Conflito (Outros Clientes: confirmed/pending)
                $isOccupiedByOtherCustomer = Reserva::whereDate('date', $dateString)
                    ->where('start_time', '<', $endTime)
                    ->where('end_time', '>', $startTime)
                    ->where('is_fixed', false)
                    ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                    ->exists();

                if ($isOccupiedByOtherCustomer) {
                    $isConflict = true;
                    Log::warning("Conflito com OUTRO CLIENTE durante a repetição da série #{$masterId} na data {$dateString}. Slot pulado.");
                }

                // Busca o slot fixo, se existir, para DELETAR (consumir)
                $fixedSlot = null;
                if (!$isConflict) {
                    $fixedSlot = Reserva::where('is_fixed', true)
                        ->whereDate('date', $dateString)
                        ->where('start_time', $startTime)
                        ->where('end_time', $endTime)
                        ->where('status', Reserva::STATUS_FREE)
                        ->first();
                }

                // Cria a nova reserva se não houver conflito real (confirmado/pendente por outro cliente)
                if (!$isConflict) {
                    $newReservasToCreate[] = [
                        'user_id' => $userId,
                        'manager_id' => $managerId,
                        'date' => $dateString,
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'price' => $price,
                        'signal_value' => 0.00,
                        'total_paid' => 0.00,
                        'payment_status' => 'pending',
                        'client_name' => $clientName,
                        'client_contact' => $clientContact,
                        'status' => Reserva::STATUS_CONFIRMADA,
                        'is_fixed' => false,
                        'is_recurrent' => true,
                        'recurrent_series_id' => $masterId,
                        'created_at' => Carbon::now(),
                        'updated_at' => Carbon::now(),
                    ];

                    if ($fixedSlot) {
                        $fixedSlot->delete(); // Consome o slot verde/FREE
                        Log::debug("Slot fixo ID {$fixedSlot->id} consumido para data recorrente {$dateString} em série {$masterId}.");
                    } else {
                        Log::warning("Nenhum slot fixo encontrado para consumir para data recorrente {$dateString} em série {$masterId}.");
                    }
                } else {
                    $conflictedOrSkippedCount++;
                }

                $currentDate->addWeek();
            }

            if (!empty($newReservasToCreate)) {
                Reserva::insert($newReservasToCreate);
                Log::info("Inserção em massa concluída: " . count($newReservasToCreate) . " reservas recorrentes criadas para série {$masterId}.");
                $recurrentCount = count($newReservasToCreate);
            }
            // Fim da Lógica de Recorrência
        }

        // 6. 🛑 CANCELAR AUTOMATICAMENTE OUTRAS PRÉ-RESERVAS NO MESMO HORÁRIO
        $conflictingPendingReservas = Reserva::where('id', '!=', $reserva->id)
            ->where('date', $reserva->date)
            ->where('start_time', $reserva->start_time)
            ->where('end_time', $reserva->end_time)
            ->where('status', Reserva::STATUS_PENDENTE)
            ->where('is_fixed', false)
            ->get();

        $canceledCount = 0;
        foreach ($conflictingPendingReservas as $conflictingReserva) {
            $conflictingReserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'cancellation_reason' => 'Cancelado automaticamente - Horário confirmado para outro cliente (Reserva ID: ' . $reserva->id . ')',
                'manager_id' => $managerId,
            ]);
            $canceledCount++;

            // Se as reservas canceladas eram de clientes, recria o slot fixo
            $this->reservaController->recreateFixedSlot($conflictingReserva);

            Log::info("Reserva ID: {$conflictingReserva->id} cancelada automaticamente devido à confirmação da reserva ID: {$reserva->id}");
        }

        // 7. Gera a Transação Financeira (Entrada no Caixa)
        if ($sinal > 0) {
            FinancialTransaction::create([
                'reserva_id' => $reserva->id,
                'user_id' => $reserva->user_id,
                'manager_id' => $managerId,
                'amount' => $sinal,
                'type' => 'signal',
                'payment_method' => 'pix',
                'description' => 'Sinal recebido na confirmação do agendamento',
                'paid_at' => Carbon::now(),
            ]);
        }

        DB::commit();

        $message = "Reserva confirmada com sucesso!";
        if ($isRecurrent) {
            $message = "Série recorrente de {$reserva->client_name} criada com sucesso! Total de " . ($recurrentCount + 1) . " reservas agendadas.";
             if ($conflictedOrSkippedCount > 0) {
                 $message .= " Atenção: {$conflictedOrSkippedCount} datas foram puladas devido a conflitos.";
             }
        }

        if ($sinal > 0) {
            $message .= " Sinal de R$ " . number_format($sinal, 2, ',', '.') . " registrado.";
        }
        if ($canceledCount > 0) {
            $message .= " {$canceledCount} outra(s) pré-reserva(s) no mesmo horário foi/foram cancelada(s) automaticamente.";
        }

        Log::info("Reserva ID: {$reserva->id} (Recorrente: " . ($isRecurrent ? 'Sim' : 'Não') . ") confirmada por Gestor ID: {$managerId}. Sinal: R$ {$sinal}, Canceladas: {$canceledCount}");

        // Resposta compatível com AJAX e Blade
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message], 200);
        }

        return redirect()->back()->with('success', $message);

    } catch (\Exception $e) {
        DB::rollBack();
        Log::error("Erro ao confirmar reserva ID: {$reserva->id}: " . $e->getMessage());

        $errorMessage = 'Erro interno ao confirmar reserva: ' . $e->getMessage();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => false, 'message' => $errorMessage], 500);
        }
        return redirect()->back()->with('error', $errorMessage);
    }
    }

    /**
     * Rejeita uma reserva pendente.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function rejeitarReserva(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if ($reserva->status !== Reserva::STATUS_PENDENTE) {
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'A reserva não está pendente.'], 400);
            }
            return redirect()->back()->with('error', 'A reserva não está mais pendente.');
        }

        // 2. Validação do Motivo (Opcional)
        $validated = $request->validate([
            'rejection_reason' => 'nullable|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_REJEITADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = $validated['rejection_reason'] ?? 'Rejeitada pelo gestor (motivo não especificado).';
            $reserva->save();

            // 3. Recria o slot fixo de disponibilidade (verde) para liberar a agenda
            // Verifica se o controller injetado existe antes de chamar
            if (isset($this->reservaController)) {
                $this->reservaController->recreateFixedSlot($reserva);
            } else {
                // Fallback se não estiver injetado (instancia manualmente ou usa log)
                Log::warning("ReservaController não injetado em AdminController. Slot fixo não recriado automaticamente para reserva {$reserva->id}.");
            }

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} rejeitada pelo gestor ID: " . Auth::id());

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => 'Reserva rejeitada com sucesso! O horário foi liberado.'], 200);
            }
            return redirect()->back()->with('success', 'Reserva rejeitada e horário liberado com sucesso!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao rejeitar reserva ID: {$reserva->id}.", ['exception' => $e]);

            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => false, 'message' => 'Erro interno ao rejeitar a reserva.'], 500);
            }
            return redirect()->back()->with('error', 'Erro interno ao rejeitar a reserva.');
        }
    }


    /**
     * ✅ NOVO: Reativa uma reserva cancelada ou rejeitada para o status CONFIRMADA.
     * @param Request $request
     * @param Reserva $reserva A reserva cancelada/rejeitada a ser reativada.
     */
    public function reativar(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if (!in_array($reserva->status, [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])) {
            return response()->json(['success' => false, 'message' => 'A reserva deve estar cancelada ou rejeitada para ser reativada.'], 400);
        }

        // 2. Checa por sobreposição (evita reativar se o slot estiver ocupado por outra reserva ativa)
        // Usamos o helper checkOverlap com 'true' para verificar apenas reservas de clientes ativas
        if ($this->reservaController->checkOverlap($reserva->date, $reserva->start_time, $reserva->end_time, true, $reserva->id)) {
             return response()->json(['success' => false, 'message' => 'O horário está ocupado por outra reserva ativa (confirmada ou pendente). Não é possível reativar.'], 400);
        }

        DB::beginTransaction();
        try {
            // 3. Atualiza a Reserva
            $reserva->status = Reserva::STATUS_CONFIRMADA;
            $reserva->manager_id = Auth::id(); // Atualiza quem a reativou
            // Limpa o motivo de cancelamento/rejeição
            $reserva->cancellation_reason = null;
            $reserva->save();

            // 4. 🛑 CONSUMIR O SLOT FIXO (remover do calendário público)
            // Se o slot fixo para este horário existir, ele deve ser excluído, pois a reserva foi reativada.
            $this->reservaController->consumeFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} reativada (de volta para CONFIRMADA) por Gestor ID: " . Auth::id());

            return response()->json(['success' => true, 'message' => 'Reserva reativada com sucesso para o status Confirmada! O slot fixo foi consumido.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao reativar reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao reativar a reserva: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Atualiza o preço de uma reserva específica via requisição AJAX (PATCH).
     *
     * Esta ação é usada para alterar o preço de um slot (livre ou reservado)
     * por motivo de feriado, promoção ou desconto, mantendo o histórico de
     * auditoria através da justificativa.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Reserva $reserva
     * @return \Illuminate\Http\JsonResponse
     */
    public function updatePrice(Request $request, Reserva $reserva)
    {
        // 1. Validação dos dados
        $validated = $request->validate([
            'new_price' => 'required|numeric|min:0',
            'justification' => 'required|string|min:5',
        ], [
            'new_price.required' => 'O novo preço é obrigatório.',
            'new_price.numeric' => 'O preço deve ser um valor numérico.',
            'new_price.min' => 'O preço não pode ser negativo.',
            'justification.required' => 'A justificativa para alteração de preço é obrigatória.',
            'justification.min' => 'A justificativa deve ter pelo menos 5 caracteres.',
        ]);

        try {
            // 2. Garante que a reserva existe e está no estado correto para alteração de preço
            // Embora o Laravel já faça o Model Binding, é bom verificar o estado.

            // 3. Verifica se o preço realmente mudou
            $oldPrice = $reserva->price;
            $newPrice = $validated['new_price'];
            $justification = $validated['justification'];

            if ((float)$oldPrice == (float)$newPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'O preço não foi alterado. O valor novo é igual ao valor antigo.',
                ], 400);
            }

            // 4. Atualiza o preço na reserva
            $reserva->price = $newPrice;
            $reserva->save();

            // 5. Opcional: Registrar a auditoria da mudança de preço, incluindo a justificativa
            // Você precisará de um sistema de logs ou de eventos/model observers para isto.
            Log::info("Preço da Reserva ID {$reserva->id} alterado de R$ {$oldPrice} para R$ {$newPrice} por " . auth()->user()->name . ". Justificativa: {$justification}");

            return response()->json([
                'success' => true,
                'message' => "Preço atualizado para R$ " . number_format($newPrice, 2, ',', '.') . " com sucesso. A tela será recarregada.",
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Se a validação falhar, o Laravel cuida do erro 422 automaticamente no AJAX.
            // Apenas para fins de robustez, o catch genérico é bom.
            return response()->json([
                'success' => false,
                'message' => 'Erro de validação: ' . $e->getMessage(),
                'errors' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Erro geral do servidor
            return response()->json([
                'success' => false,
                'message' => 'Erro ao processar a alteração de preço: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Cria uma nova reserva recorrente para um cliente.
     * Rota: admin.reservas.make_recurrent
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function makeRecurrent(Request $request)
    {
        // Limite máximo de 6 meses (26 semanas) a partir da data de início da série
        // Usamos a data de hoje como âncora para o limite.
        $maxDate = Carbon::today()->addMonths(6)->toDateString();

        // 1. Validação CRÍTICA: Enforça o limite de 6 meses na data final.
        $validated = $request->validate([
            'reserva_id' => 'required|exists:reservas,id',
            'start_date' => 'required|date|after_or_equal:today',
            // CRÍTICO: Limita a data final para 6 meses no futuro
            'end_date' => 'required|date|before_or_equal:' . $maxDate,
            'fixed_price' => 'required|numeric|min:0',
        ], [
            // Mensagem de erro customizada para o limite
            'end_date.before_or_equal' => "A série recorrente não pode exceder 6 meses (data máxima: {$maxDate}). Por favor, escolha uma data final anterior.",
        ]);

        try {
            // 2. Delega a criação da série de reservas para o ReservaController
            // (O ReservaController deve ter o método processRecurrentCreation que contém a lógica de loop)
            $result = $this->reservaController->processRecurrentCreation(
                $validated['reserva_id'],
                $validated['start_date'],
                $validated['end_date'],
                $validated['fixed_price']
            );

            // 3. Retorno de sucesso (usando a mensagem do helper)
            return response()->json([
                'success' => true,
                'message' => $result['message'] ?? 'Série recorrente criada com sucesso (limitada a 6 meses).',
            ]);

        } catch (ValidationException $e) {
             // 4. Exceções de Validação são relançadas para serem tratadas pelo handler do Laravel (ex: erro 422)
            throw $e;

        } catch (\Exception $e) {
            Log::error("Erro ao criar série recorrente (AdminController::makeRecurrent): " . $e->getMessage(), ['request' => $request->all()]);

            // 5. Tratamento de erro geral
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao criar série recorrente. Verifique as datas e o log: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Cancela uma reserva PONTUAL confirmada (PATCH /admin/reservas/{reserva}/cancelar).
     * @param Reserva $reserva A reserva confirmada PONTUAL a ser cancelada.
     */
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        if ($reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Use as rotas de cancelamento de série para reservas recorrentes.'], 400);
        }
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva não está confirmada.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor] ' . $validated['cancellation_reason'];
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            // 🛑 CRÍTICO: Delega para o helper correto no ReservaController
            $this->reservaController->recreateFixedSlot($reserva);

            // 2. Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva cancelada com sucesso! O horário foi liberado.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a reserva.'], 500);
        }
    }


    /**
     * Cancela UMA reserva de uma série recorrente (PATCH /admin/reservas/{reserva}/cancelar-pontual).
     * @param Reserva $reserva A reserva específica na série a ser cancelada.
     */
    public function cancelarReservaRecorrente(Request $request, Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva não é recorrente. Use a rota de cancelamento pontual.'], 400);
        }
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva não está confirmada.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        DB::beginTransaction();
        try {
            // Se for o mestre, devemos parar. Mas o mestre é tratado no DELETE.
            // Aqui, é um slot pontual de uma série.
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor - Pontual Recorrência] ' . $validated['cancellation_reason'];
            $reserva->save();

            // 1. Recria o slot fixo de disponibilidade (verde)
            // ✅ CRÍTICO: Delega para o helper correto no ReservaController. Isso resolve o problema de slot sumir.
            $this->reservaController->recreateFixedSlot($reserva);

            // 2. Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva RECORRENTE PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Reserva recorrente pontual cancelada com sucesso! O horário foi liberado.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva RECORRENTE PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a reserva pontual: ' . $e->getMessage()], 500);
        }
    }


    /**
     * Cancela TODAS as reservas futuras de uma série recorrente (DELETE /admin/reservas/{reserva}/cancelar-serie).
     * @param Reserva $reserva Qualquer reserva pertencente à série.
     */
    public function cancelarSerieRecorrente(Request $request, Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva não pertence a uma série recorrente.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
        ]);

        // Determina o ID mestre da série
        $masterId = $reserva->recurrent_series_id ?? $reserva->id;
        $today = Carbon::today()->toDateString();
        $cancellationReason = '[Gestor - Série Recorrente] ' . $validated['cancellation_reason'];
        $managerId = Auth::id();

        DB::beginTransaction();
        try {
            // Busca todas as reservas da série (incluindo a mestra) que estão no futuro
            $seriesReservas = Reserva::where(function ($query) use ($masterId) {
                $query->where('recurrent_series_id', $masterId)
                    ->orWhere('id', $masterId);
            })
                ->where('is_fixed', false)
                ->whereDate('date', '>=', $today)
                ->where('status', Reserva::STATUS_CONFIRMADA)
                ->get();

            $cancelledCount = 0;

            foreach ($seriesReservas as $slot) {
                $slot->status = Reserva::STATUS_CANCELADA;
                $slot->manager_id = $managerId;
                $slot->cancellation_reason = $cancellationReason;
                $slot->save();

                // 🛑 CRÍTICO: Recria o slot fixo para cada item cancelado da série.
                $this->reservaController->recreateFixedSlot($slot);

                // 2. Mantemos o registro para auditoria.

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente MASTER ID: {$masterId} cancelada pelo gestor ID: " . Auth::id() . ". Total de {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "Toda a série recorrente futura (total de {$cancelledCount} slots) foi cancelada com sucesso! Os horários foram liberados."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente ID: {$masterId}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a série recorrente.'], 500);
        }
    }


    /**
     * Exclui permanentemente uma reserva (Admin).
     */
    public function destroyReserva(Reserva $reserva)
    {
        DB::beginTransaction();
        try {
            // Se a reserva era ativa (confirmada/pendente) antes da exclusão
            if ($reserva->status === Reserva::STATUS_CONFIRMADA || $reserva->status === Reserva::STATUS_PENDENTE) {
                // Se for uma reserva de cliente, recria o slot
                if (!$reserva->is_fixed) {
                    $this->reservaController->recreateFixedSlot($reserva);
                }
            }

            // CRÍTICO: Aqui mantemos o delete, pois o propósito deste método é a exclusão PERMANENTE.
            $reserva->delete();

            DB::commit();
            Log::warning("Reserva ID: {$reserva->id} excluída permanentemente pelo gestor ID: " . Auth::id()); // 🐛 ADICIONADO LOG
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva excluída permanentemente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir reserva ID: {$reserva->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir reserva: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: GERENCIAMENTO DE USUÁRIOS
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de todos os usuários, com opção de filtro por função (role) e pesquisa.
     *
     * @param \Illuminate\Http\Request $request
     */
    public function indexUsers(Request $request)
    {
        // 1. Obtém o filtro de função e a busca da query string
        $roleFilter = $request->query('role_filter');
        $search = $request->query('search'); // ✅ NOVO

        $query = User::query();

        // 2. Aplica o filtro de função.
        if ($roleFilter) {
            if ($roleFilter === 'gestor') {
                // CORREÇÃO: Inclui 'admin' e 'gestor'
                $query->whereIn('role', ['gestor', 'admin']);
            } elseif ($roleFilter === 'cliente') {
                $query->where('role', 'cliente');
            }
        }

        // 3. Aplica o filtro de pesquisa (Search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $search . '%');
            });
        }

        // 4. Obtém os usuários, ordenando primeiro por Função (Gestor/Admin = 0, Cliente = 1), e depois por Nome.
        // 🛑 NOVO: Aplica a ordenação por função prioritária (Admin/Gestor = 0)
        $users = $query
            ->orderByRaw("CASE WHEN role IN ('admin', 'gestor') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20);

        // 5. Passa todas as variáveis necessárias para a View
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => 'Gerenciamento de Usuários',
            'roleFilter' => $roleFilter,
            'search' => $search, // ✅ NOVO
        ]);
    }

    /**
     * Exibe o formulário de criação de usuário.
     */
    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Salva um novo usuário.
     */
    public function storeUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'whatsapp_contact' => 'nullable|string|max:20',
            'password' => 'required|string|min:8|confirmed',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
        ]);

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'whatsapp_contact' => $request->whatsapp_contact,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_admin' => in_array($request->role, ['gestor', 'admin']),
            ]);

            return redirect()->route('admin.users.index')->with('success', 'Usuário criado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao criar usuário via Admin:", ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Erro ao criar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Exibe o formulário de edição de usuário.
     */
    public function editUser(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Atualiza um usuário.
     */
    public function updateUser(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'whatsapp_contact' => 'nullable|string|max:20',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => ['required', Rule::in(['cliente', 'gestor', 'admin'])],
        ]);

        $userData = [
            'name' => $request->name,
            'email' => $request->email,
            'whatsapp_contact' => $request->whatsapp_contact,
            'role' => $request->role,
            'is_admin' => in_array($request->role, ['gestor', 'admin']),
        ];

        if ($request->filled('password')) {
            $userData['password'] = Hash::make($request->password);
        }

        try {
            $user->update($userData);

            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.users.index')->with('success', 'Usuário atualizado com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao atualizar usuário ID: {$user->id}.", ['exception' => $e]);
            return redirect()->back()->withInput()->with('error', 'Erro ao atualizar usuário: ' . $e->getMessage());
        }
    }

    /**
     * Exclui um usuário.
     * ✅ NOVO: Inclui checagem de integridade de reservas ativas.
     */
    public function destroyUser(User $user)
    {
        // 1. Impede a auto-exclusão
        if (Auth::user()->id === $user->id) {
            return redirect()->back()->with('error', 'Você não pode excluir sua própria conta.');
        }

        // 2. 🛑 CHECAGEM CRÍTICA DE RESERVAS ATIVAS (Pontuais ou Recorrentes)
        $activeReservationsExist = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false) // Apenas reservas reais de clientes, não slots de disponibilidade
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->exists(); // Usa exists() para eficiência

        if ($activeReservationsExist) {
            $errorMessage = "Impossível excluir o usuário '{$user->name}'. Ele(a) possui reservas ativas (pendentes ou confirmadas). Cancele ou rejeite todas as reservas dele(a) antes de prosseguir com a exclusão.";
            Log::warning("Exclusão de usuário ID: {$user->id} bloqueada por reservas ativas.");
            return redirect()->back()->with('error', $errorMessage);
        }
        // ----------------------------------------------------------------------

        try {
            // 3. Antes de excluir o usuário, zere os IDs de manager nas reservas para manter a integridade
            Reserva::where('manager_id', $user->id)->update(['manager_id' => null]);

            $user->delete();

            Log::warning("Usuário ID: {$user->id} excluído pelo gestor ID: " . Auth::id());
            return redirect()->route('admin.users.index')->with('success', 'Usuário excluído com sucesso.');
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o usuário {$user->id}.", ['exception' => $e]);
            return redirect()->back()->with('error', 'Erro ao excluir o usuário: ' . $e->getMessage());
        }
    }

    // ------------------------------------------------------------------------
    // ✅ NOVO MÓDULO: RESERVAS POR CLIENTE
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de reservas (ativas e históricas) de um cliente específico.
     *
     * @param \App\Models\User $user O cliente cujas reservas serão listadas.
     */
    public function clientReservations(User $user)
    {
        if ($user->role !== 'cliente') {
            return redirect()->route('admin.users.index')->with('error', 'Apenas clientes podem ter histórico de reservas nesta seção.');
        }

        // 1. Busca todas as reservas do cliente, excluindo slots fixos (is_fixed=true)
        $reservas = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            // 🛑 CORRIGIDO: Ordem crescente (asc) por data e hora para mostrar o histórico cronológico
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. ✅ CRÍTICO: Cálculo da Contagem Total de Slots FUTUROS/HOJE por Série (ANTES da paginação)
        // Isso garante que o botão de cancelamento de série na view mostre o total correto de slots futuros.
        $seriesFutureCounts = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            ->where('is_recurrent', true)
            // Filtra apenas status que podem ser cancelados (ativos)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            // Filtra apenas reservas futuras ou de hoje
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->select('recurrent_series_id', DB::raw('count(*) as total'))
            ->groupBy('recurrent_series_id')
            ->pluck('total', 'recurrent_series_id')
            ->toArray();


        // 3. Paginação manual do Collection (mantém a lógica da view, mas agrupa primeiro)
        $perPage = 20;
        $page = request()->get('page', 1);
        $paginatedReservas = $reservas->slice(($page - 1) * $perPage, $perPage)->values();

        // 4. Cria o Paginator
        $reservasPaginadas = new \Illuminate\Pagination\LengthAwarePaginator(
            $paginatedReservas,
            $reservas->count(),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );


        return view('admin.users.reservas', [ // View a ser criada
            'reservas' => $reservasPaginadas, // Passa o paginator
            'client' => $user,
            'pageTitle' => "Reservas de Cliente: {$user->name}",
            'seriesFutureCounts' => $seriesFutureCounts, // ✅ NOVO: Passa a contagem total
        ]);
    }

    /**
     * Cancela TODAS as reservas futuras de uma série recorrente específica (a partir do masterId).
     * Rota usada na listagem de reservas do cliente.
     * @param Request $request
     * @param int $masterId O ID da reserva mestra (recurrent_series_id).
     */
    public function cancelClientSeries(Request $request, $masterId)
    {
        $validated = $request->validate([
            'justificativa_gestor' => 'required|string|min:5|max:255', // Campo adaptado do front-end
        ]);

        // Validação adicional: garante que o ID mestre existe e pertence a uma série recorrente de cliente
        $masterReserva = Reserva::find($masterId);
        if (!$masterReserva || !$masterReserva->is_recurrent || $masterReserva->is_fixed) {
            return response()->json(['success' => false, 'message' => 'ID da série inválido ou não é uma série recorrente ativa de cliente.'], 400);
        }

        $today = Carbon::today()->toDateString();
        $cancellationReason = '[Gestor - Cliente/Série] ' . $validated['justificativa_gestor'];
        $managerId = Auth::id();

        DB::beginTransaction();
        try {
            // Busca todas as reservas da série (incluindo a mestra) que estão no futuro
            $seriesReservas = Reserva::where(function ($query) use ($masterId) {
                $query->where('recurrent_series_id', $masterId)
                    ->orWhere('id', $masterId);
            })
                ->where('is_fixed', false)
                ->whereDate('date', '>=', $today)
                // Inclui pendentes para garantir que a série inteira seja cancelada
                ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
                ->get();

            $cancelledCount = 0;

            foreach ($seriesReservas as $slot) {
                // 1. Marca como CANCELADA (status) e adiciona o motivo
                $slot->status = Reserva::STATUS_CANCELADA;
                $slot->manager_id = $managerId;
                $slot->cancellation_reason = $cancellationReason;
                $slot->save();

                // 2. Recria o slot fixo de disponibilidade (verde)
                $this->reservaController->recreateFixedSlot($slot);

                // 3. MANTÉM A RESERVA (sem o delete)

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente (Cliente: {$masterReserva->client_name}, Master ID: {$masterId}) cancelada. Total: {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "A série recorrente (ID: {$masterId}) de {$masterReserva->client_name} foi cancelada com sucesso! Total de {$cancelledCount} horários futuros liberados."], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente (Admin/Cliente) ID: {$masterId}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a série recorrente: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Exibe a lista de Reservas Rejeitadas.
     */
    public function indexReservasRejeitadas(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $reservas = Reserva::where('status', Reserva::STATUS_REJEITADA)
            ->where('is_fixed', false)
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%')
                        ->orWhere('cancellation_reason', 'like', '%' . $search . '%');
                });
            })
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.reservas.rejeitadas', [
            'reservas' => $reservas,
            'pageTitle' => 'Reservas Rejeitadas',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
