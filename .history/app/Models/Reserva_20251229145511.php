<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reserva extends Model
{
    use HasFactory;

    /**
     * Define as constantes de status de uma Reserva.
     * Esta é a lista centralizada de todos os estados possíveis (alinhado com a lógica dos Controllers).
     */
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONFIRMADA = 'confirmed';
    public const STATUS_CONCLUIDA = 'completed'; // Pagamento integral feito
    public const STATUS_CANCELADA = 'cancelled';
    public const STATUS_REJEITADA = 'rejected';
    public const STATUS_EXPIRADA = 'expired';
    public const STATUS_NO_SHOW = 'no_show'; // Falta do cliente
    public const STATUS_FREE = 'free'; // Slot de disponibilidade (inventário)
    public const STATUS_MAINTENANCE = 'maintenance'; // Slot de manutenção (inventário bloqueado)
    public const STATUS_LANCADA_CAIXA = 'launched'; // Lançada no caixa, mas ainda não concluída (intermediário)

    /**
     * Os atributos que podem ser atribuídos em massa (mass assignable).
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'manager_id',
        'date',
        'day_of_week',
        'start_time',
        'end_time',
        'price',
        'final_price',
        'signal_value',
        'total_paid',
        'payment_status',
        'payment_method',
        'client_name',
        'client_contact',
        'notes',
        'status',
        'is_fixed',
        'is_recurrent',
        'recurrent_series_id',
        'fixed_slot_id', // ID do slot fixo consumido (para pré-reservas)
        'cancellation_reason',
        'no_show_reason',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'is_fixed' => 'boolean',
        'is_recurrent' => 'boolean',
        'price' => 'float',
        'final_price' => 'float',
        'signal_value' => 'float',
        'total_paid' => 'float',
        // ✅ CORRIGIDO: Garante que as horas são tratadas como objetos Carbon
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    // ... manter casts e outros métodos acima ...

    /**
     * 🛡️ TRAVA DE SEGURANÇA: Impede criação/exclusão em caixa fechado
     */
    protected static function boot()
    {
        parent::boot();

        // Bloqueia a criação de novas reservas
        static::creating(function ($reserva) {
            $financeiro = app(\App\Http\Controllers\FinanceiroController::class);

            // Verifica a data da reserva
            $dateToCheck = $reserva->date instanceof \Carbon\Carbon
                ? $reserva->date->toDateString()
                : $reserva->date;

            if ($financeiro->isCashClosed($dateToCheck)) {
                throw new \Exception("Bloqueio de Segurança: O caixa do dia " . \Carbon\Carbon::parse($dateToCheck)->format('d/m/Y') . " já está encerrado. Reabra-o para agendar.");
            }
        });

        // Bloqueia a exclusão de reservas existentes
        static::deleting(function ($reserva) {
            $financeiro = app(\App\Http\Controllers\FinanceiroController::class);

            $dateToCheck = $reserva->date instanceof \Carbon\Carbon
                ? $reserva->date->toDateString()
                : $reserva->date;

            if ($financeiro->isCashClosed($dateToCheck)) {
                throw new \Exception("Bloqueio de Segurança: Não é possível excluir agendamentos de um dia com caixa encerrado.");
            }
        });
    }

    // ... manter relacionamentos e scopes abaixo ...

    // -------------------------------------------------------------------------
    // RELACIONAMENTOS (Relationships)
    // -------------------------------------------------------------------------

    /**
     * Obtém o usuário (cliente) que fez a reserva.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Obtém o usuário (administrador/gestor) que processou a reserva por último.
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * Obtém as transações financeiras associadas a esta reserva.
     */
    public function transactions(): HasMany
    {
        // Assume que a classe FinancialTransaction existe em App\Models\FinancialTransaction
        return $this->hasMany(FinancialTransaction::class, 'reserva_id');
    }

    /**
     * Obtém a reserva mestra, se esta reserva fizer parte de uma série recorrente.
     */
    public function masterReserva(): BelongsTo
    {
        // A chave estrangeira é 'recurrent_series_id' e refere-se ao 'id' da Reserva Mestra.
        return $this->belongsTo(Reserva::class, 'recurrent_series_id');
    }

    /**
     * Obtém todos os slots (reservas) que fazem parte desta série recorrente.
     * Útil quando chamada na Reserva Mestra.
     */
    public function seriesReservas(): HasMany
    {
        // A chave estrangeira é 'recurrent_series_id' e refere-se ao 'id' desta reserva (mestra)
        return $this->hasMany(Reserva::class, 'recurrent_series_id');
    }

    // -------------------------------------------------------------------------
    // SCOPES (Consultas Comuns)
    // -------------------------------------------------------------------------

    /**
     * Scope para buscar apenas reservas ativas de clientes (confirmed e pending).
     */
    public function scopeActiveCustomer(
        \Illuminate\Database\Eloquent\Builder $query
    ): void {
        $query->where('is_fixed', false)
            ->whereIn('status', [self::STATUS_CONFIRMADA, self::STATUS_PENDENTE]);
    }

    /**
     * Scope para buscar apenas slots fixos de disponibilidade (free e maintenance).
     */
    public function scopeFixedSlots(
        \Illuminate\Database\Eloquent\Builder $query
    ): void {
        $query->where('is_fixed', true)
            ->whereIn('status', [self::STATUS_FREE, self::STATUS_MAINTENANCE]);
    }

    public function scopeIsOccupied($query, $date, $startTime, $endTime)
    {
        return $query->where('date', $date)
            ->whereIn('status', [self::STATUS_CONFIRMADA, self::STATUS_PENDENTE, self::STATUS_CONCLUIDA])
            ->where(function ($q) use ($startTime, $endTime) {
                $q->where(function ($inner) use ($startTime, $endTime) {
                    // Caso 1: O início da nova reserva está entre uma reserva existente
                    $inner->where('start_time', '>=', $startTime)
                        ->where('start_time', '<', $endTime);
                })
                    ->orWhere(function ($inner) use ($startTime, $endTime) {
                        // Caso 2: O fim da nova reserva está entre uma reserva existente
                        $inner->where('end_time', '>', $startTime)
                            ->where('end_time', '<=', $endTime);
                    })
                    ->orWhere(function ($inner) use ($startTime, $endTime) {
                        // Caso 3: A nova reserva engloba totalmente uma reserva existente
                        $inner->where('start_time', '<=', $startTime)
                            ->where('end_time', '>=', $endTime);
                    });
            });
    }
}
