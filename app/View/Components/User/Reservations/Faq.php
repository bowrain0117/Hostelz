<?php

namespace App\View\Components\User\Reservations;

use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Faq extends Component
{
    public function render(): View
    {
        return view('user.components.reservations.faq');
    }
}
