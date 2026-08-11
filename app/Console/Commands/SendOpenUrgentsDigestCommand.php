<?php

namespace App\Console\Commands;

use App\Services\DigestMailSender;
use Illuminate\Console\Command;

class SendOpenUrgentsDigestCommand extends Command
{
    protected $signature = 'reports:send-open-urgents-digest';

    protected $description = 'Envía a contactos el digest diario de urgentes pendientes';

    public function handle(DigestMailSender $sender): int
    {
        $sent = $sender->sendOpenUrgents();
        $this->info("Destinatarios notificados: {$sent}");

        return self::SUCCESS;
    }
}
