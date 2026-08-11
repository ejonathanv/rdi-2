<?php

namespace App\Console\Commands;

use App\Services\DigestMailSender;
use Illuminate\Console\Command;

class SendWeeklyIncidentsDigestCommand extends Command
{
    protected $signature = 'reports:send-weekly-incidents-digest';

    protected $description = 'Envía a contactos el resumen semanal de incidencias (viernes)';

    public function handle(DigestMailSender $sender): int
    {
        $sent = $sender->sendWeeklyIncidents();
        $this->info("Destinatarios notificados: {$sent}");

        return self::SUCCESS;
    }
}
