<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Http\Controllers\Admin\FinanceiroController;
use Carbon\Carbon;

class FinancialTransaction extends Model
{
    use HasFactory;

    /**
     * ✅ Constantes de Tipo de Transação
     * Centralizar aqui evita erros de digitação nas Controllers
     */
    public const TYPE_SIGNAL = 'signal';
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';
    public const TYPE_NO_SHOW_PENALTY = 'no_show_penalty';

    // Constantes de retenção específicas
    public const TYPE_RETEN_NOSHOW_COMP = 'RETEN_NOSHOW_COMP';
    public const TYPE_RETEN_CANC_COMP = 'RETEN_CANC_COMP';
    public const TYPE_RETEN_CANC_P_COMP = 'RETEN_CANC_P_COMP';
    public const TYPE_RETEN_CANC_S_COMP = 'RETEN_CANC_S_COMP';

    protected $fillable = [
        'reserva_id',
        'arena_id',
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
     * 🛡️ TRAVA DE SEGURANÇA GLOBAL
     * Impede criação de transação em caixa fechado, agindo como um "trigger" de aplicação.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            // 1. Ignora se for via console (Seeds, Migrations, etc)
            if (app()->runningInConsole()) return;

            // 2. Determina a data da transação com precisão
            $dateToCheck = $transaction->paid_at
                ? \Carbon\Carbon::parse($transaction->paid_at)->toDateString()
                : now()->toDateString();

            // 3. Verificação Direta via Model (Ajustado para Multiquadras)
            // 🎯 AQUI ESTAVA O ERRO: Adicionamos o filtro da arena_id
            $isClosed = \App\Models\Cashier::where('date', $dateToCheck)
                ->where('arena_id', $transaction->arena_id) // Filtra pela arena da transação
                ->where('status', 'closed')
                ->exists();

            if ($isClosed) {
                $formattedDate = \Carbon\Carbon::parse($dateToCheck)->format('d/m/Y');
                throw new \Exception("Bloqueio de Segurança: O caixa desta arena para o dia {$formattedDate} já está encerrado. Reabra-o para lançar movimentações.");
            }
        });
    }

    /**
     * ✅ RELAÇÕES
     */

    public function arena(): BelongsTo
    {
        return $this->belongsTo(Arena::class);
    }

    // withDefault evita erro de "tentar ler propriedade de nulo" se a reserva for deletada
    public function reserva(): BelongsTo
    {
        return $this->belongsTo(Reserva::class)->withDefault([
            'client_name' => 'Reserva Excluída/Finalizada',
            'id' => 'N/D'
        ]);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    /**
     * ✅ HELPER DE SCOPE
     * Facilita pegar apenas entradas ou apenas saídas (estornos)
     */
    public function scopeCredits($query)
    {
        return $query->where('amount', '>', 0);
    }
    public function scopeDebits($query)
    {
        return $query->where('amount', '<', 0);
    }
}
