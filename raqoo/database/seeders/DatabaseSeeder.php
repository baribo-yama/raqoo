<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ほかのseederをここから呼び出す（RoomSeederとか)
        $this->call([
            RoomSeeder::class,
            AdminSeeder::class,
            UserSeeder::class,
        ]);
    }
}
