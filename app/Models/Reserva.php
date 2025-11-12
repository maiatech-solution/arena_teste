<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute; // NOVO: Para usar o formato moderno de Accessors/Mutators
use App\Models\User; // Necessário para as relações com User

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
     * Incluindo os campos de recorrência.
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
     * Incluindo is_fixed.
     */
    protected $casts = [
        'date' => 'date',
        'is_fixed' => 'boolean', // Conversão para booleano
    ];

    // ------------------------------------------------------------------------
    // SCOPES LOCAIS (CORREÇÃO DA DISPONIBILIDADE)
    // ------------------------------------------------------------------------

    /**
     * Scope local para retornar todas as reservas que ESTÃO OCUPANDO um horário.
     * CRÍTICO: Este scope exclui as reservas que foram canceladas, rejeitadas ou expiradas.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $checkDate Data que você deseja verificar (Ex: '2025-11-20')
     * @param string $checkStartTime Hora de início do slot (Ex: '10:00:00')
     * @param string $checkEndTime Hora de fim do slot (Ex: '11:00:00')
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIsOccupied($query, string $checkDate, string $checkStartTime, string $checkEndTime)
    {
        // CORREÇÃO: Deve incluir PENDENTE e CONFIRMADA para ser considerado "ocupado".
        return $query->where('date', $checkDate) // 1. Filtra pela data específica
            ->whereIn('status', [self::STATUS_CONFIRMADA, self::STATUS_PENDENTE]) // 2. FILTRO CRÍTICO: Inclui Pendente e Confirmada
            ->where(function ($q) use ($checkStartTime, $checkEndTime) {
                // 3. Lógica de sobreposição de tempo (usando as strings de hora 'HH:MM:SS')
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

    /**
     * Relação com a regra de horário (Schedule) que originou a reserva.
     * Nota: O modelo Schedule deve ser importado ou estar no namespace correto.
     */
    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }


    // ------------------------------------------------------------------------
    // ACESSORES MODERNOS (Laravel 9/10+)
    // ------------------------------------------------------------------------

    /**
     * Retorna o nome amigável do status (usado nas listas do Admin).
     * Usa o novo formato Attribute::make().
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn () => match ($this->status) {
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
     * Usa o novo formato Attribute::make().
     */
    protected function criadoPorLabel(): Attribute
    {
        return Attribute::make(
            // Se manager_id estiver preenchido, retorna o nome do gestor associado.
            // Caso contrário, retorna 'Cliente via Web'.
            get: fn () => $this->manager?->name ?? 'Cliente via Web',
        );
    }
}
