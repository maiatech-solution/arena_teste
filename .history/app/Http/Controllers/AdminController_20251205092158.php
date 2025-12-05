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
            // ✅ NOVO: Adiciona a possibilidade de sinal/pagamento para criação manual
            'signal_value' => 'nullable|numeric|min:0',
            // FIM NOVO
            'client_name' => 'required|string|max:255',
            'client_contact' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $price = (float) $validated['price'];
        $signalValue = (float) ($validated['signal_value'] ?? 0.00);
        $totalPaid = $signalValue;

        $paymentStatus = 'pending';
        if ($signalValue > 0) {
            $paymentStatus = ($signalValue >= $price) ? 'paid' : 'partial';
        }

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
            $newReserva = Reserva::create([
                'user_id' => $validated['user_id'] ?? null,
                'date' => $validated['date'],
                'day_of_week' => Carbon::parse($validated['date'])->dayOfWeek,
                'start_time' => $startTimeNormalized,
                'end_time' => $endTimeNormalized,
                'price' => $price,
                // ✅ Adicionado Pagamento/Sinal
                'signal_value' => $signalValue,
                'total_paid' => $totalPaid,
                'payment_status' => $paymentStatus,
                // FIM Adicionado
                'client_name' => $validated['client_name'],
                'client_contact' => $validated['client_contact'],
                'notes' => $validated['notes'] ?? null,
                'status' => Reserva::STATUS_CONFIRMADA, // Reserva de cliente confirmada pelo Admin
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            // ✅ NOVO: GERA TRANSAÇÃO FINANCEIRA para o sinal
            if ($signalValue > 0) {
                FinancialTransaction::create([
                    'reserva_id' => $newReserva->id,
                    'user_id' => $newReserva->user_id,
                    'manager_id' => Auth::id(),
                    'amount' => $signalValue,
                    'type' => 'signal',
                    'payment_method' => 'manual', // Assumindo pagamento manual
                    'description' => 'Sinal/Pagamento inicial recebido na criação manual da reserva.',
                    'paid_at' => Carbon::now(),
                ]);
            }

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
     * 🛑 AGORA DELEGA a lógica complexa para o ReservaController.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function confirmarReserva(Request $request, Reserva $reserva)
    {
        // 🛑 DELEGAÇÃO COMPLETA: O AdminController apenas repassa a requisição
        // A lógica de confirmação, criação de série, consumo de slot fixo,
        // cancelamento de outras pendentes e registro de transação financeira
        // AGORA RESIDE INTEIRAMENTE em ReservaController::confirmar.
        return $this->reservaController->confirmar($request, $reserva);
    }

    /**
     * Rejeita uma reserva pendente.
     * 🛑 AGORA DELEGA a lógica de rejeição para o ReservaController.
     * @param Request $request
     * @param Reserva $reserva
     */
    public function rejeitarReserva(Request $request, Reserva $reserva)
    {
        // 🛑 DELEGAÇÃO COMPLETA: O AdminController apenas repassa a requisição.
        // A lógica de alteração de status e recriação do slot fixo reside em ReservaController::rejeitar.
        return $this->reservaController->rejeitar($request, $reserva);
    }

    /**
     * ✅ NOVO: Registra a falta do cliente (No-Show) e gerencia o estorno/retenção.
     * A falta (No-Show) é quando o cliente não comparece e NÃO INFORMA o cancelamento.
     * @param Request $request
     * @param Reserva $reserva
     * @return \Illuminate\Http\JsonResponse
     */
    public function registerNoShow(Request $request, Reserva $reserva)
    {
        // 1. Validação de Status
        if ($reserva->status !== Reserva::STATUS_CONFIRMADA) {
            return response()->json(['success' => false, 'message' => 'A reserva deve estar confirmada para ser marcada como falta.'], 400);
        }

        // 2. Validação da Requisição (motivo e decisão de estorno)
        $validated = $request->validate([
            'no_show_reason' => 'required|string|min:5|max:255',
            'should_refund' => 'required|boolean', // Se deve estornar o valor pago
            'paid_amount' => 'required|numeric|min:0', // O valor pago pelo cliente (para referência)
        ], [
            'no_show_reason.required' => 'O motivo da falta é obrigatório.',
            'no_show_reason.min' => 'O motivo da falta deve ter pelo menos 5 caracteres.',
        ]);

        // 3. Checagem de integridade (o valor pago do front deve bater com o DB)
        $amountPaid = (float) $reserva->total_paid;
        $shouldRefund = $validated['should_refund'];

        if ((float) $validated['paid_amount'] != $amountPaid) {
              Log::warning("Tentativa de No-Show ID: {$reserva->id} com valor pago inconsistente. Front: {$validated['paid_amount']}, DB: {$amountPaid}");
              // Continuamos, mas o log de aviso é importante
        }

        DB::beginTransaction();
        try {
            // 4. Atualiza a Reserva para STATUS_NO_SHOW
            $reserva->status = Reserva::STATUS_NO_SHOW;
            $reserva->manager_id = Auth::id();
            $reserva->no_show_reason = '[Gestor] ' . $validated['no_show_reason'];
            // Garante que o motivo de cancelamento não seja usado
            $reserva->cancellation_reason = null;
            $reserva->save();

            // 5. Gera Transação Financeira de Estorno ou Retenção
            if ($amountPaid > 0) {
                if ($shouldRefund) {
                    // Estornar: Cria uma transação negativa (saída do caixa)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => -$amountPaid, // Valor negativo para estorno/saída
                        'type' => 'refund_noshow',
                        'payment_method' => 'manual',
                        'description' => "Estorno do valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido à falta (No-Show).",
                        'paid_at' => Carbon::now(),
                    ]);
                    $message = "Reserva marcada como Falta. O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi estornado (saiu do caixa).";

                } else {
                    // Retenção: O valor pago já está no caixa. Criamos uma transação de ENTRADA para registrar
                    // que esse valor foi RETIDO como receita de No-Show, em vez de ser um pagamento comum.
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => $amountPaid, // Valor positivo
                        'type' => 'retention_noshow', // Tipo de transação para retenção
                        'payment_method' => 'retained_funds',
                        'description' => "Retenção do valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido à falta (No-Show).",
                        'paid_at' => Carbon::now(),
                    ]);
                    $message = "Reserva marcada como Falta. O valor pago de R$ " . number_format($amountPaid, 2, ',', '.') . " foi RETIDO no caixa.";
                }
            } else {
                $message = "Reserva marcada como Falta. Não havia valor pago a ser gerenciado.";
            }

            // 6. Recria o slot fixo de disponibilidade (verde)
            // Isso libera o horário para ser reservado por outra pessoa, se for o caso.
            $this->reservaController->recreateFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva ID: {$reserva->id} marcada como FALTA (No-Show) por Gestor ID: " . Auth::id() . ". Estorno: " . ($shouldRefund ? 'Sim' : 'Não'));

            return response()->json(['success' => true, 'message' => $message], 200);

        } catch (ValidationException $e) {
             // Garante que erros de validação sejam tratados corretamente
            DB::rollBack();
            return response()->json(['success' => false, 'errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao registrar No-Show para reserva ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao registrar a falta: ' . $e->getMessage()], 500);
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

            // 4. Opcional: Registrar a auditoria da mudança de preço, incluindo a justificativa
            Log::info("Preço da Reserva ID {$reserva->id} alterado de R$ {$oldPrice} para R$ {$newPrice} por " . auth()->user()->name . ". Justificativa: {$justification}");

            return response()->json([
                'success' => true,
                'message' => "Preço atualizado para R$ " . number_format($newPrice, 2, ',', '.') . " com sucesso. A tela será recarregada.",
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;

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
     * 🛑 NOTA: Este método está chamando um método (processRecurrentCreation) que não existe
     * no ReservaController que eu criei. No entanto, estou MANTENDO a chamada
     * para que o fluxo do seu AdminController seja preservado, caso você o implemente
     * ou o renomeie. O método principal que usei para séries é storeRecurrentReservaApi.
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
            // Se o método processRecurrentCreation não existir, isso irá falhar.
            // Para maior robustez, você pode querer unificar com o storeRecurrentReservaApi.
            // MANTENDO COMO ESTÁ para preservar o seu fluxo.
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
     * O cancelamento implica que o cliente informou o não comparecimento ANTES ou no ato,
     * e o status final é STATUS_CANCELADA.
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
            // 🛑 NOVO: Adiciona a validação para o estorno
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0', // ✅ CRÍTICO: Novo campo de valor de referência
        ]);

        $shouldRefund = $validated['should_refund'];
        // Usamos paid_amount_ref pois o cliente pode ter pago mais que o signal_value.
        $amountPaid = (float) $validated['paid_amount_ref'];

        DB::beginTransaction();
        try {
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor] ' . $validated['cancellation_reason'];
            $reserva->save();

            $messageFinance = "";
            // 1. Gera Transação Financeira: Estorno OU Retenção
            if ($amountPaid > 0) {
                if ($shouldRefund) {
                     // 1.1 Estorno: Cria uma transação negativa (saída do caixa)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => -$amountPaid, // Valor negativo para estorno/saída
                        'type' => 'refund_cancellation',
                        'payment_method' => 'manual',
                        'description' => "Estorno do sinal/valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido ao cancelamento pontual.",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi estornado.";
                } else {
                    // 1.2 Retenção: Cria uma transação POSITIVA (entrada de receita por retenção)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => $amountPaid, // Valor positivo para retenção (fica no caixa)
                        'type' => 'retention_cancellation',
                        'payment_method' => 'retained_funds',
                        'description' => "Retenção do valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido ao cancelamento pontual (No-Refund).",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi RETIDO no caixa.";
                }
            }


            // 2. Recria o slot fixo de disponibilidade (verde)
            $this->reservaController->recreateFixedSlot($reserva);

            DB::commit();
            Log::info("Reserva PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            $message = "Reserva cancelada com sucesso! O horário foi liberado." . $messageFinance;
            return response()->json(['success' => true, 'message' => $message], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar reserva PONTUAL ID: {$reserva->id}.", ['exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Erro interno ao cancelar a reserva.'], 500);
        }
    }


    /**
     * Cancela UMA reserva de uma série recorrente (PATCH /admin/reservas/{reserva}/cancelar-pontual).
     * O cancelamento pontual implica que o cliente informou o não comparecimento.
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
            // 🛑 NOVO: Adiciona a validação para o estorno
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0', // ✅ CRÍTICO: Novo campo de valor de referência
        ]);

        $shouldRefund = $validated['should_refund'];
        $amountPaid = (float) $validated['paid_amount_ref']; // Usamos paid_amount_ref para ser o valor exato pago pelo cliente.

        DB::beginTransaction();
        try {
            // Se for o mestre, devemos parar. Mas o mestre é tratado no DELETE.
            // Aqui, é um slot pontual de uma série.
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->manager_id = Auth::id();
            $reserva->cancellation_reason = '[Gestor - Pontual Recorrência] ' . $validated['cancellation_reason'];
            $reserva->save();

            $messageFinance = "";
            // 1. Gera Transação Financeira: Estorno OU Retenção
            if ($amountPaid > 0) {
                if ($shouldRefund) {
                     // 1.1 Estorno: Cria uma transação negativa (saída do caixa)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => -$amountPaid, // Valor negativo para estorno/saída
                        'type' => 'refund_cancellation_pontual_recurrent',
                        'payment_method' => 'manual',
                        'description' => "Estorno do sinal/valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido ao cancelamento pontual da recorrência.",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi estornado.";
                } else {
                    // 1.2 Retenção: Cria uma transação POSITIVA (entrada de receita por retenção)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => $amountPaid, // Valor positivo para retenção (fica no caixa)
                        'type' => 'retention_cancellation_pontual_recurrent',
                        'payment_method' => 'retained_funds',
                        'description' => "Retenção do valor pago (R$ " . number_format($amountPaid, 2, ',', '.') . ") devido ao cancelamento pontual da recorrência (No-Refund).",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O valor de R$ " . number_format($amountPaid, 2, ',', '.') . " foi RETIDO no caixa.";
                }
            }


            // 2. Recria o slot fixo de disponibilidade (verde)
            // ✅ CRÍTICO: Delega para o helper correto no ReservaController. Isso resolve o problema de slot sumir.
            $this->reservaController->recreateFixedSlot($reserva);

            // 3. Mantemos o registro para auditoria.

            DB::commit();
            Log::info("Reserva RECORRENTE PONTUAL ID: {$reserva->id} cancelada pelo gestor ID: " . Auth::id());
            $message = "Reserva recorrente pontual cancelada com sucesso! O horário foi liberado." . $messageFinance;
            return response()->json(['success' => true, 'message' => $message], 200);
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
            // 🛑 NOVO: Adiciona a validação para o estorno
            'should_refund' => 'required|boolean',
            'paid_amount_ref' => 'required|numeric|min:0', // ✅ CRÍTICO: Novo campo de valor de referência
        ]);

        $shouldRefund = $validated['should_refund'];
        // Para séries, o estorno só deve considerar o pagamento que estava na reserva (signal_value).
        $amountPaidForRefund = (float) $validated['paid_amount_ref'];


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
                // Se a reserva já passou, não cancelamos
                if (Carbon::parse($slot->date . ' ' . $slot->start_time)->isPast() && !$slot->date->isToday()) {
                    continue;
                }

                $slot->status = Reserva::STATUS_CANCELADA;
                $slot->manager_id = $managerId;
                $slot->cancellation_reason = $cancellationReason;
                $slot->save();

                // 🛑 CRÍTICO: Recria o slot fixo para cada item cancelado da série.
                $this->reservaController->recreateFixedSlot($slot);

                // 2. Mantemos o registro para auditoria.

                $cancelledCount++;
            }

            $messageFinance = "";
            // 1. Gera Transação Financeira: Estorno OU Retenção (Apenas uma transação para a série)
            if ($amountPaidForRefund > 0) {
                if ($shouldRefund) {
                    // 1.1 Estorno: Cria uma transação negativa (saída do caixa)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id, // Usa a reserva mestre ou a reserva clicada como âncora
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => -$amountPaidForRefund, // Valor negativo para estorno/saída
                        'type' => 'refund_cancellation_serie',
                        'payment_method' => 'manual',
                        'description' => "Estorno do sinal/valor pago (R$ " . number_format($amountPaidForRefund, 2, ',', '.') . ") devido ao cancelamento da série inteira.",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O sinal de R$ " . number_format($amountPaidForRefund, 2, ',', '.') . " foi estornado.";
                } else {
                    // 1.2 Retenção: Cria uma transação POSITIVA (entrada de receita por retenção)
                    FinancialTransaction::create([
                        'reserva_id' => $reserva->id,
                        'user_id' => $reserva->user_id,
                        'manager_id' => Auth::id(),
                        'amount' => $amountPaidForRefund, // Valor positivo para retenção (fica no caixa)
                        'type' => 'retention_cancellation_serie',
                        'payment_method' => 'retained_funds',
                        'description' => "Retenção do sinal/valor pago (R$ " . number_format($amountPaidForRefund, 2, ',', '.') . ") devido ao cancelamento da série (No-Refund).",
                        'paid_at' => Carbon::now(),
                    ]);
                    $messageFinance = " O sinal de R$ " . number_format($amountPaidForRefund, 2, ',', '.') . " foi RETIDO no caixa.";
                }
            }


            DB::commit();
            Log::info("Série Recorrente MASTER ID: {$masterId} cancelada pelo gestor ID: " . Auth::id() . ". Total de {$cancelledCount} slots liberados.");

            $message = "Toda a série recorrente futura (total de {$cancelledCount} slots) foi cancelada com sucesso! Os horários foram liberados." . $messageFinance;

            return response()->json(['success' => true, 'message' => $message], 200);
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
            Log::warning("Reserva ID: {$reserva->id} excluída permanentemente pelo gestor ID: " . auth()->user()->id); // 🐛 ADICIONADO LOG
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
