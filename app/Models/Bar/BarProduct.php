<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarProduct extends Model
{
    protected $table = 'bar_products';

    // Adicionamos 'bar_category_id' para permitir o salvamento da categoria
    protected $fillable = [
        'bar_category_id',
        'barcode',
        'name',
        'purchase_price',
        'sale_price',
        'stock_quantity',
        'min_stock',
        'is_active',
        'unit_type',        // Adicione este
        'content_quantity'  // Adicione este
    ];

    /**
     * Relacionamento: Um produto pertence a uma categoria.
     * Isso permite usar $product->category->name na sua listagem.
     */
    public function category()
    {
        return $this->belongsTo(BarCategory::class, 'bar_category_id');
    }
}
