<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cria os usuários Administradores, Gestores e padroniza Clientes.
     */
    public function run(): void
    {
        // 1. Definição dos Usuários Administradores
        $administradores = [
            [
                'name'     => 'Admin Mestre',
                'email'    => 'admin@arena.com',
                'password' => Hash::make('password'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Adriano Maia',
                'email'    => 'drikomaia89@gmail.com',
                'password' => Hash::make('26157795'),
                'role'     => 'admin',
            ],
            [
                'name'     => 'Marcos Leal',
                'email'    => 'marcosbleal26@gmail.com',
                'password' => Hash::make('12345678'),
                'role'     => 'admin',
            ],
        ];

        // Loop para criar cada administrador
        foreach ($administradores as $admin) {
            User::firstOrCreate(
                ['email' => $admin['email']], // Busca pelo e-mail
                [
                    'name'     => $admin['name'],
                    'password' => $admin['password'],
                    'role'     => $admin['role'],
                ]
            );
        }

        // 2. Cria o Usuário Gestor (role: gestor)
        User::firstOrCreate(
            ['email' => 'gestor@arena.com'],
            [
                'name'     => 'Gestor Operacional',
                'password' => Hash::make('password'),
                'role'     => 'gestor',
            ]
        );

        // 3. Garante que qualquer usuário existente que não tenha role seja cliente
        User::whereNull('role')
            ->orWhere('role', '')
            ->update(['role' => 'cliente']);
    }
}
