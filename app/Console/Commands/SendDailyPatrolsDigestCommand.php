<?php

namespace App\Console\Commands;

use App\Services\DigestMailSender;
use Illuminate\Console\Command;

class SendDailyPatrolsDigestCommand extends Command
{
    protected $signature = 'reports:send-daily-patrols-digest';

    protected $description = 'Envía a contactos el resumen de recorridos del día';

    public function handle(DigestMailSender $sender): int
    {
        $sent = $sender->sendDailyPatrols();
        $this->info("Destinatarios notificados: {$sent}");

        return self::SUCCESS;
    }
}
