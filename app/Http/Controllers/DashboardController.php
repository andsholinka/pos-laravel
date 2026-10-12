<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class DashboardController extends Controller
{
    /**
     * Admin dashboard (placeholder; stats and charts arrive with the reports feature).
     */
    public function __invoke(): View
    {
        return view('dashboard');
    }
}
