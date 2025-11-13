<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    /**
     * Define explicitamente o nome da tabela (embora 'schedules' seja o padrão).
     */
    protected $table = 'schedules';

    /**
     * Os atributos que são mass assignable (seguros para serem preenchidos em massa).
     */
    protected $fillable = [
        'date', // <--- CORREÇÃO 1: ESSENCIAL para o agendamento avulso
        'day_of_week',
        'start_time',
        'end_time',
        'price',
        'is_active',
    ];

    /**
     * Os atributos que devem ser convertidos para tipos nativos.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'price' => 'decimal:2',
    ];

    /**
     * Retorna o nome do dia da semana (Helper usado na View).
     * Mapeia os índices de 0 (Domingo) a 6 (Sábado).
     */
    public function getDayName(int $dayOfWeek = null): string
    {
        // Usa a propriedade do objeto se o argumento não for passado
        $dayOfWeek = $dayOfWeek ?? $this->day_of_week;

        return match ($dayOfWeek) {
            0 => 'Domingo',         // 🚨 CORREÇÃO 2: Domingo é 0
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            default => 'Dia Inválido',
        };
    }
}
