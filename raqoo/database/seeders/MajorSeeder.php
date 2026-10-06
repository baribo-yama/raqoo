<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\Department;
use App\Models\Major;
use Illuminate\Database\Seeder;

class MajorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 学科 => 専攻
        $majorsByDepartment = [
            'AI&テクノロジー科' => ['AIエンジニア専攻', 'ホワイトハッカー専攻'],
            'デジタルテクノロジー科' => ['ITエンジニア専攻'],
        ];

        foreach ($majorsByDepartment as $departmentName => $majorNames){
            $department = Department::where('name', $departmentName) -> first();

            if(!$department){
                continue;
            }

            foreach ($majorNames as $majorName){
                Major::create([
                    'name' => $majorName,
                    'department_id' => $department->id,
                ]);
            }
        }
    }
}
