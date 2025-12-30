<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashier extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'calculated_amount',
        'actual_amount',
        'status', // 'open', 'closed'
        'closed_by_user_id',
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
     * Relacionamento: Usuário que realizou o fechamento.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}
