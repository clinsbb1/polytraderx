<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminEmailMessage;
use Illuminate\View\View;

class AdminEmailMessageController extends Controller
{
    public function index(): View
    {
        $history = AdminEmailMessage::query()
            ->with(['admin:id,name', 'recipient:id,name,account_id', 'announcement:id,title'])
            ->latest('created_at')
            ->paginate(25)
            ->withQueryString();

        return view('admin.email.messages', compact('history'));
    }
}
