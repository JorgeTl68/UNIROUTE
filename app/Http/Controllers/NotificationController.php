<?php

namespace App\Http\Controllers;

use App\Models\ArrivalAlarm;
use App\Models\TransitRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Mis Notificaciones y Alertas (S3-08): avisos guardados por TransitNotification
 * (canal database), las alarmas de llegada y las rutas favoritas.
 */
class NotificationController extends Controller
{
    /**
     * Cuántos avisos se muestran en la página.
     */
    private const int LIMIT = 50;

    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('notifications/index', [
            'notifications' => $user->notifications()
                ->latest()
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (DatabaseNotification $notification): array => self::present($notification)),
            'alarms' => $user->arrivalAlarms()
                ->with(['route:id,number,name,color', 'stop:id,name'])
                ->latest()
                ->get()
                ->map(fn (ArrivalAlarm $alarm): array => [
                    'id' => $alarm->id,
                    'minutes_before' => $alarm->minutes_before,
                    'route' => $alarm->route->only(['id', 'number', 'name', 'color']),
                    'stop' => $alarm->stop->only(['id', 'name']),
                ]),
            'favorites' => $user->favoriteRoutes()
                ->orderBy('number')
                ->get()
                ->map(fn (TransitRoute $route): array => $route->only(['id', 'number', 'name', 'color'])),
        ]);
    }

    /**
     * Marca el aviso como leído y lleva a lo que anuncia.
     */
    public function open(Request $request, string $notification): RedirectResponse
    {
        $record = $request->user()->notifications()->findOrFail($notification);
        $record->markAsRead();

        $url = $record->data['url'] ?? null;

        return is_string($url) && str_starts_with($url, '/')
            ? redirect($url)
            : to_route('notifications.index');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back();
    }

    /**
     * Forma en que llega un aviso al frontend (tipo AppNotification).
     *
     * @return array<string, mixed>
     */
    public static function present(DatabaseNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? 'service',
            'title' => $notification->data['title'] ?? '',
            'body' => $notification->data['body'] ?? '',
            'read_at' => $notification->read_at?->toIso8601String(),
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
