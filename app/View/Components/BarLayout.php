<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class BarLayout extends Component
{
    /**
     * Define o layout isolado do Bar.
     */
    public function render(): View
    {
        // Aqui dizemos que este componente usa o arquivo de layout que criamos antes
        return view('layouts.bar');
    }
}
