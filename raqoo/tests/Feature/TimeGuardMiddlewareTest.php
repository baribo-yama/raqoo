<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeGuardMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsVerifiedUser(): static
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        return $this->actingAs($user);
    }

    // ==================== 平日 ====================

    /** @test */
    public function 平日の利用可能時間内はホームにアクセスできる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(14, 0));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 平日の開始時刻ちょうど9時10分はアクセスできる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(9, 10));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 平日の終了時刻ちょうど19時30分はアクセスできる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(19, 30));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 平日の9時09分はunavailableにリダイレクトされる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(9, 9));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }

    /** @test */
    public function 平日の19時31分はunavailableにリダイレクトされる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(19, 31));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }

    /** @test */
    public function 平日の深夜0時はunavailableにリダイレクトされる(): void
    {
        $this->travelTo(now()->next('Monday')->setTime(0, 0));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }

    // ==================== 休日 ====================

    /** @test */
    public function 休日の利用可能時間内はホームにアクセスできる(): void
    {
        $this->travelTo(now()->next('Saturday')->setTime(14, 0));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 休日の開始時刻ちょうど10時50分はアクセスできる(): void
    {
        $this->travelTo(now()->next('Saturday')->setTime(10, 50));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 休日の終了時刻ちょうど17時50分はアクセスできる(): void
    {
        $this->travelTo(now()->next('Saturday')->setTime(17, 50));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertStatus(200);
    }

    /** @test */
    public function 休日の10時49分はunavailableにリダイレクトされる(): void
    {
        $this->travelTo(now()->next('Saturday')->setTime(10, 49));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }

    /** @test */
    public function 休日の17時51分はunavailableにリダイレクトされる(): void
    {
        $this->travelTo(now()->next('Saturday')->setTime(17, 51));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }

    /** @test */
    public function 日曜日も休日ルールが適用される(): void
    {
        $this->travelTo(now()->next('Sunday')->setTime(20, 0));

        $response = $this->actingAsVerifiedUser()->get(route('user.home.index'));

        $response->assertRedirect(route('unavailable'));
    }
}
