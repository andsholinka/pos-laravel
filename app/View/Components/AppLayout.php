<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

class AppLayout extends Component
{
    /**
     * @param  string|null  $title  Page title (topbar, <title> and page header). Can also be a named slot.
     * @param  string|null  $subtitle  Optional line under the page header title. Can also be a named slot.
     */
    public function __construct(
        public ?string $title = null,
        public ?string $subtitle = null,
    ) {}

    /**
     * Get the view / contents that represents the component.
     */
    public function render(): View
    {
        return view('layouts.app');
    }
}
