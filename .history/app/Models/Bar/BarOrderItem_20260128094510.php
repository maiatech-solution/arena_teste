<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;

class BarOrderItem extends Model
{
    protected $table = 'bar_order_items';
    protected $fillable = ['bar_order_id', 'bar_product_id', 'quantity', 'unit_price'];

    public function product()
    {
        return $this->belongsTo(BarProduct::class, 'bar_product_id');
    }
}
