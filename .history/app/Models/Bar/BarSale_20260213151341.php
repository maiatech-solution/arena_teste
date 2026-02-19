<?php

namespace App\Models\Bar;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class BarSale extends Model
{
    use HasFactory;

    protected $table = 'bar_sales';

    // 🔥 Adicionado bar_cash_session_id para permitir a gravação
    protected $fillable = [
        'user_id',
        'total_value',
        'payment_method',
        'status',
        'bar_cash_session_id'
    ];

    public function items()
    {
        return $this->hasMany(BarSaleItem::class, 'bar_sale_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
