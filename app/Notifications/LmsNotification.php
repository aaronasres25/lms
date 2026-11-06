<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

/** In-app (database) notification shown in the bell. Stays unread until opened / marked as read. */
class LmsNotification extends Notification
{
    public function __construct(
        public string $title,
        public string $body,
        public string $url,
        public string $icon = 'bell',
        public string $color = 'violet',
        public array $meta = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return array_merge($this->meta, [
            'title' => $this->title, 'body' => $this->body, 'url' => $this->url,
            'icon' => $this->icon, 'color' => $this->color,
        ]);
    }
}
