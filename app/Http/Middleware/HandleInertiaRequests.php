<?php

namespace App\Http\Middleware;

use App\Http\Controllers\NotificationController;
use App\Http\Requests\StoreEmergencyRequest;
use App\Models\Campus;
use App\Models\TransitRoute;
use App\Models\Trip;
use App\Services\Transit\CurrentCampus;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Inertia\Inertia;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            // Header y sidebar: se calculan solo con sesión y no en recargas parciales.
            'campuses' => fn (): array => $request->user() === null ? [] : Campus::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->all(),
            'currentCampus' => fn (): ?array => $request->user() === null
                ? null
                : app(CurrentCampus::class)->resolve($request)?->only(['id', 'name', 'slug']),
            'liveUnits' => fn (): int => $request->user() === null ? 0 : Trip::query()->inProgress()->count(),
            'notifications' => fn (): ?array => $request->user() === null ? null : [
                'unread' => $request->user()->unreadNotifications()->count(),
                'latest' => $request->user()->notifications()
                    ->latest()
                    ->limit(5)
                    ->get()
                    ->map(fn (DatabaseNotification $notification): array => NotificationController::present($notification))
                    ->all(),
            ],
            // Datos del modal "Reportar emergencia": se piden al abrirlo.
            'emergency' => Inertia::optional(function () use ($request): array {
                $campus = app(CurrentCampus::class)->resolve($request);

                return [
                    'routes' => $campus === null ? [] : TransitRoute::query()
                        ->active()
                        ->forCampus($campus)
                        ->orderBy('number')
                        ->get(['id', 'number', 'name'])
                        ->all(),
                    'categories' => collect(StoreEmergencyRequest::CATEGORIES)
                        ->map(fn (string $label, string $value): array => ['value' => $value, 'label' => $label])
                        ->values()
                        ->all(),
                    'driver_route_id' => $request->user()?->currentTrip()?->route_id,
                ];
            }),
        ];
    }
}
