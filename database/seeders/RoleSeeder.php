<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            [
                'name' => 'admin',
                'label' => 'Administrator',
                'description' => 'Acceso completo al sistema',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'seller',
                'label' => 'Seller',
                'description' => 'Puede publicar y gestionar sus vehículos',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'customer',
                'label' => 'Customer',
                'description' => 'Puede explorar vehículos y gestionar compras',
                'created_at' => now(),
                'updated_at' => now()
            ],
            [
                'name' => 'support',
                'label' => 'Technical Support',
                'description' => 'Gestiona consultas y soporte al cliente',
                'created_at' => now(),
                'updated_at' => now()
            ],
        ]);
    }
}
