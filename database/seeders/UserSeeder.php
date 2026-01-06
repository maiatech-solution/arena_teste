<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela para não duplicar IDs ao rodar o comando
        User::truncate();

        // 1. Usuário Maia (Admin)
        User::create([
            'id' => 1,
            'name' => 'Maia',
            'email' => 'drikomaia89@gmail.com',
            'whatsapp_contact' => '00000000000', // Adicione o número real se tiver
            'role' => 'admin',
            'password' => '$2y$12$4hDmdJBwslgcOVI46jD0CuL2UPs8WtfAMa6S/XF1yVe2m6tfk7aia',
            'is_vip' => true,
            'is_blocked' => false,
            'no_show_count' => 0,
            'created_at' => '2025-11-04 21:29:39',
            'updated_at' => '2025-11-04 21:29:39',
        ]);

        // 2. Usuário Marcos (Gestor)
        User::create([
            'id' => 2,
            'name' => 'Marcos Barroso Leal',
            'email' => 'marcosbleal26@gmail.com',
            'whatsapp_contact' => '00000000000',
            'role' => 'gestor',
            'password' => '$2y$12$CQP/cASvWu48N7qY4lSeQuHW1NgFnVlFNknx.rOSENf0..GuAuUIa',
            'is_vip' => true,
            'is_blocked' => false,
            'no_show_count' => 0,
            'created_at' => '2025-11-11 00:59:35',
            'updated_at' => '2025-11-11 00:59:35',
        ]);
    }
}