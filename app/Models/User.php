<?php

namespace App\Models;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Para o Accessor de Status

class User extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'whatsapp_contact',
        'data_nascimento',
        'password',
        'role', // 'admin', 'gestor', 'cliente'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'data_nascimento' => 'date', // Cast para objeto Carbon
    ];

    // =========================================================================
    // ✅ MÉTODOS DE RELACIONAMENTO
    // =========================================================================

    /**
     * Obtém todas as reservas associadas a este usuário.
     * ⚠️ Nota: Reserva é o nome do seu modelo de agendamento (Reserva.php).
     */
    public function reservas(): HasMany
    {
        return $this->hasMany(Reserva::class, 'user_id');
    }

    // =========================================================================
    // ✅ ACCESSORS PARA AUTORIZAÇÃO (CRÍTICO PARA O BLADE)
    // =========================================================================

    /**
     * Verifica se o usuário tem a role 'gestor' ou 'admin'.
     *
     * 🛑 CORREÇÃO: Accessor permite que a propriedade $user->is_gestor
     * seja usada no Blade.
     */
    protected function isGestor(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->role, ['gestor', 'admin']),
        );
    }

    /**
     * Verifica se o usuário tem a role 'cliente'.
     * Accessor permite que a propriedade $user->is_client seja usada no Blade.
     */
    protected function isClient(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'cliente',
        );
    }

    // =========================================================================
    // ✅ ACCESSOR (Novo: Para formatar o contato com máscara)
    // =========================================================================
    /**
     * Formata o contato de WhatsApp (adiciona a máscara).
     * Ex: 11988887777 -> (11) 9 8888-7777 (Exemplo de SP)
     */
    protected function formattedWhatsappContact(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $contact = preg_replace('/\D/', '', $attributes['whatsapp_contact']);
                $length = strlen($contact);

                if ($length === 11) { // Ex: (DD) 9 XXXX-XXXX
                    return '('.substr($contact, 0, 2).') '.substr($contact, 2, 1).' '.substr($contact, 3, 4).'-'.substr($contact, 7, 4);
                } elseif ($length === 10) { // Ex: (DD) XXXX-XXXX (sem o 9)
                    return '('.substr($contact, 0, 2).') '.substr($contact, 2, 4).'-'.substr($contact, 6, 4);
                }

                return $attributes['whatsapp_contact'];
            },
        );
    }
}
