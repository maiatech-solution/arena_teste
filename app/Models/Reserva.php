<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // NECESSÁRIO para referenciar o modelo User no Accessor

class Reserva extends Model
{
    use HasFactory;

    // ------------------------------------------------------------------------
    // CONSTANTES DE STATUS
    // ------------------------------------------------------------------------
    public const STATUS_PENDENTE = 'pending';
    public const STATUS_CONFIRMADA = 'confirmed';
    public const STATUS_CANCELADA = 'cancelled';
    public const STATUS_REJEITADA = 'rejected';
    public const STATUS_EXPIRADA = 'expired';

    /**
     * Os atributos que são mass assignable.
     * ATUALIZADO: Incluindo os campos de recorrência.
     */
    protected $fillable = [
        'user_id',
        'schedule_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'client_name',
        'client_contact',
        'notes',
        'status',
        'manager_id', // ID do gestor que criou/confirmou

        // --- Campos para Recorrência ---
        'is_fixed', // Se é uma reserva fixa recorrente
        'day_of_week', // Dia da semana para reservas fixas (0=Dom, 1=Seg, ...)
        'recurrent_series_id', // ID para agrupar a série recorrente
        'week_index', // Índice dentro da série (0 a 51)
    ];

    /**
     * Os atributos que devem ser convertidos (casted) para tipos nativos.
     * ATUALIZADO: Incluindo is_fixed.
     */
    protected $casts = [
        'date' => 'date',
        'is_fixed' => 'boolean', // Conversão para booleano
    ];


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

    /**
     * Relação com a regra de horário (Schedule) que originou a reserva.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }


    // ------------------------------------------------------------------------
    // ACESSORES
    // ------------------------------------------------------------------------

    /**
     * Retorna o nome amigável do status (usado nas listas do Admin).
     */
    public function getStatusTextAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_PENDENTE => 'Pendente',
            self::STATUS_CONFIRMADA => 'Confirmada',
            self::STATUS_CANCELADA => 'Cancelada',
            self::STATUS_REJEITADA => 'Rejeitada',
            self::STATUS_EXPIRADA => 'Expirada',
            default => 'Desconhecido',
        };
    }

    /**
     * Retorna o nome do criador (Gestor ou Cliente via Web).
     * Este é o Accessor que resolve o seu problema de exibição.
     */
    public function getCriadoPorLabelAttribute(): string
    {
        // Se manager_id estiver preenchido, retorna o nome do gestor associado.
        // O operador '?' (nullsafe) garante que não haverá erro se a relação for nula.
        // Caso contrário, retorna 'Cliente via Web'.
        return $this->manager?->name ?? 'Cliente via Web';
    }
}
