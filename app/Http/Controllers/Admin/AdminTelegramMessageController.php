<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTelegramMessage;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminTelegramMessageController extends Controller
{
    public function index(): View
    {
        $connectedUsers = User::query()
            ->whereNotNull('telegram_chat_id')
            ->orderBy('name')
            ->get(['id', 'name', 'account_id', 'telegram_username', 'telegram_chat_id']);

        $history = AdminTelegramMessage::query()
            ->with(['admin:id,name', 'recipient:id,name,account_id'])
            ->latest('created_at')
            ->paginate(50);

        return view('admin.telegram.messages', compact('connectedUsers', 'history'));
    }

    public function send(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'target' => ['required', 'in:all,single'],
            'recipient_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'message' => ['required', 'string', 'max:4000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        if ($validated['target'] === 'single' && empty($validated['recipient_user_id'])) {
            return back()->with('error', 'Select a recipient user for single send.');
        }

        $admin = $request->user();
        $isBroadcast = $validated['target'] === 'all';
        $batchId = $isBroadcast ? (string) Str::uuid() : null;

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('telegram-admin', 'public');
        }

        $recipients = $isBroadcast
            ? User::query()->whereNotNull('telegram_chat_id')->get()
            : User::query()->whereKey((int) $validated['recipient_user_id'])->whereNotNull('telegram_chat_id')->get();

        if ($recipients->isEmpty()) {
            return back()->with('error', 'No connected Telegram recipients found for this send.');
        }

        $message = (string) $validated['message'];
        $queuedCount = 0;

        foreach ($recipients as $recipient) {
            AdminTelegramMessage::create([
                'admin_id' => $admin->id,
                'recipient_user_id' => $recipient->id,
                'recipient_chat_id' => $recipient->telegram_chat_id,
                'batch_id' => $batchId,
                'is_broadcast' => $isBroadcast,
                'message' => $message,
                'image_path' => $imagePath,
                'status' => 'pending',
                'attempts' => 0,
                'success' => false,
                'error_message' => null,
                'sent_at' => null,
            ]);

            $queuedCount++;
        }

        $label = $isBroadcast ? 'broadcast' : 'single';
        return back()->with(
            'success',
            "Telegram {$label} queued. {$queuedCount} message(s) will be processed by scheduler each minute."
        );
    }
}
