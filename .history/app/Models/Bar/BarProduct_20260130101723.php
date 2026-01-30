<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarProduct extends Model
{
    protected $table = 'bar_products';

    protected $fillable = [
        'bar_category_id',
        'barcode',
        'name',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'min_stock',
        'is_active',
        'unit_type',
        'content_quantity',
        'manage_stock' // 🚀 ADICIONE ESTE AQUI!
    ];

    /**
     * Relacionamento: Um produto pertence a uma categoria.
     */
    public function category()
    {
        return $this->belongsTo(BarCategory::class, 'bar_category_id');
    }
}
