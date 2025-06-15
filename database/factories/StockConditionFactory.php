<?php
declare(strict_types=1);

namespace Database\Factories;

use App\Models\StockCondition;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockCondition>
 */
class StockConditionFactory extends Factory
{
    protected $model = StockCondition::class;

    public function definition(): array
    {
        return [
            'bean_type' => $this->faker->word(),
            'quantity' => $this->faker->numberBetween(1, 1000),
            'temperature' => $this->faker->randomFloat(1, 10, 40),
            'humidity' => $this->faker->randomFloat(1, 10, 90),
            'status' => $this->faker->randomElement(['Good', 'Warning', 'Critical']),
            'location' => $this->faker->city(),
            'air_condition' => $this->faker->word(),
            'action_taken' => $this->faker->sentence(),
            'last_updated' => now(),
        ];
    }
}
