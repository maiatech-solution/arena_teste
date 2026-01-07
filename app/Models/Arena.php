<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Arena extends Model
{
    use HasFactory;

    // 🎯 ADICIONE ESTA LINHA:
    protected $fillable = ['name', 'is_active'];

    /**
     * Relacionamento com as configurações de horário
     */
    public function configurations()
    {
        return $this->hasMany(ArenaConfiguration::class);
    }
}