<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;
use App\Models\User;

class Reserva extends Model
{
    use HasFactory;

    // ------------------------------------------------------------------------
    // CONSTANTES DE STATUS
    // ------------------------------------------------------------------------
    public const STATUS_FREE = 'free'; // ✅ NOVO STATUS: Slot disponível, não ocupado
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONFIRMADA = 'confirmed';
    public const STATUS_CANCELADA = 'cancelled';
    public const STATUS_REJEITADA = 'rejected';
    public const STATUS_EXPIRADA = 'expired';

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
        'manager_id', // ID do gestor que criou/confirmou

        'cancellation_reason', // ✅ ADICIONADO: Motivo do cancelamento

        // --- Campos para Recorrência ---
        'is_fixed',         // Grade de slots fixos gerada pelo ConfigController
        'day_of_week',      // Dia da semana para filtros (0=Dom, 1=Seg, ...)

        'is_recurrent',     // Flag para saber se é parte de uma série de cliente fixo
        'recurrent_series_id', // ID do primeiro slot da série (mestre)
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
    // SCOPES LOCAIS (CORREÇÃO DA DISPONIBILIDADE)
    // ------------------------------------------------------------------------

    /**
     * Scope local para retornar todas as reservas que ESTÃO OCUPANDO um horário.
     * ✅ CRÍTICO: Este escopo agora só inclui reservas de CLIENTES ATIVAS.
     * Ele não deve incluir slots FIXOS (is_fixed=true) ou FREE (STATUS_FREE).
     */
    public function scopeIsOccupied($query, string $checkDate, string $checkStartTime, string $checkEndTime)
    {
        return $query->where('date', $checkDate) // 1. Filtra pela data específica
            // 2. FILTRO CRÍTICO: Apenas status que indicam ocupação real
            ->whereIn('status', [self::STATUS_CONFIRMADA, self::STATUS_PENDENTE])
            // 3. Opcional: Para ter certeza de que estamos vendo apenas reservas de clientes,
            // embora o filtro de status já exclua slots FREE.
            ->where('is_fixed', false)
            ->where(function ($q) use ($checkStartTime, $checkEndTime) {
                // 4. Lógica de sobreposição de tempo (usando as strings de hora 'HH:MM:SS')
                $q->where('start_time', '<', $checkEndTime)
                    ->where('end_time', '>', $checkStartTime);
            });
    }


    // ------------------------------------------------------------------------
    // RELACIONAMENTOS
    // ------------------------------------------------------------------------

    /**
     * Relação com o Usuário (o cliente que fez a reserva, se houver)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relação com o Gestor que manipulou ou criou a reserva (se houver)
     */
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    // ------------------------------------------------------------------------
    // ACESSORES MODERNOS
    // ------------------------------------------------------------------------

    /**
     * Retorna o nome amigável do status (usado nas listas do Admin).
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
                self::STATUS_FREE => 'Livre (Slot)', // ✅ NOVO
                self::STATUS_PENDENTE => 'Pendente',
                self::STATUS_CONFIRMADA => 'Confirmada',
                self::STATUS_CANCELADA => 'Cancelada',
                self::STATUS_REJEITADA => 'Rejeitada',
                self::STATUS_EXPIRADA => 'Expirada',
                default => 'Desconhecido',
            },
        );
    }

    /**
     * Retorna o nome do criador (Gestor ou Cliente via Web).
     */
    protected function criadoPorLabel(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->manager?->name ?? 'Cliente via Web',
        );
    }

    // =========================================================================
    // 💰 MÓDULO FINANCEIRO
    // =========================================================================

    /**
     * Relacionamento: Uma reserva tem várias transações financeiras.
     */
    public function transactions()
    {
        return $this->hasMany(FinancialTransaction::class);
    }

    /**
     * Calcula quanto falta o cliente pagar.
     * Lógica: (Preço Final ou Preço Original) - Total Pago
     */
    public function getRemainingAmountAttribute(): float
    {
        $total = $this->final_price ?? $this->price;
        return max(0, $total - $this->total_paid);
    }

    /**
     * Verifica se a reserva está totalmente quitada.
     */
    public function getIsPaidAttribute(): bool
    {
        return $this->payment_status === 'paid' || $this->remaining_amount <= 0;
    }
}
