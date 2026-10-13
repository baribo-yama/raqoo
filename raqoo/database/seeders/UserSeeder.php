<?php

namespace Database\Seeders;

use App\Models\Major;
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
            'family_name' => 'ばやし',
            'email' => 'bayashi@example.com',
            'password' => Hash::make('123456'),
            'student_id' => 'ST001',
            'major_id' => Major::where('name', 'AIエンジニア専攻')->firstOrFail()->id,
        ]);

        User::create([
            'family_name' => '佐藤',
            'first_name' => '花子',
            'email' => 'sato@example.com',
            'password' => Hash::make('password123'),
            'student_id' => 'ST002',
            'major_id' => Major::where('name', 'CG・アニメーション専攻')->firstOrFail()->id,
        ]);

        User::create([
            'family_name' => '山田',
            'first_name' => '次郎',
            'email' => 'yamada@example.com',
            'password' => Hash::make('password123'),
            'student_id' => 'ST003',
            'major_id' => Major::where('name', 'ホワイトハッカー専攻')->firstOrFail()->id,
        ]);

        // より多くのテストユーザーを作成したい場合はFactoryを使用
        // User::factory(10)->create();
    }
}
