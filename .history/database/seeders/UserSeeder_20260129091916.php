<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Desativa restrições de chave estrangeira para poder limpar a tabela com segurança
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Schema::enableForeignKeyConstraints();

        // 1. Usuário Maia (Admin)
        User::create([
            'id' => 1,
            'name' => 'Maia',
            'email' => 'drikomaia89@gmail.com',
            'whatsapp_contact' => null,
            'role' => 'admin',
            'is_vip' => 0,
            'is_blocked' => 0,
            'no_show_count' => 0,
            'customer_qualification' => 'normal',
            'email_verified_at' => null,
            'password' => '$2y$12$4hDmdJBwslgcOVI46jD0CuL2UPs8WtfAMa6S/XF1yVe2m6tfk7aia',
            'created_at' => '2025-11-04 21:29:39',
            'updated_at' => '2025-11-04 21:29:39',
        ]);

        // 2. Usuário Marcos Barroso Leal (Gestor)
        User::create([
            'id' => 2,
            'name' => 'Marcos Barroso Leal',
            'email' => 'marcosbleal26@gmail.com',
            'whatsapp_contact' => null,
            'role' => 'admin',
            'is_vip' => 0,
            'is_blocked' => 0,
            'no_show_count' => 0,
            'customer_qualification' => 'normal',
            'email_verified_at' => null,
            'password' => '$2y$12$CQP/cASvWu48N7qY4lSeQuHW1NgFnVlFNknx.rOSENf0..GuAuUIa',
            'created_at' => '2025-11-11 00:59:35',
            'updated_at' => '2025-11-11 00:59:35',
        ]);

        $this->command->info('Usuários administrativos criados com sucesso!');
    }
}