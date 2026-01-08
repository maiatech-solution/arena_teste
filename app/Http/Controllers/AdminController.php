<?php

namespace App\Http\Controllers;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;
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
        return view('admin.reservas.index-dashboard');
    }

    /**
     * Exibe a lista de Reservas Pendentes (Multiquadra).
     */
    public function indexReservas(Request $request)
    {
        // 1. Captura o ID da arena vindo do filtro da View
        $arenaId = $request->query('arena_id');

        // 2. Inicia a query buscando apenas as pré-reservas pendentes
        $query = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->where('is_fixed', false)
            ->with('arena')
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc');

        // 3. Aplica o filtro de Arena
        if ($arenaId) {
            $query->where('arena_id', $arenaId);
        }

        // 4. Pagina os resultados
        $reservas = $query->paginate(20)->appends($request->all());

        // 5. Retorna a view enviando as ARENAS para o filtro funcionar
        return view('admin.reservas.index', [
            'reservas' => $reservas,
            'pageTitle' => 'Pré-Reservas Pendentes',
            'arenas' => \App\Models\Arena::all(), // ✨ ADICIONE ESTA LINHA AQUI
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
        $isOnlyMine = $request->input('only_mine') === 'true';
        // 🏟️ NOVO: Captura a arena selecionada no filtro da view
        $arenaId = $request->input('arena_id');

        $reservas = Reserva::whereIn('status', [
            Reserva::STATUS_CONFIRMADA,
            Reserva::STATUS_CONCLUIDA,
            Reserva::STATUS_PENDENTE,
            'completed',
            'concluida'
        ])
            ->where('is_fixed', false)
            ->with('arena') // 🏟️ ESSENCIAL: Carrega os dados da quadra (nome, cor, etc.)

            // 🏟️ FILTRO MULTIQUADRA: Só filtra se uma arena for selecionada
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })

            // FILTRO DE BUSCA (Nome ou Contato)
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%');
                });
            })

            // FILTROS DE DATA
            ->when($startDate, function ($query, $startDate) {
                return $query->whereDate('date', '>=', $startDate);
            })
            ->when($endDate, function ($query, $endDate) {
                return $query->whereDate('date', '<=', $endDate);
            })

            // FILTRO "MINHAS RESERVAS"
            ->when($isOnlyMine, function ($query) {
                return $query->where('manager_id', Auth::id());
            })

            // ORDENAÇÃO
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(20)
            ->appends($request->except('page'));

        return view('admin.reservas.confirmed_index', [
            'reservas' => $reservas,
            'pageTitle' => 'Gerenciamento de Reservas (Geral)',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'isOnlyMine' => $isOnlyMine,
            'arenaId' => $arenaId, // 🏟️ Envia o ID de volta para manter o select selecionado
            'arenas' => \App\Models\Arena::all(), // 🏟️ Envia a lista para o select de filtro
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
        // 🏟️ NOVO: Filtro de Arena
        $arenaId = $request->input('arena_id');

        // 1. Inicia a query com Eager Loading da Arena
        $query = Reserva::with('arena');

        // 2. Filtro de Arena (Multiquadra)
        if ($arenaId) {
            $query->where('arena_id', $arenaId);
        }

        // 3. Filtro de Status
        if ($filterStatus) {
            $query->where('status', $filterStatus);
        }

        // 4. Filtros de Data
        $query->when($startDate, function ($q, $startDate) {
            return $q->whereDate('date', '>=', $startDate);
        })
            ->when($endDate, function ($q, $endDate) {
                return $q->whereDate('date', '<=', $endDate);
            });

        // 5. Filtro de Busca
        $query->when($search, function ($q, $search) {
            return $q->where(function ($sub) use ($search) {
                $sub->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('client_contact', 'like', '%' . $search . '%');
            });
        });

        // 6. Ordenação e Paginação
        $reservas = $query->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(20)
            ->appends($request->all()); // 🎯 appends($request->all()) mantém todos os filtros na paginação

        // 7. Retorna a view com as Arenas para o Select
        return view('admin.reservas.todas', [
            'reservas' => $reservas,
            'pageTitle' => 'Todas as Reservas (Inventário e Clientes)',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'filterStatus' => $filterStatus,
            'isOnlyMine' => $isOnlyMine,
            'arenaId' => $arenaId, // Passa de volta para a view
            'arenas' => \App\Models\Arena::all(), // 🏟️ Envia as arenas para o select
        ]);
    }

    /**
     * Exibe o formulário para criação manual de reserva.
     */
    public function createReserva()
    {
        $users = User::where('role', 'cliente')->get();
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
     * ✅ CORRIGIDO: Cria uma nova reserva manual (Admin) - DELEGADO.
     * Delega a lógica de criação complexa (consumir slot, criar cliente, transação) para ReservaController.
     */
    public function storeReserva(Request $request)
    {
        $validated = $request->validate([
            'arena_id' => 'required|exists:arenas,id', // 🏟️ ADICIONADO: Obrigatório escolher a quadra
            'user_id' => 'nullable|exists:users,id',
            'date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'price' => 'required|numeric|min:0',
            'signal_value' => 'nullable|numeric|min:0',
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'notes' => 'nullable|string',
            'payment_method' => 'required|string',
            'is_recurrent' => 'nullable|boolean',
        ]);

        $clientContact = $validated['client_contact'];

        DB::beginTransaction();
        try {
            // 1. Encontra/Cria o cliente
            $clientUser = $this->reservaController->findOrCreateClient([
                'name' => $validated['client_name'],
                'whatsapp_contact' => $clientContact,
                'email' => null,
            ]);

            // 2. Normalização dos horários
            $startTimeNormalized = Carbon::createFromFormat('H:i', $validated['start_time'])->format('H:i:s');
            $endTimeNormalized = Carbon::createFromFormat('H:i', $validated['end_time'])->format('H:i:s');

            // 3. Busca slot fixo filtrando por ARENA_ID (Crucial para integridade) 🏟️
            $fixedSlot = Reserva::where('is_fixed', true)
                ->where('arena_id', $validated['arena_id']) // 🎯 FILTRO ADICIONADO
                ->where('date', $validated['date'])
                ->where('start_time', $startTimeNormalized)
                ->where('end_time', $endTimeNormalized)
                ->where('status', Reserva::STATUS_FREE)
                ->first();

            $fixedSlotId = $fixedSlot ? $fixedSlot->id : null;

            // 4. DELEGA A CRIAÇÃO FINAL ao ReservaController
            // O $validated já contém o arena_id agora, então o helper salvará corretamente.
            $newReserva = $this->reservaController->createConfirmedReserva($validated, $clientUser, $fixedSlotId);

            DB::commit();
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva criada com sucesso na quadra selecionada!');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar reserva manual.", ['exception' => $e, 'data' => $validated]);
            return redirect()->back()->withInput()->with('error', 'Erro interno ao criar reserva: ' . $e->getMessage());
        }
    }


    // ------------------------------------------------------------------------
    // MÓDULO: AÇÕES DE STATUS E CANCELAMENTO (DELEGADOS)
    // ------------------------------------------------------------------------

    /**
     * Confirma uma reserva pendente e registra o sinal financeiro. (DELEGADO)
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        // 🛑 DELEGAÇÃO COMPLETA
        return $this->reservaController->confirmar($request, $reserva);
    }

    /**
     * Rejeita uma reserva pendente. (DELEGADO)
     */
    public function rejeitarReserva(Request $request, Reserva $reserva)
    {
        // 🛑 DELEGAÇÃO COMPLETA
        return $this->reservaController->rejeitar($request, $reserva);
    }

    /**
     * ✅ CORRIGIDO: Registra a falta do cliente (No-Show) - DELEGADO.
     * Delega a manipulação de status e transações financeiras.
     */
    public function registerNoShow(Request $request, Reserva $reserva)
    {
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva deve estar confirmada para ser marcada como falta.'], 400);
        }

        $validated = $request->validate([
            'no_show_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean',
            'paid_amount' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // 🛑 DELEGA A LÓGICA CENTRALIZADA
            $result = $this->reservaController->finalizeStatus(
                $reserva,
                Reserva::STATUS_NO_SHOW,
                '[Gestor] ' . $validated['no_show_reason'],
                $validated['should_refund'],
                (float) $validated['paid_amount']
            );

            DB::commit();
            $message = "Reserva marcada como Falta." . $result['message_finance'];
            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (ValidationException $e) {
            // Garante que erros de validação sejam tratados corretamente
            DB::rollBack();
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar No-Show para reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao registrar a falta. Detalhe: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * ✅ CORRIGIDO: Reativa uma reserva cancelada ou rejeitada para o status CONFIRMADA.
     */
    public function reativar(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if (!in_array($reserva->status, [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])) {
            return response()->json(['success' => false, 'message' => 'A reserva deve estar cancelada ou rejeitada para ser reativada.'], 400);
        }

        // 2. Checa por sobreposição (evita reativar se o slot estiver ocupado por outra reserva ativa)
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
            $this->reservaController->consumeFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} reativada (de volta para CONFIRMADA) por Gestor ID: " . Auth::id());

            return response()->json(['success' => true, 'message' => 'Reserva reativada com sucesso para o status Confirmada! O slot fixo foi consumido.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao reativar reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao reativar a reserva. Detalhe: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Atualiza o preço de uma reserva específica via requisição AJAX (PATCH).
     */
    public function updatePrice(Request $request, Reserva $reserva)
    {
        // 1. Validação dos dados
        $validated = $request->validate([
            'new_price' => 'required|numeric|min:0',
            'justification' => 'required|string|min:5',
        ], [
            'new_price.required' => 'O novo preço é obrigatório.',
            'justification.required' => 'A justificativa para alteração de preço é obrigatória.',
        ]);

        try {
            // 2. Verifica se o preço realmente mudou
            $oldPrice = $reserva->price;
            $newPrice = $validated['new_price'];
            $justification = $validated['justification'];

            if ((float)$oldPrice == (float)$newPrice) {
                return response()->json([
                    'success' => false,
                    'message' => 'O preço não foi alterado. O valor novo é igual ao valor antigo.',
                ], 400);
            }

            // 3. Atualiza o preço na reserva
            $reserva->price = $newPrice;
            $reserva->save();

            // 4. Opcional: Registrar a auditoria da mudança de preço
            Log::info("Preço da Reserva ID {$reserva->id} alterado de R$ {$oldPrice} para R$ {$newPrice} por " . auth()->user()->name . ". Justificativa: {$justification}");

            return response()->json([
                'success' => true,
                'message' => "Preço atualizado para R$ " . number_format($newPrice, 2, ',', '.') . " com sucesso. A tela será recarregada.",
            ]);
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
     */
    public function makeRecurrent(Request $request)
    {
        // Limite máximo de 6 meses (26 semanas) a partir da data de início da série
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
            // 2. Delega a criação da série de reservas para o ReservaController (Assumindo que este método existe lá)
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
     * ✅ CORRIGIDO: Cancela uma reserva PONTUAL confirmada - DELEGADO.
     * Delega a manipulação de status e transações financeiras.
     */
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        if ($reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Use as rotas de cancelamento de série para reservas recorrentes.'], 400);
        }

        // 🚩 AJUSTE: Permite cancelar tanto as Confirmadas quanto as já Pagas (Completed)
        $statusPermitidos = [
            Reserva::STATUS_CONFIRMADA,
            Reserva::STATUS_CONCLUIDA, // Caso sua model tenha essa constante
            'completed',
            'concluida'
        ];

        if (!in_array($reserva->status, $statusPermitidos)) {
            return response()->json(['success' => false, 'message' => 'A reserva não está em um status que permite cancelamento.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            // A lógica delegada ao reservaController já sabe lidar com o estorno
            // se o should_refund for true e o paid_amount_ref for > 0
            $result = $this->reservaController->finalizeStatus(
                $reserva,
                Reserva::STATUS_CANCELADA,
                '[Gestor] ' . $validated['cancellation_reason'],
                $validated['should_refund'],
                (float) $validated['paid_amount_ref']
            );

            DB::commit();
            $message = "Reserva cancelada com sucesso! O horário foi liberado." . $result['message_finance'];
            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao cancelar a reserva: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * ✅ CORRIGIDO: Cancela UMA reserva de uma série recorrente (PATCH /admin/reservas/{reserva}/cancelar-pontual).
     * Delega a manipulação de status e transações financeiras.
     */
    public function cancelarReservaRecorrente(Request $request, Reserva $reserva)
    {
        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'A reserva não é recorrente. Use a rota de cancelamento pontual.'], 400);
        }

        // 🚩 AJUSTE: Aceita reservas Confirmadas ou Concluídas (Pagas)
        $statusPermitidos = [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_CONCLUIDA, 'completed', 'concluida'];

        if (!in_array($reserva->status, $statusPermitidos)) {
            return response()->json(['success' => false, 'message' => 'A reserva não está em um status cancelável.'], 400);
        }

        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0', // 🚩 O Controller espera este nome
        ]);

        DB::beginTransaction();
        try {
            // O finalizeStatus cuidará de criar a saída no caixa se should_refund for true
            $result = $this->reservaController->finalizeStatus(
                $reserva,
                Reserva::STATUS_CANCELADA,
                '[Gestor - Pontual Recorrência] ' . $validated['cancellation_reason'],
                $validated['should_refund'],
                (float) $validated['paid_amount_ref']
            );

            DB::commit();
            $message = "Reserva recorrente pontual cancelada com sucesso! O horário foi liberado." . ($result['message_finance'] ?? '');
            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva RECORRENTE PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao cancelar a reserva pontual: ' . $e->getMessage()
            ], 500);
        }
    }


    /**
     * ✅ CORRIGIDO: Cancela TODAS as reservas futuras de uma série recorrente (DELETE /admin/reservas/{reserva}/cancelar-serie).
     * Esta função encerra o contrato mensalista e processa o estorno do valor pago hoje, se solicitado.
     */
    public function cancelarSerieRecorrente(Request $request, Reserva $reserva)
    {
        // 1. Validação de Integridade: Verifica se realmente é uma reserva de mensalista
        if (!$reserva->is_recurrent) {
            return response()->json([
                'success' => false,
                'message' => 'A reserva não pertence a uma série recorrente. Use a rota de cancelamento pontual.'
            ], 400);
        }

        // 🚩 2. AJUSTE DE STATUS: Permite cancelar mesmo que o horário de hoje já esteja pago (Caso do Amaral)
        $statusPermitidos = [
            Reserva::STATUS_CONFIRMADA,
            Reserva::STATUS_CONCLUIDA,
            'completed',
            'concluida'
        ];

        if (!in_array($reserva->status, $statusPermitidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Não é possível cancelar a série: o status atual da reserva (' . $reserva->status . ') não permite esta ação.'
            ], 400);
        }

        // 3. Validação dos dados vindos do Modal
        $validated = $request->validate([
            'cancellation_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0', // Valor pago hoje para possível estorno
        ]);

        // 4. Identifica o ID mestre da série (se a reserva atual não for a mestre, busca a original)
        $masterId = $reserva->recurrent_series_id ?? $reserva->id;

        DB::beginTransaction();
        try {
            // 🛑 5. DELEGAÇÃO: O método cancelSeries no ReservaController deve:
            // - Percorrer todos os horários futuros com este masterId.
            // - Trocar o status para 'cancelled'.
            // - Recriar os slots 'fixed' (verdes) para liberar a agenda.
            // - Se should_refund for true, gerar uma transação de SAÍDA no caixa de hoje.
            $result = $this->reservaController->cancelSeries(
                $masterId,
                '[Gestor - Cancelamento Série] ' . $validated['cancellation_reason'],
                $validated['should_refund'],
                (float) $validated['paid_amount_ref']
            );

            DB::commit();

            $message = "Toda a série recorrente futura (total de {$result['cancelled_count']} slots) foi cancelada com sucesso! Os horários foram liberados." . ($result['message_finance'] ?? '');

            return response()->json([
                'success' => true,
                'message' => $message
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente ID: {$masterId}.", ['exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Erro interno ao cancelar a série recorrente: ' . $e->getMessage()
            ], 500);
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
            Log::warning("Reserva ID: {$reserva->id} excluída permanentemente pelo gestor ID: " . auth()->user()->id);
            return redirect()->route('admin.reservas.confirmadas')->with('success', 'Reserva excluída permanentemente.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao excluir reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro ao excluir reserva: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------------
    // MÓDULO: GERENCIAMENTO DE USUÁRIOS
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de todos os usuários.
     */
    public function indexUsers(Request $request)
    {
        // 1. Obtém o filtro de função e a busca da query string
        $roleFilter = $request->query('role_filter');
        $search = $request->query('search');

        $query = User::query();

        // 2. Aplica o filtro de função.
        if ($roleFilter) {
            if ($roleFilter === 'gestor') {
                $query->whereIn('role', ['gestor', 'admin']);
            } elseif ($roleFilter === 'cliente') {
                $query->where('role', 'cliente');
            }
        }

        // 3. Aplica o filtro de pesquisa (Search)
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        // 4. Obtém os usuários, ordenando primeiro por Função, e depois por Nome.
        $users = $query
            ->orderByRaw("CASE WHEN role IN ('admin', 'gestor') THEN 0 ELSE 1 END")
            ->orderBy('name')
            ->paginate(20);

        // 5. Passa todas as variáveis necessárias para a View
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => 'Gerenciamento de Usuários',
            'roleFilter' => $roleFilter,
            'search' => $search,
        ]);
    }

    /**
     * Exibe o formulário de criação de usuário.
     */
    public function createUser()
    {
        return view('admin.users.create', [
            // ...
        ]);
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
     */
    public function destroyUser(User $user)
    {
        // 1. Impede a auto-exclusão
        if (Auth::user()->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Você não pode excluir sua própria conta.'], 403);
        }

        // 2. CHECAGEM CRÍTICA DE RESERVAS ATIVAS
        $activeReservationsExist = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false) // Apenas reservas reais de clientes, não slots de disponibilidade
            ->whereIn('status', [Reserva::STATUS_PENDENTE, Reserva::STATUS_CONFIRMADA])
            ->exists();

        if ($activeReservationsExist) {
            $errorMessage = "Impossível excluir o usuário '{$user->name}'. Ele(a) possui reservas ativas (pendentes ou confirmadas). Cancele ou rejeite todas as reservas dele(a) antes de prosseguir com a exclusão.";
            Log::warning("Exclusão de usuário ID: {$user->id} bloqueada por reservas ativas.");
            return response()->json(['success' => false, 'message' => $errorMessage], 400);
        }
        // ----------------------------------------------------------------------

        try {
            // 3. Antes de excluir o usuário, zere os IDs de manager nas reservas para manter a integridade
            Reserva::where('manager_id', $user->id)->update(['manager_id' => null]);

            $user->delete();

            Log::warning("Usuário ID: {$user->id} excluído pelo gestor ID: " . Auth::id());
            return response()->json(['success' => true, 'message' => 'Usuário excluído com sucesso.'], 200);
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o usuário {$user->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro ao excluir o usuário: ' . $e->getMessage()], 500);
        }
    }

    // ------------------------------------------------------------------------
    // ✅ NOVO MÓDULO: RESERVAS POR CLIENTE
    // ------------------------------------------------------------------------

    /**
     * Exibe a lista de reservas (ativas e históricas) de um cliente específico.
     */
    public function clientReservations(User $user)
    {
        if ($user->role !== 'cliente') {
            return response()->json(['success' => false, 'message' => 'Apenas clientes podem ter histórico de reservas nesta seção.'], 400);
        }

        // 1. Busca todas as reservas do cliente, excluindo slots fixos (is_fixed=true)
        $reservas = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        // 2. ✅ CRÍTICO: Cálculo da Contagem Total de Slots FUTUROS/HOJE por Série
        $seriesFutureCounts = Reserva::where('user_id', $user->id)
            ->where('is_fixed', false)
            ->where('is_recurrent', true)
            // Filtra apenas status que podem ser cancelados (ativos)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
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

        // NOTA: Para cancelamento de série por cliente, assumimos que o pagamento do sinal já foi tratado
        // ou retido, pois o Admin deve usar a rota `cancelarSerieRecorrente` com a lógica financeira.
        // Este método aqui faz apenas a atualização de status para a view de histórico do cliente.

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

                $cancelledCount++;
            }

            DB::commit();
            Log::info("Série Recorrente (Cliente: {$masterReserva->client_name}, Master ID: {$masterId}) cancelada. Total: {$cancelledCount} slots liberados.");

            return response()->json(['success' => true, 'message' => "A série recorrente (ID: {$masterReserva->id}) de {$masterReserva->client_name} foi cancelada com sucesso! Total de {$cancelledCount} horários futuros liberados."], 200);
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
        // 🏟️ NOVO: Filtro de Arena
        $arenaId = $request->input('arena_id');

        $reservas = Reserva::where('status', Reserva::STATUS_REJEITADA)
            ->where('is_fixed', false)
            ->with('arena') // 🏟️ Eager loading para mostrar o nome da quadra na lista

            // 🏟️ FILTRO MULTIQUADRA
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })

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
            ->appends($request->all()); // 🎯 Mantém todos os filtros na paginação

        return view('admin.reservas.rejeitadas', [
            'reservas' => $reservas,
            'pageTitle' => 'Reservas Rejeitadas',
            'search' => $search,
            'startDate' => $startDate,
            'endDate' => $endDate,
            'arenaId' => $arenaId, // Passa de volta para manter o select preenchido
            'arenas' => \App\Models\Arena::all(), // 🏟️ Envia as arenas para o select
        ]);
    }

    // ------------------------------------------------------------------------
    // ✅ MÓDULO: RELATÓRIO DE PAGAMENTOS/CAIXA (Backend da sua view)
    // ------------------------------------------------------------------------

    /**
     * Calcula o saldo total de todas as transações financeiras.
     * @return float
     */
    private function calculateTotalBalance()
    {
        // Esta consulta deve somar TODOS os valores na coluna 'amount'.
        $total = FinancialTransaction::sum('amount');
        Log::info("DEBUG FINANCEIRO: Saldo total do caixa calculado: R$ " . number_format($total, 2, ',', '.'));
        return (float) $total;
    }

    /**
     * Exibe a lista de transações financeiras e o saldo.
     */
    public function indexFinancialDashboard(Request $request)
    {
        // 1. Definição da data e da ARENA (Filtro essencial) 🏟️
        $selectedDate = $request->input('date', Carbon::today()->toDateString());
        $date = Carbon::parse($selectedDate)->toDateString();
        $arenaId = $request->input('arena_id'); // 🎯 NOVO: Captura o filtro de quadra
        $search = $request->input('search');
        $reservaId = $request->input('reserva_id');

        // 2. Consulta de Reservas com Filtro de Arena e Eager Loading
        $reservasQuery = Reserva::where('is_fixed', false)
            ->with('arena') // 🏟️ Para exibir o nome da quadra na tabela
            ->whereDate('date', $date)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE, Reserva::STATUS_CONCLUIDA, Reserva::STATUS_CANCELADA, Reserva::STATUS_NO_SHOW])
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId); // Filtra por quadra
            })
            ->when($reservaId, function ($query, $reservaId) {
                return $query->where('id', $reservaId);
            })
            ->when($search, function ($query, $search) {
                return $query->where(function ($q) use ($search) {
                    $q->where('client_name', 'like', '%' . $search . '%')
                        ->orWhere('client_contact', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('start_time')
            ->get();

        // 3. Cálculos Financeiros Segmentados 💰

        // 3.1 Total Recebido (Filtrado por Arena se houver)
        $totalReceived = FinancialTransaction::whereDate('paid_at', $date)
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->sum('amount');

        // Transações para auditoria (Filtradas)
        $financialTransactions = FinancialTransaction::whereDate('paid_at', $date)
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->orderBy('paid_at', 'asc')
            ->get();

        // 3.2 Total Esperado e Pendente (Baseado na query filtrada de reservas)
        // Usamos as reservas ativas da quadra selecionada
        $activeReservas = Reserva::where('is_fixed', false)
            ->whereDate('date', $date)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->get();

        $totalExpected = $activeReservas->sum('price');
        $totalPaidBySignals = $activeReservas->sum('total_paid');
        $totalPending = $totalExpected - $totalPaidBySignals;

        // 3.3 No-Show (Filtrado)
        $noShowCount = Reserva::whereDate('date', $date)
            ->where('is_fixed', false)
            ->where('status', Reserva::STATUS_NO_SHOW)
            ->when($arenaId, function ($query, $arenaId) {
                return $query->where('arena_id', $arenaId);
            })
            ->count();

        return view('admin.financial.index', [
            'reservas' => $reservasQuery,
            'financialTransactions' => $financialTransactions,
            'selectedDate' => $selectedDate,
            'arenaId' => $arenaId, // Passa o ID para manter o select preenchido
            'arenas' => \App\Models\Arena::all(), // 🏟️ Lista de quadras para o filtro
            'highlightReservaId' => $reservaId,
            'totalReceived' => $totalReceived,
            'totalPending' => max(0, $totalPending),
            'totalExpected' => $totalExpected,
            'noShowCount' => $noShowCount,
            'pageTitle' => 'Gerenciamento de Caixa & Pagamentos',
            'search' => $search,
            'totalGlobalBalance' => $this->calculateTotalBalance(),
        ]);
    }
}
