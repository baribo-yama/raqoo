<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 学科
        $departments = [
            'AI&テクノロジー科',
            'デジタルテクノロジー科',
            'クリエイティブデザイン科',
            '音楽ビジネス科',
            '音楽テクノロジー科',
            'スーパーｅエンターテイメント科',
            '総合音楽科（高等課程）',
        ];

        foreach ($departments as $name){
            Department::create(['name' => $name]);
        }
    }
}
