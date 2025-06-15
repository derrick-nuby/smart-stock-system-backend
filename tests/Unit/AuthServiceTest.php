<?php
declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Services\AuthService;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AuthServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuthService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Role::create(['name' => 'Admin', 'guard_name' => 'web']);
        Role::create(['name' => 'Farmer', 'guard_name' => 'web']);
        $this->service = new AuthService();
    }

    public function test_register_assigns_farmer_role(): void
    {
        $user = $this->service->register([
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'secret',
        ]);

        $this->assertDatabaseHas('users', ['email' => 'john@example.com']);
        $this->assertTrue(Hash::check('secret', $user->password));
        $this->assertTrue($user->hasRole('Farmer'));
    }

    public function test_login_returns_token_and_user_details(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);
        $user->assignRole('Farmer');

        $response = $this->service->login([
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertArrayHasKey('access_token', $response);
        $this->assertSame($user->email, $response['user']['email']);
    }
}
