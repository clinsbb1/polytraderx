<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminTelegramMessage;
use App\Models\Announcement;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::latest()->paginate(20);

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        return view('admin.announcements.form', ['announcement' => null]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,success,danger'],
            'is_active' => ['boolean'],
            'show_on_dashboard' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active', true);
        $validated['show_on_dashboard'] = $request->boolean('show_on_dashboard', true);

        $announcement = Announcement::create($validated);

        $queuedCount = 0;
        if ($announcement->is_active) {
            $queuedCount = $this->queueTelegramBroadcast($announcement, (int) auth()->id());
        }

        $message = 'Announcement created.';
        if ($queuedCount > 0) {
            $message .= " Telegram broadcast queued for {$queuedCount} connected user(s).";
        }

        return redirect('/admin/announcements')->with('success', $message);
    }

    public function edit(Announcement $announcement): View
    {
        return view('admin.announcements.form', compact('announcement'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,success,danger'],
            'is_active' => ['boolean'],
            'show_on_dashboard' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_dashboard'] = $request->boolean('show_on_dashboard');

        $announcement->update($validated);

        $queuedCount = 0;
        if ($announcement->is_active) {
            $queuedCount = $this->queueTelegramBroadcast($announcement, (int) auth()->id());
        }

        $message = 'Announcement updated.';
        if ($queuedCount > 0) {
            $message .= " Telegram broadcast queued for {$queuedCount} connected user(s).";
        }

        return redirect('/admin/announcements')->with('success', $message);
    }

    public function destroy(Announcement $announcement): RedirectResponse
    {
        $announcement->delete();

        return redirect('/admin/announcements')->with('success', 'Announcement deleted.');
    }

    private function queueTelegramBroadcast(Announcement $announcement, int $adminId): int
    {
        $recipients = User::query()
            ->whereNotNull('telegram_chat_id')
            ->get(['id', 'telegram_chat_id']);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $batchId = (string) Str::uuid();
        $now = now();
        $safeTitle = trim(strip_tags((string) $announcement->title));
        $safeBody = trim(strip_tags((string) $announcement->body));
        $message = "<b>Platform Announcement</b>\n\n"
            . "<b>{$safeTitle}</b>\n"
            . "{$safeBody}";
        if (function_exists('mb_substr')) {
            $message = mb_substr($message, 0, 3900);
        } else {
            $message = substr($message, 0, 3900);
        }

        $rows = [];
        foreach ($recipients as $recipient) {
            $rows[] = [
                'admin_id' => $adminId,
                'recipient_user_id' => $recipient->id,
                'recipient_chat_id' => $recipient->telegram_chat_id,
                'batch_id' => $batchId,
                'is_broadcast' => true,
                'message' => $message,
                'image_path' => null,
                'status' => 'pending',
                'attempts' => 0,
                'last_attempt_at' => null,
                'success' => false,
                'error_message' => null,
                'sent_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        try {
            AdminTelegramMessage::insert($rows);
            return count($rows);
        } catch (\Throwable $e) {
            Log::channel('simulator')->warning('Failed to queue Telegram broadcast for announcement', [
                'announcement_id' => $announcement->id,
                'error' => $e->getMessage(),
            ]);

            return 0;
        }
    }
}
