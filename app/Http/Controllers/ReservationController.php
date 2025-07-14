<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Http\Requests\StoreReservationRequest;

class ReservationController extends Controller
{
    public function store(StoreReservationRequest $request)
    {
        $reservation = Reservation::create($request->validated());

        return response()->json($reservation, 201);
    }
}
