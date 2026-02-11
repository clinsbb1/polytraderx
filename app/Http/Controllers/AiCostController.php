<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\View\View;

class AiCostController extends Controller
{
    public function index(): View
    {
        return view('ai-costs.index');
    }
}
