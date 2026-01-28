<?php

namespace App\Models\Bar;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarSale extends Model
{
    use HasFactory;

    // Nome da tabela explicitado para evitar erros com o prefixo do Model
    protected $table = 'bar_sales';

    protected $fillable = [
        'user_id',
        'total_value',
        'payment_method',
        'status'
    ];

    /**
     * Relacionamento: Uma venda possui vários itens
     */
    public function items()
    {
        return $this->hasMany(BarSaleItem::class, 'bar_sale_id');
    }

    /**
     * Relacionamento: Uma venda pertence a um usuário (vendedor)
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
