<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

// TODO(S3-01): evento provisional de S0-10. Eliminar cuando existan
// VehiclePositionUpdated, TripStatusChanged e IncidentReported.
class TrackingTestPing implements ShouldBroadcastNow
{
    use Dispatchable, SerializesModels;

    public const string CHANNEL = 'test.ping';

    public function __construct(public readonly string $message) {}

    /**
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [new Channel(self::CHANNEL)];
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'message' => $this->message,
            'sent_at' => Carbon::now()->toIso8601String(),
        ];
    }
}
