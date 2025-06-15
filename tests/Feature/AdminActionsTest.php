<?php
declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class AdminActionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Farmer', 'guard_name' => 'web']);
    }

    public function test_admin_can_create_farmer(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Sanctum::actingAs($admin);

        $data = [
            'name' => 'Test Farmer',
            'email' => 'farmer@example.com',
            'password' => 'password123',
        ];

        $response = $this->postJson('/api/admin/create-farmer', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('users', ['email' => 'farmer@example.com']);
    }

    public function test_non_admin_cannot_create_farmer(): void
    {
        $farmer = User::factory()->create();
        $farmer->assignRole('Farmer');

        Sanctum::actingAs($farmer);

        $response = $this->postJson('/api/admin/create-farmer', [
            'name' => 'Another Farmer',
            'email' => 'another@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_access_user_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response->assertStatus(200);
    }

    public function test_farmer_cannot_access_user_list(): void
    {
        $farmer = User::factory()->create();
        $farmer->assignRole('Farmer');

        Sanctum::actingAs($farmer);

        $response = $this->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_create_farmer_validation_failure(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/admin/create-farmer', [
            'name' => 'Invalid Farmer',
            // email missing
            'password' => 'password123',
        ]);

        $response->assertStatus(500);
    }
}
