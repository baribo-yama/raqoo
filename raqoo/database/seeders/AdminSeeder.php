<?php

namespace Database\Seeders;

use App\Models\Admin;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 管理者アカウントを作成
        Admin::create([
            'email' => 'admin@raqoo.com',
            'password' => Hash::make('admin123'),
        ]);

        // 追加の管理者アカウント（必要に応じて）
        Admin::create([
            'email' => 'manager@raqoo.com',
            'password' => Hash::make('manager123'),
        ]);
    }
}
