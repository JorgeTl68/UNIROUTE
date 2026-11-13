<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\TransitRoute;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FavoriteRouteController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        $favorites = $user->favoriteRoutes()
            ->with(['route.stops'])
            ->whereHas('route', function ($query) use ($user) {
                $query->forCampus($user->campus_id);
            })
            ->get()
            ->map(function ($favorite) {
                return $favorite->route;
            })
            ->filter()
            ->values();

        return Inertia::render('favorites/index', [
            'favorites' => $favorites,
        ]);
    }

    public function store(Request $request, int $route)
    {
        $user = $request->user();

        if (!$user->campus_id) {
            return back()->with('error', 'No tienes un campus asignado.');
        }

        $transitRoute = TransitRoute::query()
            ->active()
            ->forCampus($user->campus_id)
            ->findOrFail($route);

        $user->favoriteRoutes()->firstOrCreate([
            'route_id' => $transitRoute->id,
        ]);

        return back()->with('success', 'Ruta agregada a favoritos.');
    }

    public function destroy(Request $request, int $route)
    {
        $user = $request->user();

        if (!$user->campus_id) {
            return back()->with('error', 'No tienes un campus asignado.');
        }

        $transitRoute = TransitRoute::query()
            ->forCampus($user->campus_id)
            ->findOrFail($route);

        $user->favoriteRoutes()
            ->where('route_id', $transitRoute->id)
            ->delete();

        return back()->with('success', 'Ruta eliminada de favoritos.');
    }
}
