<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarProduct extends Model
{
    protected $table = 'bar_products'; // Forçamos o prefixo
    protected $fillable = ['barcode', 'name', 'purchase_price', 'sale_price', 'stock_quantity', 'min_stock', 'is_active'];
}
