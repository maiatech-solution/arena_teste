<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Schedule;

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
        'status',

        // 🔑 NOVO: Permitir preenchimento do ID do Gestor que criou a reserva manual
        'manager_id',

        // 💡 NOVOS CAMPOS PARA RECORRÊNCIA
        'recurrent_series_id', // ID para agrupar todas as reservas de uma série fixa (ex: 20 semanas)
        'is_recurrent'         // Flag para indicar que esta reserva faz parte de uma série
    ];

    // CRÍTICO: Define o casting para string para evitar a confusão do Eloquent.
    protected $casts = [
        'date' => 'string',
        'start_time' => 'string',
        'end_time' => 'string',
    ];

    /**
     * Relacionamento com o usuário (cliente que fez a reserva).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); // 💡 Explicitando a chave para maior clareza
    }

    /**
     * 🔑 NOVO: Relacionamento com o gestor (usuário) que criou a reserva manual.
     */
    public function manager()
    {
        // Define a relação com o modelo User usando a chave estrangeira 'manager_id'
        return $this->belongsTo(User::class, 'manager_id');
    }


    /**
     * Relacionamento com a regra de horário fixo (Schedule).
     */
    public function schedule()
    {
        return $this->belongsTo(Schedule::class, 'schedule_id');
    }

    /**
     * Acessório para retornar o nome do status em português.
     */
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
