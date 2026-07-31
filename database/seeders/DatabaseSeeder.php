<?php

namespace Database\Seeders;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $area = Area::query()->create([
            'name' => 'Planta Norte',
            'code' => 'PLANTA-NORTE',
            'location' => 'Monterrey, MX',
            'is_active' => true,
        ]);

        $admin = User::factory()->superAdmin()->create([
            'name' => 'Super Admin',
            'email' => 'admin@example.com',
        ]);

        $guard = User::factory()->create([
            'name' => 'Demo Guard',
            'email' => 'guard@example.com',
        ]);

        $contact = User::factory()->create([
            'name' => 'Demo Contact',
            'email' => 'contact@example.com',
        ]);

        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);
    }
}
