<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PosController extends Controller
{
    /**
     * Cashier (POS) screen (placeholder; the full POS arrives with the checkout feature).
     */
    public function index(): View
    {
        return view('pos.index');
    }
}
