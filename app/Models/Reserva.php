<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Horario; // Corrigindo para Horario, se Schedule não existir
use App\Models\Schedule; // Mantendo se Schedule for o correto

class Reserva extends Model
{
    use HasFactory;

    // CONSTANTES DE STATUS
    const STATUS_PENDENTE = 'pending';
    const STATUS_CONFIRMADA = 'confirmed';
    const STATUS_CANCELADA = 'cancelled';
    const STATUS_EXPIRADA = 'expired';
    const STATUS_REJEITADA = 'rejected';

    // CAMPOS PREENCHÍVEIS
    protected $fillable = [
        'user_id',
        'schedule_id',
        'date',
        'start_time',
        'end_time',
        'price',
        'client_name',
        'client_contact',
        'notes',
        'status'
    ];

    // CRÍTICO: Define o casting para string para evitar a confusão do Eloquent.
    protected $casts = [
        'date' => 'string',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function schedule()
    {
        // Se você estiver usando o modelo Schedule para horários fixos
        return $this->belongsTo(Schedule::class, 'schedule_id');

        // Se você estiver usando o modelo Horario (como no seu controller)
        // return $this->belongsTo(Horario::class, 'schedule_id');
    }

    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case self::STATUS_PENDENTE:
                return 'Pendente';
            case self::STATUS_CONFIRMADA:
                return 'Confirmada';
            case self::STATUS_CANCELADA:
                return 'Cancelada';
            case self::STATUS_REJEITADA:
                return 'Rejeitada';
            case self::STATUS_EXPIRADA:
                return 'Expirada';
            default:
                return 'Desconhecido';
        }
    }
}
