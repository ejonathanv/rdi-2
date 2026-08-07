<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<IncidentCategory>
 */
class IncidentCategoryFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'area_id' => Area::factory(),
            'name' => Str::title($name),
            'code' => Str::upper(Str::slug($name, '_')),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
