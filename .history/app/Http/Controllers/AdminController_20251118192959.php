<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Reserva;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use App\Events\ReservaCancelada;

class AdminController extends Controller
{
    /**
     * Retorna a lógica de checagem de sobreposição.
     * [Imagens de Diagrama de Sobreposição de Intervalos]
     */
    protected function checkOverlap(string $dateString, string $startTime, ?string $endTime, bool $isFixed, ?int $ignoreId = null): bool
    {
        // Se o end_time não foi fornecido (ex: erro no agendamento), assume 1h
        $endTime = $endTime ?: Carbon::parse($startTime)->addHour()->format('H:i:s');

        $query = Reserva::whereDate('date', $dateString)
            // Não deve haver sobreposição com reservas CONFIRMADAS ou PENDENTES (reais ou slots fixos)
            ->whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->where(function ($q) use ($startTime, $endTime) {
                // A reserva existente começa DEPOIS que a nova começa E ANTES que a nova termina
                $q->where('start_time', '>=', $startTime)
                    ->where('start_time', '<', $endTime);
            })
            ->orWhere(function ($q) use ($startTime, $endTime) {
                // A reserva existente começa ANTES que a nova comece E termina DEPOIS que a nova começa
                $q->where('start_time', '<', $startTime)
                    ->where('end_time', '>', $startTime);
            });

        // Ignora a própria reserva se estivermos editando/confirmando uma existente
        if ($ignoreId) {
            $query->where('id', '!=', $ignoreId);
        }

        return $query->exists();
    }

    /**
     * Encontra um cliente pelo contato do WhatsApp ou e-mail, ou cria um novo se não existir.
     * Este método é ideal para ser chamado por outros controllers (públicos ou admin).
     *
     * @param array $data Contém 'name', 'email', 'whatsapp_contact', 'data_nascimento'
     * @return User O objeto User encontrado ou recém-criado.
     */
    public function findOrCreateClient(array $data): User
    {
        // 1. Tenta encontrar o usuário pelo WhatsApp ou Email
        $user = User::where('whatsapp_contact', $data['whatsapp_contact'])
            ->orWhere('email', $data['email'])
            ->first();

        // Dados a serem atualizados/criados
        $updateData = [
            'name' => $data['name'],
            'email' => $data['email'],
            'whatsapp_contact' => $data['whatsapp_contact'],
            'data_nascimento' => $data['data_nascimento'] ?? null,
            'role' => 'cliente',
        ];

        if ($user) {
            // 2. Cliente Encontrado: Atualiza dados.
            $user->update($updateData);
            Log::info("Cliente existente atualizado para reserva: {$user->email}");
            return $user;

        } else {
            // 3. Cliente Não Encontrado: Cria um novo usuário
            $updateData['password'] = Hash::make(Str::random(16));
            $user = User::create($updateData);
            Log::info("Novo cliente criado automaticamente para reserva: {$user->email}");
            return $user;
        }
    }

