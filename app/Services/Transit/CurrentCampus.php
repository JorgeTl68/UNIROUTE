<?php

namespace App\Services\Transit;

use App\Models\Campus;
use Illuminate\Http\Request;

/**
 * Campus que se está consultando: el que se eligió en el header (?campus=slug),
 * el del usuario o, si no tiene, el primero.
 */
class CurrentCampus
{
    public function resolve(Request $request): ?Campus
    {
        $slug = $request->query('campus');

        if (is_string($slug) && $slug !== '') {
            $campus = Campus::query()->where('slug', $slug)->first();

            if ($campus !== null) {
                return $campus;
            }
        }

        return $request->user()->campus ?? Campus::query()->orderBy('id')->first();
    }
}
