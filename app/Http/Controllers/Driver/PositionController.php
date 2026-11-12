<?php

namespace App\Http\Controllers\Driver;

use App\Http\Controllers\Controller;
use App\Http\Requests\Driver\StorePositionRequest;
use App\Models\Trip;
use App\Services\Tracking\PositionRecorder;
use Illuminate\Http\Response;

/**
 * Recibe la ubicación del celular del conductor cada 5 s mientras el viaje
 * está en curso (S3-07). Responde sin contenido para no recargar la página.
 */
class PositionController extends Controller
{
    public function store(StorePositionRequest $request, Trip $trip, PositionRecorder $recorder): Response
    {
        $recorder->record(
            $trip->vehicle,
            (float) $request->validated('latitude'),
            (float) $request->validated('longitude'),
            $request->filled('speed_kmh') ? (float) $request->validated('speed_kmh') : null,
            $request->filled('heading') ? (int) round((float) $request->validated('heading')) : null,
            $trip,
        );

        return response()->noContent();
    }
}
