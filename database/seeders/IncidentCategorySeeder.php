<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Support\DefaultIncidentCategoryCatalog;
use Illuminate\Database\Seeder;

class IncidentCategorySeeder extends Seeder
{
    public function run(): void
    {
        Area::query()->each(function (Area $area): void {
            DefaultIncidentCategoryCatalog::importFor($area);
        });
    }
}
