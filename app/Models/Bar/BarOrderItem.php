<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BarOrderItem extends Model
{
    protected $table = 'bar_order_items';

    protected $fillable = [
        'bar_order_id',
        'bar_product_id', // Legado
        'itemable_id',    // Novo: ID do Produto ou Serviço
        'itemable_type',  // Novo: Model do Produto ou Serviço
        'quantity',
        'unit_price',
        'subtotal'
    ];

    /**
     * 🧬 RELACIONAMENTO POLIMÓRFICO
     * Permite que a comanda aponte para Produto ou Serviço.
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relacionamento legado com Produto.
     */
    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }

    public function order()
    {
        return $this->belongsTo(BarOrder::class, 'bar_order_id');
    }
}
