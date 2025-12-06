<?php

// app/Models/Cashier.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cashier extends Model
{
    use HasFactory;
    
    // Define os campos que podem ser preenchidos via updateOrCreate no controller
    protected $fillable = [
        'date',
        'calculated_amount',
        'actual_amount',
        'status',
        'closed_by_user_id',
        'closing_time',
    ];

    protected $casts = [
        'date' => 'date',
        'closing_time' => 'datetime',
    ];

    // Relacionamento opcional (Quem fechou o caixa)
    public function closer()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }
}