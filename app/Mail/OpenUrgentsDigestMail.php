<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OpenUrgentsDigestMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * @param  array{
     *     area: array{id: int, name: string, code: string},
     *     incidents: list<array{id: int, created_at: string, message: string, status: string, category: string|null, guard: string}>,
     *     visits: list<array{id: int, reviewed_at: string, checkpoint: string, round: string, guard: string, notes: string|null, patrol_id: int, round_id: int}>
     * }  $digest
     */
    public function __construct(public array $digest)
    {
        $this->afterCommit();
    }

    public function envelope(): Envelope
    {
        $area = $this->digest['area']['name'];
        $count = count($this->digest['incidents']) + count($this->digest['visits']);

        return new Envelope(
            subject: "[{$area}] Urgentes pendientes ({$count})",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'mail.open-urgents-digest',
        );
    }
}
