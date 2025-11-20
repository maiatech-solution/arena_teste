<?php

namespace App\Models;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Para o Accessor

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
        'password',
        'role', // 'admin', 'gestor', 'cliente'
        'no_show_count',
        'is_vip',
        'is_blocked',
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
        // 'data_nascimento' foi removido daqui
        'is_vip' => 'boolean',
        'is_blocked' => 'boolean',
    ];

    // =========================================================================
    // ✅ MÉTODOS DE RELACIONAMENTO
    // =========================================================================

    /**
     * Obtém todas as reservas associadas a este usuário.
     */
    public function reservas(): HasMany
    {
        // Supondo que você tenha um modelo 'Reserva'
        return $this->hasMany(Reserva::class, 'user_id');
    }

    // =========================================================================
    // ✅ ACCESSORS PARA AUTORIZAÇÃO
    // =========================================================================

    /**
     * Verifica se o usuário tem a role 'gestor' ou 'admin'.
     */
    protected function isGestor(): Attribute
    {
        return Attribute::make(
            get: fn () => in_array($this->role, ['gestor', 'admin']),
        );
    }

    /**
     * Verifica se o usuário tem a role 'cliente'.
     */
    protected function isClient(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->role === 'cliente',
        );
    }

    // =========================================================================
    // ✅ ACCESSOR PARA FORMATAR CONTATO
    // =========================================================================
    /**
     * Formata o contato de WhatsApp (adiciona a máscara).
     * Ex: 11988887777 -> (11) 9 8888-7777
     */
    protected function formattedWhatsappContact(): Attribute
    {
        return Attribute::make(
            get: function (mixed $value, array $attributes) {
                $contact = preg_replace('/\D/', '', $attributes['whatsapp_contact']);
                $length = strlen($contact);

                if ($length === 11) {
                    return '('.substr($contact, 0, 2).') '.substr($contact, 2, 1).' '.substr($contact, 3, 4).'-'.substr($contact, 7, 4);
                } elseif ($length === 10) {
                    return '('.substr($contact, 0, 2).') '.substr($contact, 2, 4).'-'.substr($contact, 6, 4);
                }

                return $attributes['whatsapp_contact'];
            },
        );
    }
    //NOVA IMPLEMENTAÇÃO
    public function requiresSignal(): bool
    {
        return !$this->is_vip; // Se for VIP, retorna false (não precisa de sinal)
    }

    // Verifica se está na Blacklist
    public function canSchedule(): bool
    {
    return !$this->is_blocked;
    }

    // =========================================================================
    // ⭐ REPUTAÇÃO E FINANCEIRO
    // =========================================================================

    /**
     * Define se o usuário é "Bom Pagador" (VIP).
     * Pode ser setado manualmente ou calculado com base no histórico.
     */
    public function isGoodPayer(): bool
    {
        return $this->is_vip;
    }

    /**
     * Verifica se o usuário está na lista negra.
     */
    public function isBlacklisted(): bool
    {
        // Exemplo: Bloqueado manualmente OU mais de 3 faltas
        return $this->is_blocked || $this->no_show_count >= 3;
    }
}
