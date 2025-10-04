<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // テスト用のユーザーアカウントを作成
        User::create([
            'name' => 'ばやし',
            'email' => 'kenta6flat@icloud.com',
            'password' => Hash::make('123456'),
            'student_id' => 'ST001',
        ]);

        User::create([
            'name' => '佐藤花子',
            'email' => 'sato@example.com',
            'password' => Hash::make('password123'),
            'student_id' => 'ST002',
        ]);

        User::create([
            'name' => '山田次郎',
            'email' => 'yamada@example.com',
            'password' => Hash::make('password123'),
            'student_id' => 'ST003',
        ]);

        // より多くのテストユーザーを作成したい場合はFactoryを使用
        // User::factory(10)->create();
    }
}