    // =========================================================================
    // 💡 NOVO MÉTODO: CRIAÇÃO MANUAL DE RESERVA PELO ADMIN
    // Este método demonstra a utilização de findOrCreateClient para agendamento manual.
    // =========================================================================
    public function storeManualReserva(Request $request)
    {
        $validatedData = $request->validate([
            'client_name' => 'required|string|max:255',
            // Aqui, a unicidade do email e whatsapp NÃO é checada,
            // pois findOrCreateClient vai lidar com a existência.
            'client_email' => 'required|email|max:255',
            'client_contact' => 'required|string|max:20',
            'date' => 'required|date_format:Y-m-d|after_or_equal:today',
            'start_time' => 'required|date_format:H:i:s',
            'end_time' => 'nullable|date_format:H:i:s|after:start_time',
            'price' => 'nullable|numeric|min:0',
            // ... outros campos de reserva
        ]);

        DB::beginTransaction();
        try {
            // 1. ENCONTRA/CRIA o Usuário (o coração da sua lógica)
            $client = $this->findOrCreateClient([
                'name' => $validatedData['client_name'],
                'email' => $validatedData['client_email'],
                'whatsapp_contact' => $validatedData['client_contact'],
                'data_nascimento' => $request->input('data_nascimento'), // Assumindo que este campo está no form
            ]);

            $dateString = $validatedData['date'];
            $startTime = $validatedData['start_time'];
            $endTime = $validatedData['end_time'];

            // 2. Checagem de Conflito
            if ($this->checkOverlap($dateString, $startTime, $endTime, false, null)) {
                DB::rollBack();
                return back()->with('error', 'Conflito detectado: Já existe outra reserva no mesmo horário.');
            }

            // 3. Cria a Reserva e a associa ao Usuário
            $reserva = Reserva::create([
                'user_id' => $client->id,
                'date' => $dateString,
                'day_of_week' => Carbon::parse($dateString)->dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
                'price' => $validatedData['price'],
                'client_name' => $client->name,
                'client_contact' => $client->whatsapp_contact,
                'status' => Reserva::STATUS_CONFIRMADA, // Admin confirma imediatamente
                'is_fixed' => false,
                'is_recurrent' => false,
                'manager_id' => Auth::id(),
            ]);

            DB::commit();
            return redirect()->route('dashboard')->with('success', 'Reserva manual para ' . $client->name . ' criada e confirmada com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao criar reserva manual: " . $e->getMessage());
            return back()->withInput()->with('error', 'Erro inesperado ao criar a reserva: ' . $e->getMessage());
        }
    }
    // =========================================================================

    // =========================================================================
    // ✅ NOVO MÉTODO: DASHBOARD CENTRAL DE RESERVAS (Página de Botões)
    // =========================================================================
    /**
     * Exibe o dashboard principal com as opções de listagem (Confirmadas, Pendentes, Canceladas).
     */
    public function indexReservasDashboard()
    {
        // Contagem de Reservas Ativas (Confirmadas + Pendentes) e Canceladas
        $pendingCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();
        $confirmedCount = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
                                    ->where('is_fixed', false) // Apenas reservas de clientes
                                    ->whereDate('date', '>=', Carbon::today()->toDateString())
                                    ->count();
        $canceledCount = Reserva::where('status', Reserva::STATUS_CANCELADA)->count();

        return view('admin.reservas.index-dashboard', compact('pendingCount', 'confirmedCount', 'canceledCount'));
    }

    /**
     * Exibe o dashboard principal do gestor.
     */
    public function dashboard()
    {
        $reservasPendentesCount = Reserva::where('status', Reserva::STATUS_PENDENTE)->count();

        // Código de contagem de séries expirando
        try {
            // Buscando reservas que pertencem a uma série, mas a série em si está expirando
            $expiringSeriesIds = Reserva::where('is_recurrent', true)
                ->whereDate('recurrent_end_date', '<=', Carbon::now()->addDays(30))
                ->whereDate('date', '>=', Carbon::now())
                ->distinct('recurrent_series_id')
                ->pluck('recurrent_series_id')
                ->filter() // Remove nulos
                ->toArray();

            // Agora contamos as séries únicas
            $expiringSeriesCount = count($expiringSeriesIds);

            // Buscando os dados da primeira reserva de cada série que expira
            $expiringSeries = Reserva::whereIn('recurrent_series_id', $expiringSeriesIds)
                ->orWhere(function ($query) use ($expiringSeriesIds) {
                    $query->whereIn('id', $expiringSeriesIds) // Pega o mestre se ele não tiver recurrent_series_id
                          ->where('is_recurrent', true);
                })
                ->get()
                ->unique('recurrent_series_id') // Garante uma linha por série
                ->map(function($r) {
                    // Formata os dados para o JS (Master ID, Slot, Cliente, Data Fim)
                    return [
                        'master_id' => $r->recurrent_series_id ?? $r->id,
                        'client_name' => $r->client_name ?? optional($r->user)->name ?? 'Cliente',
                        'slot_time' => $r->start_time . ' - ' . $r->end_time,
                        'slot_price' => $r->price,
                        'day_of_week' => $r->day_of_week,
                        'last_date' => $r->recurrent_end_date ? $r->recurrent_end_date->toDateString() : null,
                    ];
                });

        } catch (\Exception $e) {
            Log::warning("Não foi possível carregar séries recorrentes expirando: " . $e->getMessage());
            $expiringSeries = collect();
            $expiringSeriesCount = 0;
        }

        return view('dashboard', compact('reservasPendentesCount', 'expiringSeries', 'expiringSeriesCount'));
    }
    // =========================================================================


    public function searchClients(Request $request)
    {
        $query = $request->input('query');

        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }

        // Busca usuários com a role 'cliente'
        $clients = User::where('role', 'cliente')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', '%' . $query . '%')
                    ->orWhere('email', 'like', '%' . $query . '%')
                    ->orWhere('whatsapp_contact', 'like', '%' . $query . '%');
            })
            // Limita a 10 resultados para otimizar a pesquisa
            ->limit(10)
            ->get();

        // Formata a saída para o JS
        $formattedClients = $clients->map(function ($client) {
            // O ideal é usar o Accessor 'formatted_whatsapp_contact' se ele estiver definido no User Model
            $formattedContact = $client->formatted_whatsapp_contact ?? $client->whatsapp_contact;

            return [
                'id' => $client->id,
                'name' => $client->name,
                'email' => $client->email,
                'whatsapp_contact' => $formattedContact,
                'contact' => $client->whatsapp_contact, // Retorna o contato cru (sem formatação)
            ];
        });

        return response()->json($formattedClients);
    }
    // =========================================================================

    // =========================================================================
    // MÉTODO API: RESERVAS CONFIRMADAS/PENDENTES PARA FULLCALENDAR
    // NOTA: Este método está obsoleto se a rota 'api.reservas.confirmadas' aponta para o ApiReservaController.
    // Manter apenas para compatibilidade de referência, mas deve ser ignorado.
    // =========================================================================
    public function getConfirmedReservasApi(Request $request)
    {
        // Este método está sendo substituído pelo ApiReservaController::getConfirmedReservas
        // Para evitar bugs, vamos simular o comportamento antigo, mas com a correção da duplicação (is_fixed = false)
        // Se a sua rota web.php estiver correta, este método não será chamado.
        Log::warning('AdminController::getConfirmedReservasApi foi chamado. Verifique se a rota web.php aponta para ApiReservaController::getConfirmedReservas.');

        $start = $request->input('start') ? Carbon::parse($request->input('start')) : Carbon::now()->startOfMonth();
        $end = $request->input('end') ? Carbon::parse($request->input('end')) : Carbon::now()->endOfMonth();

        // 🛑 CORREÇÃO: Filtra SLOTS FIXOS (is_fixed = true) para evitar duplicação com slots disponíveis.
        $reservas = Reserva::whereIn('status', [Reserva::STATUS_CONFIRMADA, Reserva::STATUS_PENDENTE])
            ->where('is_fixed', false) // APENAS reservas de cliente (is_fixed=false)
            ->whereDate('date', '>=', $start->toDateString())
            ->whereDate('date', '<=', $end->toDateString())
            ->with('user')
            ->get();

        $events = $reservas->map(function ($reserva) {
            $bookingDate = $reserva->date->toDateString();

            $start = Carbon::parse($bookingDate . ' ' . $reserva->start_time);
            $end = $reserva->end_time ? Carbon::parse($bookingDate . ' ' . $reserva->end_time) : $start->copy()->addHour();

            $userName = optional($reserva->user)->name;
            $clientName = $userName ?? $reserva->client_name ?? 'Cliente Desconhecido';

            $isRecurrent = (bool)$reserva->is_recurrent;

            if ($reserva->status === Reserva::STATUS_PENDENTE) {
                $statusColor = '#ff9800'; // Orange
                $statusText = 'PENDENTE: ';
                $className = 'fc-event-pending';
            } elseif ($isRecurrent) {
                $statusColor = '#C026D3'; // Fuchsia
                $statusText = 'RECORRENTE: ';
                $className = 'fc-event-recurrent';
            } else {
                $statusColor = '#4f46e5'; // Indigo
                $statusText = ''; // Removido prefixo para usar apenas o nome do cliente no título
                $className = 'fc-event-quick';
            }

            // Monta o título do evento
            $title = $statusText . $clientName;

            // Removido o preço do título para maior clareza, já que o API Controller faz isso
            // if (isset($reserva->price)) {
            //     $title .= ' - R$ ' . number_format($reserva->price, 2, ',', '.');
            // }

            return [
                'id' => $reserva->id,
                'title' => $title,
                'start' => $start->format('Y-m-d\TH:i:s'),
                'end' => $end->format('Y-m-d\TH:i:s'),
                'color' => $statusColor,
                'className' => $className,
                'extendedProps' => [
                    'status' => $reserva->status,
                    'price' => $reserva->price,
                    'is_recurrent' => $isRecurrent,
                    'is_fixed' => false,
                    'recurrent_series_id' => $reserva->recurrent_series_id,
                ]
            ];
        });

        return response()->json($events);
    }
    // =========================================================================

    // --- Métodos de Listagem, Ação e Status de Reservas ---

    public function indexReservas()
    {
        $reservas = Reserva::where('status', Reserva::STATUS_PENDENTE)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
        $pageTitle = 'Pré-Reservas Pendentes';
        // 🛑 ATUALIZADO: Usando a nova view de listagem 'index.blade.php'
        return view('admin.reservas.index', compact('reservas', 'pageTitle'));
    }

    /**
     * Exibe o índice de reservas confirmadas, ordenadas por data crescente.
     */
    public function confirmed_index(Request $request)
    {
        $search = $request->get('search');

        $query = Reserva::where('status', Reserva::STATUS_CONFIRMADA)
            // Filtra slots fixos (is_fixed = true)
            ->where('is_fixed', false)
            // Apenas reservas futuras ou de hoje
            ->whereDate('date', '>=', Carbon::today()->toDateString())
            ->with('user');

        // Aplica filtro de pesquisa
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('client_contact', 'like', '%' . $search . '%');
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            });
        }

        $isOnlyMine = $request->get('only_mine') === 'true';

        if ($isOnlyMine) {
            $pageTitle = 'Minhas Reservas Manuais Confirmadas';
            $query->where('manager_id', Auth::id());
        } else {
            $pageTitle = 'Todas as Reservas Confirmadas (Próximos Agendamentos)';
        }

        $reservas = $query->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->paginate(15);

        // 🛑 ATUALIZADO: Usando a view de listagem 'confirmed-index.blade.php'
        return view('admin.reservas.confirmed-index', compact('reservas', 'pageTitle', 'isOnlyMine', 'search'));
    }

    // =========================================================================
    // ✅ NOVO MÉTODO: LISTA DE RESERVAS CANCELADAS
    // =========================================================================
    /**
     * Exibe o índice de todas as reservas canceladas ou rejeitadas.
     */
    public function canceled_index(Request $request)
    {
        $search = $request->get('search');

        $query = Reserva::whereIn('status', [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])
            // Filtra slots fixos que foram recriados (is_fixed = true)
            ->where('is_fixed', false)
            ->with('user', 'manager'); // Carrega quem cancelou/rejeitou

        // Aplica filtro de pesquisa
        if ($search) {
             $query->where(function ($q) use ($search) {
                $q->where('client_name', 'like', '%' . $search . '%')
                    ->orWhere('client_contact', 'like', '%' . $search . '%');
                $q->orWhereHas('user', function ($userQuery) use ($search) {
                    $userQuery->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%');
                });
            });
        }

        $reservas = $query->orderBy('date', 'desc')
            ->orderBy('start_time', 'desc')
            ->paginate(15);

        $pageTitle = 'Histórico de Reservas Canceladas/Rejeitadas';

        return view('admin.reservas.canceled-index', compact('reservas', 'pageTitle', 'search'));
    }
    // =========================================================================


    public function showReserva(Reserva $reserva)
    {
        $reserva->load('user', 'manager');
        return view('admin.reservas.show', compact('reserva'));
    }

    /**
     * Redireciona a rota de criação manual para o Dashboard.
     */
    public function createReserva()
    {
        return redirect()->route('dashboard')
            ->with('warning', 'A criação manual foi simplificada! Por favor, use o calendário (slots verdes) na tela principal para agendamento rápido.');
    }

    // --- MÉTODOS DE AÇÕES PADRÃO (CONFIRMAR, REJEITAR, CANCELAR) ---

    public function confirmarReserva(Reserva $reserva)
    {
        DB::beginTransaction();
        try {
            $dateString = $reserva->date->toDateString();
            $isFixed = $reserva->is_fixed;
            $ignoreId = $reserva->id;

            // 1. Checagem de Conflito (Usando o método local)
            if ($this->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                DB::rollBack();
                return back()->with('error', 'Conflito detectado: Esta reserva não pode ser confirmada pois já existe outro agendamento (Pendente ou Confirmado) no mesmo horário.');
            }

            // 2. Atualiza Status e atribui o Gestor
            $reserva->update([
                'status' => Reserva::STATUS_CONFIRMADA,
                'manager_id' => Auth::id(), // O gestor que confirma
            ]);

            DB::commit();

            // DEFESA: Força a recarga do objeto do usuário autenticado no Laravel
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('dashboard')
                ->with('success', 'Reserva confirmada com sucesso! O horário está agora visível no calendário.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao confirmar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao confirmar a reserva: ' . $e->getMessage());
        }
    }

    /**
     * Recria o slot fixo após a rejeição da pré-reserva.
     */
    public final function rejeitarReserva(Reserva $reserva)
    {
        DB::beginTransaction();
        try {
            // 1. Captura as informações do slot original (data, hora, preço)
            $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);

            // 2. Marca o status como REJEITADA e o gestor responsável
            $reserva->update([
                'status' => Reserva::STATUS_REJEITADA,
                'manager_id' => Auth::id(),
                'cancellation_reason' => 'Pré-reserva rejeitada pelo gestor.'
            ]);

            // Lógica para recriar o slot (impedindo duplicação)
            $reservaRecreated = Reserva::where('date', $originalData['date']->toDateString())
                ->where('start_time', $originalData['start_time'])
                ->where('end_time', $originalData['end_time'])
                ->where('is_fixed', true)
                ->exists();

            if (!$reservaRecreated) {
                // 3. Recria o slot fixo de disponibilidade (o evento verde)
                Reserva::create([
                    'date' => $originalData['date']->toDateString(),
                    'day_of_week' => $originalData['day_of_week'],
                    'start_time' => $originalData['start_time'],
                    'end_time' => $originalData['end_time'],
                    'price' => $originalData['price'],
                    'client_name' => 'Slot Fixo de 1h',
                    'client_contact' => 'N/A',
                    'status' => Reserva::STATUS_CONFIRMADA, // Torna o slot DISPONÍVEL (verde)
                    'is_fixed' => true,
                    'manager_id' => Auth::id(),
                ]);
            }

            // 4. Deleta a reserva rejeitada (se necessário, para histórico, considere um Soft Delete ou mover a linha 2)
            $reserva->delete();

            DB::commit();

            // Força a recarga do objeto do usuário autenticado no Laravel
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.index')
                ->with('success', 'Pré-reserva rejeitada e horário liberado com sucesso.');

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao rejeitar a reserva ID {$reserva->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao rejeitar a reserva: ' . $e->getMessage());
        }
    }

    // ✅ MÉTODO: Cancelamento Pontual de Reserva Padrão (Avulso)
    public function cancelarReserva(Request $request, Reserva $reserva)
    {
        // Validação do Motivo do Cancelamento
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if ($reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Esta reserva é recorrente. Use o botão "Cancelar ESTE DIA" ou "Cancelar SÉRIE" para gerenciar.'], 422);
        }

        DB::beginTransaction();
        try {
            // 1. Atualiza o status para cancelado e salva o motivo
            $reserva->update([
                'status' => Reserva::STATUS_CANCELADA,
                'manager_id' => Auth::id(),
                'cancellation_reason' => $request->input('cancellation_reason'),
            ]);

            // Dispara o Evento de Notificação (se necessário)
            if (class_exists(\App\Events\ReservaCancelada::class)) {
                event(new \App\Events\ReservaCancelada($reserva));
            }

            // 2. Recria o slot fixo de disponibilidade
            $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);

            // Lógica para recriar o slot (impedindo duplicação)
            $reservaRecreated = Reserva::where('date', $originalData['date']->toDateString())
                ->where('start_time', $originalData['start_time'])
                ->where('end_time', $originalData['end_time'])
                ->where('is_fixed', true)
                ->exists();

            if (!$reservaRecreated) {
                Reserva::create([
                    'date' => $originalData['date']->toDateString(),
                    'day_of_week' => $originalData['day_of_week'],
                    'start_time' => $originalData['start_time'],
                    'end_time' => $originalData['end_time'],
                    'price' => $originalData['price'],
                    'client_name' => 'Slot Fixo de 1h',
                    'client_contact' => 'N/A',
                    'status' => Reserva::STATUS_CONFIRMADA,
                    'is_fixed' => true,
                    'manager_id' => Auth::id(),
                ]);
            }

            // 3. Deleta a reserva cancelada (para histórico, você pode mover para uma tabela de arquivamento em vez de deletar)
            $reserva->delete();

            DB::commit();

            // DEFESA: Força a recarga do objeto do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return response()->json(['success' => true, 'message' => 'Reserva pontual cancelada e slot liberado com sucesso.'], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao processar cancelamento de reserva ID {$reserva->id}: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar o cancelamento: ' . $e->getMessage()], 500);
        }
    }


    // =========================================================================
    // Cancelamento Pontual de Reserva Recorrente (Exceção)
    // =========================================================================
    public function cancelarReservaRecorrente(Request $request, Reserva $reserva)
    {
        // Validação do Motivo
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Esta reserva não faz parte de uma série recorrente e deve ser cancelada diretamente.'], 422);
        }

        // 1. Captura as informações do slot original
        $originalData = $reserva->only(['date', 'day_of_week', 'start_time', 'end_time', 'price']);
        $cancellationReason = $request->input('cancellation_reason');

        DB::beginTransaction();
        try {
            // Marca o motivo antes de deletar (para histórico, se necessário)
            $reserva->cancellation_reason = $cancellationReason . " (Pontual da Série)";
            $reserva->manager_id = Auth::id();
            $reserva->status = Reserva::STATUS_CANCELADA;
            $reserva->save();

            // Dispara o Evento de Notificação (se necessário)
            if (class_exists(\App\Events\ReservaCancelada::class)) {
                event(new \App\Events\ReservaCancelada($reserva));
            }

            // 2. Apaga a reserva real do cliente (A reserva recorrente)
            $reserva->delete();

            // Lógica para recriar o slot (impedindo duplicação)
            $reservaRecreated = Reserva::where('date', $originalData['date']->toDateString())
                ->where('start_time', $originalData['start_time'])
                ->where('end_time', $originalData['end_time'])
                ->where('is_fixed', true)
                ->exists();

            if (!$reservaRecreated) {
                // 3. Recria o slot fixo de disponibilidade (o evento verde)
                Reserva::create([
                    'date' => $originalData['date']->toDateString(),
                    'day_of_week' => $originalData['day_of_week'],
                    'start_time' => $originalData['start_time'],
                    'end_time' => $originalData['end_time'],
                    'price' => $originalData['price'],
                    'client_name' => 'Slot Fixo de 1h', // Nome padrão
                    'client_contact' => 'N/A',
                    'status' => Reserva::STATUS_CONFIRMADA, // Torna o slot DISPONÍVEL (verde)
                    'is_fixed' => true, // Volta a ser um slot fixo, mas apenas para esta data!
                    'manager_id' => Auth::id(), // Registra o gestor que liberou o slot
                ]);
            }

            DB::commit();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return response()->json([
                'success' => true,
                'message' => "Cancelamento pontual realizado! O horário de {$reserva->client_name} no dia {$originalData['date']->format('d/m/Y')} foi liberado para novos agendamentos PONTUAIS."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao recriar slot fixo após cancelamento pontual: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao processar o cancelamento pontual: ' . $e->getMessage()], 500);
        }
    }

    // =========================================================================
    // Cancelamento de SÉRIE Recorrente
    // =========================================================================
    public function cancelarSerieRecorrente(Request $request, Reserva $reserva)
    {
        // Validação do Motivo
        $request->validate([
            'cancellation_reason' => 'required|string|min:5',
        ]);

        if (!$reserva->is_recurrent) {
            return response()->json(['success' => false, 'message' => 'Esta reserva não faz parte de uma série recorrente e não pode ser cancelada em série.'], 422);
        }

        // 1. Identifica a série (mestra ou membro)
        $masterId = $reserva->recurrent_series_id ?? $reserva->id;
        $clientName = $reserva->client_name;
        $cancellationReason = $request->input('cancellation_reason');

        // 2. Busca o slot mestre e todos os membros futuros
        $reservasToCancel = Reserva::where(function ($query) use ($masterId) {
            // Inclui o mestre (se a reserva atual for o mestre)
            $query->where('id', $masterId)
                // Inclui todos os membros vinculados
                ->orWhere('recurrent_series_id', $masterId);
        })
            // Apenas reservas futuras (a partir da data da reserva atual ou depois)
            ->whereDate('date', '>=', $reserva->date->toDateString())
            ->where('is_fixed', false) // Apenas reservas reais de cliente
            ->get();

        $count = $reservasToCancel->count();

        if ($count === 0) {
            return response()->json(['success' => false, 'message' => 'Nenhuma reserva futura encontrada para esta série a partir desta data.'], 404);
        }

        // 3. Executa o cancelamento em massa (Deletar as reservas reais e recriar slots fixos)
        DB::beginTransaction();
        try {
            // Captura os dados para recriação do slot (de qualquer item da série)
            $firstReserva = $reservasToCancel->first();
            $start = $firstReserva->start_time;
            $end = $firstReserva->end_time;
            $dayOfWeek = $firstReserva->day_of_week;
            $price = $firstReserva->price;

            // Itera e marca o motivo em cada reserva antes de deletar
            $reservasToCancel->each(function ($r) use ($cancellationReason, $dayOfWeek) {
                $r->cancellation_reason = $cancellationReason . " (Série Recorrente - Dia da Semana: " . $dayOfWeek . ")";
                $r->manager_id = Auth::id();
                $r->status = Reserva::STATUS_CANCELADA;
                $r->save();

                // Dispara o Evento de Notificação (se necessário)
                if (class_exists(\App\Events\ReservaCancelada::class)) {
                    event(new \App\Events\ReservaCancelada($r));
                }
            });

            // Apaga todas as reservas reais da série futuras
            Reserva::whereIn('id', $reservasToCancel->pluck('id'))->delete();

            // 4. Recria a série de slots fixos genéricos para o mesmo período
            $dates = $reservasToCancel->pluck('date');
            $dates->each(function ($date) use ($dayOfWeek, $start, $end, $price) {

                $reservaRecreated = Reserva::where('date', $date->toDateString())
                    ->where('start_time', $start)
                    ->where('end_time', $end)
                    ->where('is_fixed', true)
                    ->exists();

                if (!$reservaRecreated) {
                    Reserva::create([
                        'date' => $date->toDateString(),
                        'day_of_week' => $dayOfWeek,
                        'start_time' => $start,
                        'end_time' => $end,
                        'price' => $price,
                        'client_name' => 'Slot Fixo de 1h',
                        'client_contact' => 'N/A',
                        'status' => Reserva::STATUS_CONFIRMADA, // Volta a ser Disponível
                        'is_fixed' => true,
                        'manager_id' => Auth::id(), // Registra o gestor que liberou o slot
                    ]);
                }
            });

            DB::commit();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }


            return response()->json([
                'success' => true,
                'message' => "Série recorrente do cliente '{$clientName}' ({$start}) cancelada com sucesso! {$count} slots foram liberados para agendamentos pontuais."
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Erro ao cancelar série recorrente (ID Mestra: {$masterId}): " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Erro ao cancelar a série recorrente: ' . $e->getMessage()], 500);
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

        // Lógica de Confirmação (com checagem de conflito)
        if ($newStatus === Reserva::STATUS_CONFIRMADA) {
            try {
                $dateString = $reserva->date->toDateString();
                $isFixed = $reserva->is_fixed;
                $ignoreId = $reserva->id;

                if ($this->checkOverlap($dateString, $reserva->start_time, $reserva->end_time, $isFixed, $ignoreId)) {
                    return back()->with('error', 'Conflito detectado: Não é possível confirmar, pois já existe outro agendamento neste horário.');
                }
                $updateData['manager_id'] = Auth::id();
            } catch (\Exception $e) {
                return back()->with('error', 'Erro na verificação de conflito: ' . $e->getMessage());
            }
        }

        if (in_array($newStatus, [Reserva::STATUS_REJEITADA, Reserva::STATUS_CANCELADA]) && !isset($updateData['manager_id'])) {
            $updateData['manager_id'] = Auth::id();
        }

        // AÇÃO CRÍTICA: Se for CANCELADA ou REJEITADA via esta rota, redireciona para o Dashboard/Lista
        // para forçar o uso dos métodos dedicados que recriam o slot fixo.
        if (in_array($newStatus, [Reserva::STATUS_CANCELADA, Reserva::STATUS_REJEITADA])) {
            return redirect()->route('dashboard')->with('warning', 'Reserva marcada como ' . $newStatus . '. Use o modal de cancelamento/rejeição na lista/calendário para liberar o slot.');
        }

        try {
            $reserva->update($updateData);

            // DEFESA: Força a recarga do objeto do usuário autenticado após o update
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.show', $reserva)
                ->with('success', "Status da reserva alterado para '{$newStatus}' com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao atualizar o status da reserva: ' . $e->getMessage());
        }
    }

    public function destroyReserva(Reserva $reserva)
    {
        // Impede a exclusão direta de reservas recorrentes.
        if ($reserva->is_recurrent) {
            return back()->with('warning', 'Esta reserva faz parte de uma série recorrente. Use a opção "Cancelar Apenas Este Dia" ou "Cancelar Série Inteira" na tela de detalhes/calendário para gerenciar.');
        }

        try {
            $name = $reserva->client_name;
            $reserva->delete();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.reservas.index')
                ->with('success', "Reserva de $name excluída permanentemente com sucesso.");
        } catch (\Exception $e) {
            return back()->with('error', 'Erro ao excluir a reserva: ' . $e->getMessage());
        }
    }

    // --- Métodos de CRUD de Usuários ---

    /**
     * Lista usuários com filtro por 'role'.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\View\View
     */
    public function indexUsers(Request $request)
    {
        // 1. Define o filtro e a query base
        $roleFilter = $request->get('role_filter');
        $query = User::orderBy('name', 'asc');

        $activeFilter = null;
        $pageTitle = 'Usuários Cadastrados';

        // 2. Aplica a lógica de filtro de forma condicional
        if ($roleFilter === 'cliente') {
            $query->where('role', 'cliente');
            $pageTitle = 'Clientes Cadastrados';
            $activeFilter = 'cliente';
        } elseif ($roleFilter === 'gestor') {
            // Inclui Gestores e Administradores
            $query->whereIn('role', ['gestor', 'admin']);
            $pageTitle = 'Gestores e Administradores';
            $activeFilter = 'gestor';
        } else {
            // Caso 'TODOS' ou parâmetro ausente. Não aplica WHERE para listar todos.
            $pageTitle = 'Todos os Usuários Cadastrados';
            $activeFilter = 'all'; // Define um valor para o botão 'Todos' ficar ativo no Blade
        }

        // 3. Executa a query com paginação
        $users = $query->paginate(20);

        // 4. Retorna a view com os dados
        return view('admin.users.index', [
            'users' => $users,
            'pageTitle' => $pageTitle,
            'roleFilter' => $activeFilter, // Passa o filtro ativo para o Blade
        ]);
    }

    public function createUser()
    {
        return view('admin.users.create');
    }

    /**
     * Lida com a submissão do formulário para criar um novo Gestor/Admin ou Cliente.
     */
    public function storeUser(Request $request)
    {
        // 1. Log para diagnóstico
        Log::info('Tentativa de cadastro de usuário. Dados recebidos: ', $request->all());

        // Define se é Gestor/Admin ou Cliente
        $role = $request->input('role', 'cliente');
        $isGestorOrAdmin = in_array($role, ['gestor', 'admin']);

        // 1. Definição das Regras de Validação CONDICIONAL
        $rules = [
            'name' => 'required|string|max:255',
            // O email precisa ser único para a criação
            'email' => 'required|string|email|max:255|unique:users',
            // Permite 'admin' pois é uma rota de gestão
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor', 'admin'])],
            // **CORREÇÃO:** Mantido 'unique:users' para o contato do WhatsApp (Para criar novo usuário).
            'whatsapp_contact' => 'nullable|string|max:20|unique:users',
            'data_nascimento' => 'nullable|date',
        ];

        if ($isGestorOrAdmin) {
            // Senha OBRIGATÓRIA apenas para Gestor/Admin
            $rules['password'] = 'required|string|confirmed|min:8';
            $rules['password_confirmation'] = 'required'; // Garante que a confirmação foi enviada
        } else {
            // Senha e confirmação são opcionais/não necessárias para Cliente
            $rules['password'] = 'nullable';
            $rules['password_confirmation'] = 'nullable';
        }

        // Validação - Se falhar, redireciona de volta automaticamente.
        $validatedData = $request->validate($rules);

        try {
            // Define a senha a ser salva
            $passwordToSave = null;
            if ($request->filled('password')) {
                // Se o campo password foi preenchido, usa o valor fornecido (hash)
                $passwordToSave = Hash::make($validatedData['password']);
            } elseif ($role === 'cliente') {
                // SE o usuário é cliente E não forneceu senha (o que é esperado),
                // geramos uma senha aleatória e segura para satisfazer a restrição NOT NULL do DB.
                $passwordToSave = Hash::make(Str::random(16));
                Log::info('Gerando senha aleatória para cliente: ' . $validatedData['email']);
            }

            // 2. Criação
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'whatsapp_contact' => $validatedData['whatsapp_contact'] ?? null,
                'data_nascimento' => $validatedData['data_nascimento'] ?? null,
                // Usa o valor tratado acima
                'password' => $passwordToSave,
                'role' => $role,
            ]);

            // 3. Sucesso e Redirecionamento
            return redirect()->route('admin.users.index')->with('success', 'O usuário ' . $user->name . ' ('.$role.') foi criado com sucesso!');

        } catch (\Exception $e) {
            // 4. Captura de Erros e Log
            Log::error('Erro ao criar usuário via Admin: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Erro inesperado ao criar o usuário. Verifique o log do sistema.');
        }
    }

// -------------------------------------------------------------------------
// 🛠️ MÉTODOS DE EDIÇÃO E EXCLUSÃO DE USUÁRIOS
// -------------------------------------------------------------------------


    /**
     * Exibe o formulário para edição de um usuário específico.
     * @param User $user O modelo de usuário a ser editado (Route Model Binding).
     */
    public function editUser(User $user)
    {
        // Regra de segurança: Gestores não podem editar o próprio 'admin'
        if ($user->role === 'admin' && Auth::user()->role !== 'admin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não tem permissão para editar usuários Administradores.');
        }

        return view('admin.users.edit', compact('user'));
    }

    /**
     * Processa a atualização de um usuário.
     * @param Request $request
     * @param User $user O modelo de usuário a ser atualizado.
     */
    public function updateUser(Request $request, User $user)
    {
        // 1. Regras de Validação
        $rules = [
            'name' => 'required|string|max:255',
            // O email deve ser único, exceto para o usuário atual
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => ['required', 'string', Rule::in(['cliente', 'gestor', 'admin'])],

            // Campos Adicionais
            // APLICADA CORREÇÃO: WhatsApp deve ser único exceto para o usuário atual
            'whatsapp_contact' => ['nullable', 'string', 'max:20', Rule::unique('users')->ignore($user->id)],
            'data_nascimento' => 'nullable|date|before:today',

            // Senha é opcional, mas se preenchida, deve ter pelo menos 8 caracteres e ser confirmada
            'password' => 'nullable|string|min:8|confirmed',
        ];

        $request->validate($rules);

        // 2. Garante Permissão para Alterar Role 'admin'
        // Se o usuário logado não for admin, ele não pode definir a role como 'admin'
        if (Auth::user()->role !== 'admin' && $request->role === 'admin') {
            return back()->withInput()->withErrors(['role' => 'Apenas Administradores podem definir um usuário como Administrador.']);
        }

        // Impede que um gestor altere um admin para outra função
        if (Auth::user()->role !== 'admin' && $user->role === 'admin' && $request->role !== 'admin') {
            return back()->withInput()->withErrors(['role' => 'Você não tem permissão para rebaixar um Administrador.']);
        }


        // 3. Atualização dos Dados
        $data = $request->only('name', 'email', 'role', 'whatsapp_contact', 'data_nascimento');

        // Se uma nova senha foi fornecida, hash e adicione aos dados
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // DEFESA: Força a recarga do usuário autenticado caso ele tenha alterado a própria role
        if (Auth::check()) {
            Auth::user()->fresh();
        }

        return redirect()->route('admin.users.edit', $user)
            ->with('success', 'Usuário atualizado com sucesso!');
    }

    /**
     * Remove um usuário do sistema.
     * @param User $user O modelo de usuário a ser excluído.
     */
    public function destroyUser(User $user)
    {
        // Regra de segurança 1: O usuário não pode excluir a si mesmo
        if (Auth::user()->id === $user->id) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não pode excluir a si mesmo.');
        }

        // Regra de segurança 2: Apenas administradores podem excluir outros administradores
        if ($user->role === 'admin' && Auth::user()->role !== 'admin') {
            return redirect()->route('admin.users.index')
                ->with('error', 'Você não tem permissão para excluir um usuário Administrador.');
        }

        try {
            $name = $user->name;
            $user->delete();

            // DEFESA: Força a recarga do usuário autenticado após a transação
            if (Auth::check()) {
                Auth::user()->fresh();
            }

            return redirect()->route('admin.users.index')
                ->with('success', "Usuário '$name' excluído com sucesso.");
        } catch (\Exception $e) {
            Log::error("Erro ao excluir o usuário {$user->id}: " . $e->getMessage());
            return back()->with('error', 'Erro ao excluir o usuário: ' . $e->getMessage());
        }
    }
}
