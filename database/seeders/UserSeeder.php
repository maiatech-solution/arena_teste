<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Limpa a tabela antes de inserir para evitar duplicados se rodar o seeder 2x
        // DB::statement('SET FOREIGN_KEY_CHECKS=0;'); // Desative se der erro de chave estrangeira
        User::truncate();

        $users = [
            [
                'id' => 1,
                'name' => 'Maia',
                'email' => 'drikomaia89@gmail.com',
                'role' => 'admin',
                'password' => '$2y$12$4hDmdJBwslgcOVI46jD0CuL2UPs8WtfAMa6S/XF1yVe2m6tfk7aia',
                'created_at' => '2025-11-04 21:29:39',
                'updated_at' => '2025-11-04 21:29:39',
            ],
            [
                'id' => 2,
                'name' => 'Marcos Barroso Leal',
                'email' => 'marcosbleal26@gmail.com',
                'role' => 'gestor',
                'password' => '$2y$12$CQP/cASvWu48N7qY4lSeQuHW1NgFnVlFNknx.rOSENf0..GuAuUIa',
                'created_at' => '2025-11-11 00:59:35',
                'updated_at' => '2025-11-11 00:59:35',
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}