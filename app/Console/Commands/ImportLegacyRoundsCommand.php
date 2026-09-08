<?php

namespace App\Console\Commands;

use App\Services\Legacy\LegacyRoundsImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportLegacyRoundsCommand extends Command
{
    protected $signature = 'rdi:import-legacy-rounds
                            {path : Ruta al XML (absoluta o relativa al proyecto)}
                            {--area= : ID del área destino}
                            {--create-area= : Crear o reutilizar área con este nombre}
                            {--force : Recrear recorridos con el mismo título}';

    protected $description = 'Importa recorridos y cuestionarios desde un XML exportado del RDI v1';

    public function handle(LegacyRoundsImporter $importer): int
    {
        $path = (string) $this->argument('path');

        if (! str_starts_with($path, DIRECTORY_SEPARATOR) && ! preg_match('/^[A-Za-z]:\\\\/', $path)) {
            $path = base_path($path);
        }

        $areaOption = $this->option('area');
        $areaId = filled($areaOption) ? (int) $areaOption : null;
        $createArea = filled($this->option('create-area'))
            ? (string) $this->option('create-area')
            : null;

        try {
            $result = $importer->import(
                absolutePath: $path,
                areaId: $areaId,
                createAreaName: $createArea,
                force: (bool) $this->option('force'),
            );
        } catch (Throwable $exception) {
            $this->error('No se pudo importar: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Importación completada.');
        $this->line("Área ID: {$result['area_id']}");
        $this->line("Importados: {$result['imported']}");
        $this->line("Omitidos: {$result['skipped']}");
        $this->line("Recreados (--force): {$result['forced']}");

        return self::SUCCESS;
    }
}
