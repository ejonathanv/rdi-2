<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DailyPatrolsDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     area: array{id: int, name: string, code: string},
     *     date: string,
     *     patrols: list<array{
     *         id: int,
     *         round: string,
     *         guard: string,
     *         status: string,
     *         started_at: string,
     *         finished_at: string|null,
     *         duration_label: string|null,
     *         visits_count: int
     *     }>
     * }  $digest
     */
    public function __construct(public array $digest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $area = $this->digest['area']['name'];
        $date = $this->digest['date'];

        return new Envelope(
            subject: "[{$area}] Recorridos del {$date}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.daily-patrols-digest',
        );
    }
}
