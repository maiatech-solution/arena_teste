<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{

    use HasFactory;

    // 🛑 ADICIONADO: Força o uso do fuso horário brasileiro para a leitura e gravação.
    // Isso deve resolver o problema de desalinhamento de 1 dia na exibição.
    protected $timezone = 'America/Sao_Paulo';

    protected $fillable = [
        'reserva_id',
        'user_id',       // Cliente que pagou
        'manager_id',    // Gestor que recebeu/registrou
        'amount',
        'type',          // 'signal', 'remaining', 'full', 'refund'
        'payment_method',// 'pix', 'money', 'card', 'debit'
        'transaction_code',
        'description',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        // O cast 'datetime' agora usará o $timezone definido acima para exibir o paid_at corretamente.
        'paid_at' => 'datetime',
    ];

    // Relação: Transação pertence a uma Reserva
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class);
    }

    // Relação: Quem pagou (Cliente)
    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relação: Quem registrou (Gestor)
    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
