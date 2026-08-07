<?php

namespace Database\Factories;

use App\Models\Area;
use App\Models\PanicAlert;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PanicAlert>
 */
class PanicAlertFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'area_id' => Area::factory(),
            'user_id' => User::factory(),
            'patrol_run_id' => null,
        ];
    }
}
