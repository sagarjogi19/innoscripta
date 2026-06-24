<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class AuthPreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_user_can_register_and_receive_a_token(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])
            ->assertCreated()
            ->assertJsonStructure(['data' => ['user' => ['id', 'name', 'email'], 'token']]);

        $this->assertDatabaseHas('users', ['email' => 'jane@example.com']);
    }

    public function test_preferences_drive_the_personalised_feed(): void
    {
        $tech = Category::factory()->create(['name' => 'Technology', 'slug' => 'technology']);
        $sport = Category::factory()->create(['name' => 'Sport', 'slug' => 'sport']);

        Article::factory()->count(4)->for($tech)->create();
        Article::factory()->count(6)->for($sport)->create();

        Sanctum::actingAs($user = User::factory()->create());

        $this->putJson('/api/preferences', ['categories' => ['Technology']])
            ->assertOk()
            ->assertJsonPath('data.categories', ['technology']);

        $this->getJson('/api/feed')
            ->assertOk()
            ->assertJsonPath('meta.total', 4);
    }

    public function test_feed_requires_authentication(): void
    {
        $this->getJson('/api/feed')->assertUnauthorized();
    }
}
