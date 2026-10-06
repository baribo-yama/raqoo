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
            'AI&テクノロジー科' => ['ホワイトハッカー専攻', 'AIエンジニア専攻', '生成AIクリエーター専攻', 'AIスタートアップ専攻', 'スポーツテック＆AI専攻', 
                                   'ゲームキャラクター＆グラフィック専攻', 'スーパーゲームクリエーター専攻', 'スーパーCG動画クリエーター専攻', 
                                   'イラスト＆CGグラフィック専攻', 'e-sportsマネジメント専攻'
                                   ],
            'デジタルテクノロジー科' => ['ITエンジニア専攻', 'e-sportsプロゲーマー専攻', 'ゲーム実況＆ストリーマー専攻', 'ゲームプログラマー専攻'],
            'クリエイティブデザイン科' => ['コミックイラスト&マンガ専攻', '動画クリエーター専攻', 'CG・アニメーション専攻', 'メタバースクリエーター専攻', 'シナリオ＆コンテンツ企画専攻']
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
