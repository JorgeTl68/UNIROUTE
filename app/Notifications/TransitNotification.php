<?php

namespace App\Notifications;

use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use InvalidArgumentException;

/**
 * Aviso para el centro de notificaciones del estudiante.
 *
 * Se guarda en la tabla notifications y se transmite en vivo por el canal
 * privado del usuario. Los tipos coinciden con los del mockup de S3-08.
 */
class TransitNotification extends Notification
{
    use Queueable;

    public const string ARRIVAL = 'arrival';

    public const string DELAY = 'delay';

    public const string SERVICE = 'service';

    public const string INCIDENT = 'incident';

    public const array TYPES = [
        self::ARRIVAL,
        self::DELAY,
        self::SERVICE,
        self::INCIDENT,
    ];

    public readonly CarbonInterface $sentAt;

    public function __construct(
        public readonly string $title,
        public readonly string $body,
        public readonly string $type,
        public readonly ?int $routeId = null,
        public readonly ?int $stopId = null,
        public readonly ?int $tripId = null,
        public readonly ?string $url = null,
        ?CarbonInterface $sentAt = null,
    ) {
        if (! in_array($type, self::TYPES, true)) {
            throw new InvalidArgumentException('El tipo de notificación no es válido.');
        }

        $this->sentAt = $sentAt ?? CarbonImmutable::now();
    }

    /**
     * @return array<int, string>
     */
    public function via(User $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(User $notifiable): array
    {
        return [
            'title' => $this->title,
            'body' => $this->body,
            'type' => $this->type,
            'route_id' => $this->routeId,
            'stop_id' => $this->stopId,
            'trip_id' => $this->tripId,
            'url' => $this->url,
            'sent_at' => $this->sentAt->toIso8601String(),
        ];
    }
}
