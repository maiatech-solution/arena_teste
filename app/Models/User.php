<?php

namespace App\Models;

// ... (outros imports)
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     * Adicione 'role' ao $fillable
     * para que possa ser definido em massa (ex: no registro).
     * Nota: Remova 'role' do $fillable se você quiser forçar a definição em outro lugar (mais seguro).
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role', // Adicionado 'role' aqui
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // =========================================================================
    // NOVO: Define a role como 'gestor' por padrão no momento da criação.
    // Isso garante que se o campo 'role' não for explicitamente passado
    // no array de criação, ele será definido como 'gestor'.
    // =========================================================================
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($user) {
            if (empty($user->role)) {
                $user->role = 'gestor';
            }
        });
    }

    // =========================================================================
    // Helper Methods para checar o role
    // =========================================================================
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function isGestor()
    {
        return $this->role === 'gestor' || $this->role === 'admin';
    }
}
