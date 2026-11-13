<?php

namespace App\Http\Controllers\Transit;

use App\Http\Controllers\Controller;
use App\Models\Stop;
use App\Services\Transit\StreetView;
use Illuminate\Http\JsonResponse;

class StreetViewController extends Controller
{
    /**
     * Foto de Mapillary más cercana a la parada para el modal "Ver en 360°".
     * El token va en la respuesta porque el visor lo necesita en el navegador;
     * es el token de cliente de Mapillary, pensado para ser público.
     */
    public function __invoke(Stop $stop, StreetView $streetView): JsonResponse
    {
        $image = $streetView->nearestTo($stop);

        return response()->json([
            'image' => $image,
            'access_token' => $image === null ? null : config('services.mapillary.token'),
        ]);
    }
}
