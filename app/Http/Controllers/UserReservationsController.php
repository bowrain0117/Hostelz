<?php

namespace App\Http\Controllers;

class UserReservationsController extends Controller
{
    public function index()
    {
        return view('user.reservations.index', [
            'user' => auth()->user(),
        ]);
    }
}
