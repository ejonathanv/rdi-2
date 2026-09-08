<?php

namespace App\Console\Commands;

use App\Services\Legacy\LegacyRoundsExporter;
use Illuminate\Console\Command;
use Throwable;

class ExportLegacyRoundsCommand extends Command
{
    protected $signature = 'rdi:export-legacy-rounds
                            {--path= : Ruta relativa dentro de storage/app}';

    protected $description = 'Exporta recorridos, zonas y cuestionarios del RDI v1 a XML';

    public function handle(LegacyRoundsExporter $exporter): int
    {
        try {
            $result = $exporter->export($this->option('path'));
        } catch (Throwable $exception) {
            $this->error('No se pudo exportar desde la base legacy: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Exportación completada.');
        $this->line("Archivo: {$result['absolute_path']}");
        $this->line("Recorridos: {$result['rounds']}");
        $this->line("Puntos: {$result['checkpoints']}");
        $this->line("Preguntas: {$result['questions']}");

        return self::SUCCESS;
    }
}
