<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarService extends Model
{
    use HasFactory;

    protected $table = 'bar_services';

    protected $fillable = [
        'name',
        'description',
        'price',
        'status'
    ];

    /**
     * Relacionamento reverso: O serviço pode estar em vários itens de venda.
     */
    public function saleItems()
    {
        return $this->morphMany(BarSaleItem::class, 'itemable');
    }
}