<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BarOrder extends Model
{
    protected $table = 'bar_orders';
    protected $fillable = ['bar_table_id', 'user_id', 'total_value', 'status', 'closed_at'];

    public function table()
    {
        return $this->belongsTo(BarTable::class, 'bar_table_id');
    }

    public function items()
    {
        return $this->hasMany(BarOrderItem::class);
    }

    public function waiter()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
