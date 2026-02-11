<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class LogController extends Controller
{
    public function index(): View
    {
        return view('logs.index');
    }
}
