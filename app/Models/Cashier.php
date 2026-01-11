<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Cashier extends Model
{
    use HasFactory;

    protected $fillable = [
        'arena_id',
        'date',
        'calculated_amount',
        'actual_amount',
        'status',
        'user_id', // ⬅️ Ajustado para bater com sua tabela (item 11 da lista anterior)
        'closing_time',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'closing_time' => 'datetime',
        'calculated_amount' => 'decimal:2',
        'actual_amount' => 'decimal:2',
    ];

    /**
     * Calcula a diferença (quebra) entre o esperado e o real.
     * Positivo: Sobrou dinheiro | Negativo: Faltou dinheiro.
     */
    public function getDifferenceAttribute(): float
    {
        return (float) ($this->actual_amount - $this->calculated_amount);
    }

    /**
     * Verifica se o caixa está lacrado.
     */
    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    /**
     * RELACIONAMENTO: Unidade (Arena) à qual este caixa pertence.
     * Isso resolve o erro "RelationNotFoundException".
     */
    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

    /**
     * RELACIONAMENTO: Usuário que realizou o fechamento.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
