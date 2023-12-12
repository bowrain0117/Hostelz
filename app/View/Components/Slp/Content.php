<?php

namespace App\View\Components\Slp;

use App\Lib\Slp\EditorShortcodes;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;

class Content extends Component
{
    public function __construct(
        public SpecialLandingPage $slp
    ) {
    }

    public function render()
    {
        return view('slp.components.content');
    }

    public function content(): string
    {
        $content = EditorShortcodes::create()
           ->replaceShortcodes(
               content: $this->slp->content,
               callback: fn ($content) => $this->slp->replaceShortcodes($content)
           );

        return Blade::render($content, ['slp' => $this->slp, 'hostels' => $this->slp->hostels]);
    }
}
