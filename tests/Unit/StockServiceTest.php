<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\StockService;
use App\Models\User;
use App\Models\StockCondition;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;

class StockServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Farmer', 'guard_name' => 'web']);
        $this->service = new StockService();
    }

    public function test_create_stock_sets_user_id(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Farmer');
        Sanctum::actingAs($user);

        $data = StockCondition::factory()->make()->toArray();

        $stock = $this->service->createStock($data);

        $this->assertSame($user->id, $stock->user_id);
        $this->assertNotNull($stock->last_updated);
        $this->assertDatabaseHas('stock_conditions', ['id' => $stock->id]);
    }

    public function test_update_stock_modifies_record(): void
    {
        $user = User::factory()->create();
        $user->assignRole('Farmer');
        $stock = StockCondition::factory()->for($user)->create();

        $updated = $this->service->updateStock($stock, [
            'temperature' => 18,
            'humidity' => 50,
        ]);

        $this->assertSame(18.0, $updated->temperature);
        $this->assertSame(50.0, $updated->humidity);
        $this->assertDatabaseHas('stock_conditions', [
            'id' => $stock->id,
            'temperature' => 18.0,
            'humidity' => 50.0,
        ]);
    }
}
