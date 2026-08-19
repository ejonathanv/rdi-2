<?php

namespace App\Notifications\Concerns;

use App\Models\User;
use Illuminate\Notifications\Notification;
use NotificationChannels\WebPush\WebPushChannel;
use NotificationChannels\WebPush\WebPushMessage;

trait RoutesOperationalAlert
{
    /**
     * @return list<string|class-string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['database'];

        if (filled(config('webpush.vapid.public_key')) && filled(config('webpush.vapid.private_key'))) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    public function toWebPush(object $notifiable, Notification $notification): WebPushMessage
    {
        /** @var array{title: string, body: string, url: string, type: string} $data */
        $data = $this->toArray($notifiable);

        return (new WebPushMessage)
            ->title($data['title'])
            ->body($data['body'])
            ->icon('/img/favicon.png')
            ->data([
                'url' => $data['url'],
                'type' => $data['type'],
            ])
            ->options(['TTL' => 60 * 60]);
    }

    protected function resolveUrl(object $notifiable, string $managerUrl): string
    {
        if ($notifiable instanceof User && ! $notifiable->canViewAnyAreaOperations()) {
            return $notifiable->homePath();
        }

        return $managerUrl;
    }
}
