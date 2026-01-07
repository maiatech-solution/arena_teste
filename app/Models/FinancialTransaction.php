<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Http\Controllers\FinanceiroController;
use Carbon\Carbon;

class FinancialTransaction extends Model
{
    use HasFactory;

    /**
     * ✅ Constantes de Tipo de Transação
     */
    public const TYPE_SIGNAL = 'signal';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund'; // ✅ NOVO: Para estornos/devoluções
    public const TYPE_RETEN_NOSHOW_COMP = 'RETEN_NOSHOW_COMP';
    public const TYPE_RETEN_CANC_COMP = 'RETEN_CANC_COMP';
    public const TYPE_RETEN_CANC_P_COMP = 'RETEN_CANC_P_COMP';
    public const TYPE_RETEN_CANC_S_COMP = 'RETEN_CANC_S_COMP';

    protected $fillable = [
        'reserva_id',
        'arena_id',    // ✅ ADICIONADO: Agora permite gravar o ID da quadra
        'user_id',
        'manager_id',
        'amount',
        'type',
        'payment_method',
        'transaction_code',
        'description',
        'paid_at'
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    /**
     * 🛡️ TRAVA DE SEGURANÇA: Impede criação de transação em caixa fechado
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // Instancia o controller para usar a lógica de verificação
            $financeiro = app(FinanceiroController::class);

            // Define a data a ser checada (se não houver paid_at, usa a data atual)
            $dateToCheck = $transaction->paid_at
                ? Carbon::parse($transaction->paid_at)->toDateString()
                : now()->toDateString();

            if ($financeiro->isCashClosed($dateToCheck)) {
                // Cancela a operação e lança erro
                throw new \Exception("Bloqueio de Segurança: O caixa do dia " . Carbon::parse($dateToCheck)->format('d/m/Y') . " já está encerrado. Reabra-o para lançar movimentações.");
            }
        });
    }

    // ✅ NOVO: Relação com a Arena (Quadra)
    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

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