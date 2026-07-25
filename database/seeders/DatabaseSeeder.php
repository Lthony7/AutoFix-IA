<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AutofixWeek1Seeder::class,
            CatalogoTallerSeeder::class,
            MecanicosTallerSeeder::class,
            ClientesVehiculosTallerSeeder::class,
            AutofixDemoSeeder::class,
        ]);
    }
}
