<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStopRequest;
use App\Models\Stop;
use Illuminate\Http\RedirectResponse;

class StopController extends Controller
{
    public function store(StoreStopRequest $request): RedirectResponse
    {
        $stop = Stop::create($request->validated());

        return back()->with('created_stop_id', $stop->id);
    }
}
