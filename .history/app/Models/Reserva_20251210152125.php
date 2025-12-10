<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User;
use App\Models\FinancialTransaction;

class Reserva extends Model
{
    use HasFactory;

    // ------------------------------------------------------------------------
    // CONSTANTES DE STATUS PRINCIPAL
    // ------------------------------------------------------------------------
    public const STATUS_FREE = 'free';
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONFIRMADA = 'confirmed';
    public const STATUS_CANCELADA = 'cancelled';
    public const STATUS_REJEITADA = 'rejected';
    public const STATUS_EXPIRADA = 'expired';
    public const STATUS_MAINTENANCE = 'maintenance';
    public const STATUS_LANCADA_CAIXA = 'paid_to_cashier';
    public const STATUS_CONCLUIDA = 'completed';
    public const STATUS_NO_SHOW = 'no_show'; // ✅ Status para Falta

    // ------------------------------------------------------------------------
    // CONSTANTES DE STATUS DE PAGAMENTO
    // ------------------------------------------------------------------------
    public const PAYMENT_STATUS_PENDING = 'pending';
    public const PAYMENT_STATUS_PARTIAL = 'partial';
    public const PAYMENT_STATUS_PAID = 'paid';
    public const PAYMENT_STATUS_UNPAID = 'unpaid';

    /**
     * Os atributos que são mass assignable.
     */
    protected $fillable = [
        'user_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'client_name',
        'client_contact',
        'notes',
        'status',
        'manager_id',

        'cancellation_reason',
        'fixed_slot_id',
        'no_show_reason',

        'is_fixed',
        'day_of_week',

        'is_recurrent',
        'recurrent_series_id',

        'final_price',
        'signal_value',
        'total_paid',
        'payment_status',
    ];

    /**
     * Os atributos que devem ser convertidos (casted) para tipos nativos.
     */
    protected $casts = [
        'date' => 'date',
        'is_fixed' => 'boolean',
        'is_recurrent' => 'boolean',
    ];

    // ------------------------------------------------------------------------
    // ✅ MÉTODOS DE CHECAGEM PARA FECHAMENTO DE CAIXA (O BLOQUEIO)
    // ------------------------------------------------------------------------

    /**
     * Retorna a lista de status que indicam que a reserva foi devidamente
     * tratada e NÃO exige mais ação do Gestor no Caixa (Baixa Dada).
     *
     * @return array
     */
    public static function getFinalizedStatuses(): array
    {
        return [
            self::STATUS_REJEITADA,
            self::STATUS_CANCELADA,
            self::STATUS_CONCLUIDA,
            self::STATUS_NO_SHOW,
        ];
    }

    /**
     * Retorna a lista de status que AINDA EXIGEM AÇÃO (Pending Finalization).
     * Estas reservas BLOQUEIAM o Fechamento de Caixa.
     *
     * @return array
     */
    public static function getActionRequiredStatuses(): array
    {
        return [
            self::STATUS_PENDENTE,      // Exige Confirmação
            self::STATUS_CONFIRMADA,    // Exige Conclusão/Cancelamento/Falta
            self::STATUS_LANCADA_CAIXA, // ADICIONADO: Bloqueia se já foi lançado, mas ainda falta concluir.
        ];
    }

    // ------------------------------------------------------------------------
    // SCOPES LOCAIS
    // ------------------------------------------------------------------------

    /**
     * Scope local para retornar todas as reservas que ESTÃO OCUPANDO um horário.
     */
    public function scopeIsOccupied($query, string $checkDate, string $checkStartTime, string $checkEndTime)
    {
        return $query->where('date', $checkDate)
            ->whereIn('status', [self::STATUS_CONFIRMADA, self::STATUS_PENDENTE])
            ->where('is_fixed', false)
            ->where(function ($q) use ($checkStartTime, $checkEndTime) {
                $q->where('start_time', '<', $checkEndTime)
                    ->where('end_time', '>', $checkStartTime);
            });
    }

    // ------------------------------------------------------------------------
    // RELACIONAMENTOS
    // ------------------------------------------------------------------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function fixedSlot(): BelongsTo
    {
        return $this->belongsTo(Reserva::class, 'fixed_slot_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    // ------------------------------------------------------------------------
    // ACESSORES MODERNOS
    // ------------------------------------------------------------------------

    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => match ($this->status) {
                self::STATUS_FREE => 'Livre (Slot)',
                self::STATUS_PENDENTE => 'Pendente',
                self::STATUS_CONFIRMADA => 'Confirmada',
                self::STATUS_CANCELADA => 'Cancelada',
                self::STATUS_REJEITADA => 'Rejeitada',
                self::STATUS_EXPIRADA => 'Expirada',
                self::STATUS_MAINTENANCE => 'Manutenção',
                self::STATUS_LANCADA_CAIXA => 'Lançada no Caixa',
                self::STATUS_CONCLUIDA => 'Concluída',
                self::STATUS_NO_SHOW => 'Falta (No-Show)',
                default => 'Desconhecido',
            },
        );
    }

    protected function criadoPorLabel(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->manager?->name ?? 'Cliente via Web',
        );
    }

    // =========================================================================
    // 💰 MÓDULO FINANCEIRO
    // =========================================================================

    public function getRemainingAmountAttribute(): float
    {
        $total = $this->final_price ?? $this->price;
        return max(0, $total - $this->total_paid);
    }

    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === self::PAYMENT_STATUS_PAID || $this->remaining_amount <= 0;
    }
}
