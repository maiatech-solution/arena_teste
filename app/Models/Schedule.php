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
     * ESSENCIAL para o Schedule::create() no Controller.
     */
    protected $fillable = [
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
     * O $dayOfWeek é opcional; se não for passado, usa a propriedade do modelo.
     */
    public function getDayName(int $dayOfWeek = null): string
    {
        // Usa a propriedade do objeto se o argumento não for passado
        $dayOfWeek = $dayOfWeek ?? $this->day_of_week;

        return match ($dayOfWeek) {
            1 => 'Segunda-feira',
            2 => 'Terça-feira',
            3 => 'Quarta-feira',
            4 => 'Quinta-feira',
            5 => 'Sexta-feira',
            6 => 'Sábado',
            7 => 'Domingo',
            default => 'Dia Inválido',
        };
    }
}
