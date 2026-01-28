<?php

namespace App\Models\Bar;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class BarCashSession extends Model
{
    protected $table = 'bar_cash_sessions';
    protected $fillable = ['user_id', 'opening_balance', 'closing_balance', 'status', 'opened_at', 'closed_at'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
