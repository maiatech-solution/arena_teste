<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinancialTransaction extends Model
{
    use HasFactory;

    /**
     * ✅ NOVO: Constantes de Tipo de Transação (Alinhadas com ReservaController)
     */
    public const TYPE_SIGNAL = 'signal';
    public const TYPE_PAYMENT = 'payment'; // Pagamento final/parcial
    // Constantes de Retenção/Compensação
    public const TYPE_RETEN_NOSHOW_COMP = 'RETEN_NOSHOW_COMP';
    public const TYPE_RETEN_CANC_COMP = 'RETEN_CANC_COMP';
    public const TYPE_RETEN_CANC_P_COMP = 'RETEN_CANC_P_COMP';
    public const TYPE_RETEN_CANC_S_COMP = 'RETEN_CANC_S_COMP';
    // Estorno não precisa de constante de entrada, pois a lógica é a exclusão do 'signal'

    protected $fillable = [
        'reserva_id',
        'user_id',      // Cliente que pagou
        'manager_id',   // Gestor que recebeu/registrou
        'amount',
        'type',         // 'signal', 'payment', 'RETEN_NOSHOW_COMP', etc.
        'payment_method',// 'pix', 'money', 'card', 'retained_funds', etc.
        'transaction_code',
        'description',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
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