<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reserva extends Model
{
    use HasFactory;

    /**
     * Os atributos que são preenchíveis em massa.
     */
    protected $fillable = [
        'client_name', //  Agora incluso!
        'client_contact', //  CORRIGIDO: O nome exato da coluna!
        'date',
        'start_time',
        'end_time',
        'price',
        'day_of_week',
        'status',
    ];

    // Status inicial padrão é 'pending'
    protected $attributes = [
        'status' => 'pending',
    ];

    // ... (restante do código do Model)
}
