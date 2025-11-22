<?php

namespace App\Models;

// ➡️ IMPORTAÇÕES NECESSÁRIAS
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute; // Para o Accessor e Mutator

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
        'customer_qualification',
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
        'is_vip' => 'boolean',
        'is_blocked' => 'boolean',
        // O campo 'customer_qualification' é uma string, mas é bom garantir
        'customer_qualification' => 'string', 
    ];

    // =========================================================================
    // ⚙️ ACCESSOR/MUTATOR COMBINADO (no_show_count)
    // =========================================================================

    /**
     * Accessor/Mutator para no_show_count.
     * Define o no_show_count E atualiza customer_qualification e is_blocked 
     * de forma atômica no momento em que o valor é setado no modelo.
     */
    protected function noShowCount(): Attribute
    {
        return Attribute::make(
            set: function (int $value) {
                $qualification = 'normal';
                $isBlocked = false;

                // 1. LÓGICA DE QUALIFICAÇÃO E BLOQUEIO
                if ($value >= 2) {
                    // Duas ou mais faltas resultam em bloqueio
                    $qualification = 'bloqueado'; 
                    $isBlocked = true;
                } elseif ($value === 1) {
                    // Uma falta apenas qualifica, mas não bloqueia
                    $qualification = 'faltou_antes';
                }

                // 2. RETORNA UM ARRAY COM TODOS OS CAMPOS A SEREM ATUALIZADOS
                return [
                    'no_show_count' => $value,
                    'customer_qualification' => $qualification,
                    'is_blocked' => $isBlocked, 
                ];
            },
            get: fn (int $value) => $value, // Retorna o valor lido do DB
        );
    }
    
    // =========================================================================
    // ✅ ACCESSORS E MÉTODOS DE LEITURA (VISUAL)
    // =========================================================================

    /**
     * Accessor que define o texto e estilo da tag de status visível na UI,
     * centralizando a lógica de is_blocked, is_vip e faltas.
     * Uso: {!! $reserva->user->status_tag !!}
     */
    protected function statusTag(): Attribute
    {
        return Attribute::make(
            get: function () {
                // 1. Prioridade Máxima: BLOQUEADO (por falta ou manual)
                if ($this->is_blocked) {
                    return '<span class="px-2 py-1 text-xs font-bold text-white bg-red-600 rounded-full" title="Cliente Bloqueado">
                                🚫 BLACKLIST
                            </span>';
                }

                // 2. Prioridade Alta: VIP
                if ($this->is_vip) {
                    return '<span class="px-2 py-1 text-xs font-bold text-white bg-green-500 rounded-full" title="Bom Pagador / VIP">
                                ⭐ VIP
                            </span>';
                }

                // 3. Status de Alerta: Já faltou uma vez
                if ($this->customer_qualification === 'faltou_antes') {
                    return '<span class="px-2 py-1 text-xs font-bold text-yellow-900 bg-yellow-300 rounded-full" title="Faltou uma vez">
                                ⚠️ FALTOU ANTES
                            </span>';
                }

                // 4. Status Padrão: Normal (0 faltas)
                return '<span class="px-2 py-1 text-xs font-bold text-gray-900 bg-gray-300 rounded-full">
                            NORMAL
                        </span>';
            }
        );
    }

    /**
     * Accessor para Reputação Qualificada, lendo o valor já calculado no DB.
     */
    protected function customerQualification(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value) => $value,
        );
    }

    // ... métodos de relacionamento e outros accessors originais aqui ...

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

    /**
     * Formata o contato de WhatsApp (adiciona a máscara).
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
    
    // Métodos de funcionalidade
    public function requiresSignal(): bool
    {
        return !$this->is_vip;
    }

    public function canSchedule(): bool
    {
        return !$this->is_blocked;
    }

    public function isGoodPayer(): bool
    {
        return $this->is_vip;
    }

    /**
     * Verifica se o usuário está na lista negra.
     */
    public function isBlacklisted(): bool
    {
        return $this->is_blocked;
    }
}