<?php

namespace Database\Seeders;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\Round;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

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
            'phone' => '5512345678',
            'notify_via_whatsapp' => true,
            'notify_via_sms' => false,
        ]);

        $admin->areas()->attach($area->id, ['role' => AreaRole::Admin->value]);
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);
        $contact->areas()->attach($area->id, ['role' => AreaRole::Contact->value]);

        $round = Round::query()->create([
            'area_id' => $area->id,
            'title' => 'Recorrido perimetral',
            'instructions' => 'Recorrer el perímetro de la planta verificando accesos y cercas.',
            'is_active' => true,
        ]);

        $checkpoints = $round->checkpoints()->createMany([
            [
                'name' => 'Entrada principal',
                'instructions' => 'Verificar portón, iluminación y bitácora de acceso.',
                'position' => 1,
                'token' => (string) Str::uuid(),
                'is_active' => true,
            ],
            [
                'name' => 'Almacén',
                'instructions' => 'Revisar candados, sellos y evidencia de manipulación.',
                'position' => 2,
                'token' => (string) Str::uuid(),
                'is_active' => true,
            ],
            [
                'name' => 'Estacionamiento',
                'instructions' => 'Verificar vehículos no autorizados y estado general.',
                'position' => 3,
                'token' => (string) Str::uuid(),
                'is_active' => true,
            ],
        ]);

        $round->contacts()->attach($contact->id);

        $almacen = $checkpoints->firstWhere('name', 'Almacén');

        $question = $almacen->questions()->create([
            'body' => '¿Está el candado de la puerta bien cerrado?',
            'position' => 1,
            'is_active' => true,
        ]);

        $question->options()->createMany([
            ['label' => 'Sí', 'position' => 1],
            ['label' => 'No', 'position' => 2],
            ['label' => 'No sé', 'position' => 3],
        ]);
    }
}
