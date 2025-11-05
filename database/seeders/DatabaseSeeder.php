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
            // Chame aqui outros Seeders que você já tenha, se houver.
            RoleUserSeeder::class, // Chamando o novo Seeder de permissões
        ]);
    }
}
```

#### Passo 4: Executar o Seeder

Para criar esses usuários automaticamente (e aplicar quaisquer outras seeds):

```bash
php artisan db:seed
