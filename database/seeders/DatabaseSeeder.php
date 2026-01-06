<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // RoleUserSeeder::class, // Caso você use permissões primeiro
            UserSeeder::class,      // Seeder que criamos com Maia e Marcos
        ]);
    }
}