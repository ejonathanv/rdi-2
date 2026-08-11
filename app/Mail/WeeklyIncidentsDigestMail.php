<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WeeklyIncidentsDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     area: array{id: int, name: string, code: string},
     *     period: array{from: string, to: string},
     *     totals: array{total: int, open: int, resolved: int, discarded: int, urgent: int},
     *     incidents: list<array{id: int, created_at: string, message: string, status: string, category: string|null, is_urgent: bool, guard: string}>
     * }  $digest
     */
    public function __construct(public array $digest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $area = $this->digest['area']['name'];

        return new Envelope(
            subject: "[{$area}] Resumen semanal de incidencias",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.weekly-incidents-digest',
        );
    }
}
