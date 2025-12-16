<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Cria um usuário Admin e um Gestor padrão para testes.
     */
    public function run(): void
    {
        // 1. Cria o Usuário Administrador (role: admin)
        User::firstOrCreate(
            ['email' => 'admin@arena.com'],
            [
                'name' => 'Admin Mestre',
                'password' => Hash::make('password'), // Senha padrão 'password'
                'role' => 'admin',
            ],
            [
                'name' => 'Marcos Leal',
                'password' => Hash::make('12345678'), 
                'role' => 'admin',
            ],
            [
                'name' => 'Adriano Maia',
                'password' => Hash::make('26157795'), 
                'role' => 'admin',
            ]
        );

        // 2. Cria o Usuário Gestor (role: gestor)
        User::firstOrCreate(
            ['email' => 'gestor@arena.com'],
            [
                'name' => 'Gestor Operacional',
                'password' => Hash::make('password'), // Senha padrão 'password'
                'role' => 'gestor',
            ]
        );

        // 3. Garante que qualquer usuário existente que não tenha role seja cliente (aplica o padrão)
        User::whereNull('role')->orWhere('role', '')->update(['role' => 'cliente']);
    }
}
