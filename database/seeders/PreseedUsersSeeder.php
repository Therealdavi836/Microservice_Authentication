<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class PreseedUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();
        $users = [];
        $commonPassword = 'Password123!'; // password que usará Locust

        $numUsers = 20; // ajusta según tus pruebas

        for ($i = 1; $i <= $numUsers; $i++) {
            $users[] = [
                'name' => $faker->name,
                'email' => "load_user_{$i}@example.com",
                'password' => Hash::make($commonPassword),
                'role_id' => 3, // por ejemplo: 1=admin,2=vendedor,3=comprador
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('users')->insert($users);
    }
}
