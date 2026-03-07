<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BarSaleItem extends Model
{
    use HasFactory;

    protected $table = 'bar_sale_items';

    protected $fillable = [
        'bar_sale_id',
        'bar_product_id', // Mantido para compatibilidade com histórico antigo
        'itemable_id',    // ID do Produto ou Serviço
        'itemable_type',  // Classe do Model (Product ou Service)
        'quantity',
        'price_at_sale'
    ];

    /**
     * 🧬 RELACIONAMENTO POLIMÓRFICO
     * Este item pode ser um Produto ou um Serviço.
     */
    public function itemable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relacionamento: O item pertence a uma venda.
     */
    public function sale()
    {
        return $this->belongsTo(BarSale::class, 'bar_sale_id');
    }

    /**
     * Mantemos o método product para não quebrar 
     * partes antigas do sistema que ainda o chamam.
     */
    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }
}
