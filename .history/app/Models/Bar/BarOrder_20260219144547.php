<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BarOrder extends Model
{
    protected $table = 'bar_orders';

    protected $fillable = [
        'bar_table_id',
        'user_id',
        'total_value',
        'status',
        'closed_at',
        'bar_cash_session_id'
    ];

    public function table()
    {
        return $this->belongsTo(BarTable::class, 'bar_table_id');
    }

    public function items()
    {
        return $this->hasMany(BarOrderItem::class);
    }

    // Você já tem esta:
    public function waiter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // 🔴 ADICIONE ESTA AQUI (Para o Controller parar de dar erro):
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
