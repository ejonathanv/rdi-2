<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class IncidentCategorySeeder extends Seeder
{
    /**
     * @var list<string>
     */
    private const array Categories = [
        'NOTA INFORMATIVA',
        'VIOLACION A REGLAMENTO',
        'ACCIDENTE',
        'RIÑA',
        'ABUSO DE CONFIANZA',
        'MAL USO DE INSTALACIONES Y EQ',
        'ROBO',
        'INCENDIO',
        'DERRAME MATERIAL PELIGROSO',
        'DAÑO A PRODUCTO',
        'DAÑO A PROPIEDAD',
        'CONDICION INSEGURA',
        'ACTO INSEGURO',
    ];

    public function run(): void
    {
        Area::query()->each(function (Area $area): void {
            foreach (self::Categories as $name) {
                $code = Str::upper(Str::slug($name, '_'));

                IncidentCategory::query()->firstOrCreate(
                    [
                        'area_id' => $area->id,
                        'code' => $code,
                    ],
                    [
                        'name' => $name,
                        'description' => null,
                        'is_active' => true,
                    ],
                );
            }
        });
    }
}
