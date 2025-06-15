<?php
declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\StockCondition;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class StockConditionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Farmer', 'guard_name' => 'web']);
    }

    public function test_authenticated_user_can_create_stock_condition(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Farmer');

        Sanctum::actingAs($user);

        $data = StockCondition::factory()->make()->toArray();

        $response = $this->postJson('/api/stocks', $data);

        $response->assertStatus(201)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('stock_conditions', [
            'bean_type' => $data['bean_type'],
            'user_id' => $user->id,
        ]);
    }

    public function test_authenticated_user_can_update_stock_condition(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Farmer');
        $stock = StockCondition::factory()->for($user)->create();

        Sanctum::actingAs($user);

        $update = [
            'temperature' => 22.5,
            'humidity' => 55,
        ];

        $response = $this->putJson("/api/stocks/{$stock->id}", $update);

        $response->assertStatus(200)
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('stock_conditions', [
            'id' => $stock->id,
            'temperature' => 22.5,
            'humidity' => 55,
        ]);
    }
}
