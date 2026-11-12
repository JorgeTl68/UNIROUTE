<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use App\Models\TransitRoute;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Rutas favoritas (S4-02): la estrella del dashboard y del detalle de ruta.
 */
class FavoriteRouteController extends Controller
{
    public function store(Request $request, TransitRoute $transitRoute): RedirectResponse
    {
        $request->user()->favoriteRoutes()->syncWithoutDetaching([$transitRoute->id]);

        return back();
    }

    public function destroy(Request $request, TransitRoute $transitRoute): RedirectResponse
    {
        $request->user()->favoriteRoutes()->detach($transitRoute->id);

        return back();
    }
}
