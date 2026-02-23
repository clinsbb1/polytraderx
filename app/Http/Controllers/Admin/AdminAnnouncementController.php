<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendAdminEmailMessageJob;
use App\Mail\BrandedNotificationMail;
use App\Models\AdminEmailMessage;
use App\Models\AdminTelegramMessage;
use App\Models\Announcement;
use App\Models\User;
use App\Support\AnnouncementTemplate;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminAnnouncementController extends Controller
{
    public function index(): View
    {
        $announcements = Announcement::with('targetUser:id,name,email,account_id')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.announcements.index', compact('announcements'));
    }

    public function create(): View
    {
        $targetUserOptions = $this->buildTargetUserOptions();

        return view('admin.announcements.form', [
            'announcement' => null,
            'targetUserOptions' => $targetUserOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,success,danger'],
            'is_active' => ['boolean'],
            'show_on_dashboard' => ['boolean'],
            'dashboard_until_date' => ['required_if:show_on_dashboard,1', 'nullable', 'date'],
            'audience_type' => ['required', 'in:all,single,paid_active,free_plan'],
            'target_user_id' => ['nullable', 'required_if:audience_type,single', 'integer', 'exists:users,id'],
            'send_email' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_dashboard'] = $request->boolean('show_on_dashboard');
        $validated['send_email'] = $request->boolean('send_email');
        $validated['dashboard_until_at'] = $validated['show_on_dashboard']
            ? Carbon::parse((string) $request->input('dashboard_until_date'))->endOfDay()
            : null;
        $validated['target_user_id'] = $validated['audience_type'] === 'single'
            ? (int) $request->input('target_user_id')
            : null;
        unset($validated['dashboard_until_date']);

        $announcement = Announcement::create($validated);

        $queuedCount = 0;
        if ($announcement->is_active) {
            $queuedCount = $this->queueTelegramBroadcast($announcement, (int) auth()->id());
        }
        $emailQueuedCount = $announcement->send_email
            ? $this->queueEmailBroadcast($announcement, (int) auth()->id())
            : 0;

        $message = 'Announcement created.';
        if ($queuedCount > 0) {
            $message .= " Telegram broadcast queued for {$queuedCount} connected user(s).";
        }
        if ($emailQueuedCount > 0) {
            $message .= " Email broadcast queued for {$emailQueuedCount} user(s).";
        }

        return redirect('/admin/announcements')->with('success', $message);
    }

    public function edit(Announcement $announcement): View
    {
        $targetUserOptions = $this->buildTargetUserOptions($announcement);

        return view('admin.announcements.form', compact('announcement', 'targetUserOptions'));
    }

    public function update(Request $request, Announcement $announcement): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string'],
            'type' => ['required', 'in:info,warning,success,danger'],
            'is_active' => ['boolean'],
            'show_on_dashboard' => ['boolean'],
            'dashboard_until_date' => ['required_if:show_on_dashboard,1', 'nullable', 'date'],
            'audience_type' => ['required', 'in:all,single,paid_active,free_plan'],
            'target_user_id' => ['nullable', 'required_if:audience_type,single', 'integer', 'exists:users,id'],
            'send_email' => ['boolean'],
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['show_on_dashboard'] = $request->boolean('show_on_dashboard');
        $validated['send_email'] = $request->boolean('send_email');
        $validated['dashboard_until_at'] = $validated['show_on_dashboard']
            ? Carbon::parse((string) $request->input('dashboard_until_date'))->endOfDay()
            : null;
        $validated['target_user_id'] = $validated['audience_type'] === 'single'
            ? (int) $request->input('target_user_id')
            : null;
        unset($validated['dashboard_until_date']);

        $announcement->update($validated);

        $queuedCount = 0;
        if ($announcement->is_active) {
            $queuedCount = $this->queueTelegramBroadcast($announcement, (int) auth()->id());
        }
        $emailQueuedCount = $announcement->send_email
            ? $this->queueEmailBroadcast($announcement, (int) auth()->id())
            : 0;

        $message = 'Announcement updated.';
        if ($queuedCount > 0) {
            $message .= " Telegram broadcast queued for {$queuedCount} connected user(s).";
        }
        if ($emailQueuedCount > 0) {
            $message .= " Email broadcast queued for {$emailQueuedCount} user(s).";
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
        $recipients = $this->recipientUsersQuery($announcement)
            ->whereNotNull('telegram_chat_id')
            ->get(['id', 'name', 'email', 'account_id', 'subscription_plan', 'subscription_ends_at', 'telegram_chat_id']);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $batchId = (string) Str::uuid();
        $now = now();
        $rows = [];
        foreach ($recipients as $recipient) {
            $safeTitle = trim(strip_tags(AnnouncementTemplate::render((string) $announcement->title, $recipient)));
            $safeBody = trim(strip_tags(AnnouncementTemplate::render((string) $announcement->body, $recipient)));
            $message = "<b>Platform Announcement</b>\n\n"
                . "<b>{$safeTitle}</b>\n"
                . "{$safeBody}";
            if (function_exists('mb_substr')) {
                $message = mb_substr($message, 0, 3900);
            } else {
                $message = substr($message, 0, 3900);
            }

            $rows[] = [
                'admin_id' => $adminId,
                'recipient_user_id' => $recipient->id,
                'recipient_chat_id' => $recipient->telegram_chat_id,
                'batch_id' => $batchId,
                'is_broadcast' => ($announcement->audience_type ?? 'all') !== 'single',
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

    private function queueEmailBroadcast(Announcement $announcement, int $adminId): int
    {
        $count = 0;
        $headline = 'Platform Announcement';
        $batchId = (string) Str::uuid();
        $emailQueue = (string) config('services.queues.email', 'emails');
        $isBroadcast = ($announcement->audience_type ?? 'all') !== 'single';
        $trackingEnabled = Schema::hasTable('admin_email_messages');

        $this->recipientUsersQuery($announcement)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->select('id', 'name', 'email', 'account_id', 'subscription_plan', 'subscription_ends_at')
            ->orderBy('id')
            ->chunkById(500, function ($users) use (&$count, $headline, $announcement, $adminId, $batchId, $emailQueue, $isBroadcast, $trackingEnabled) {
                foreach ($users as $user) {
                    $subject = 'Platform Announcement: '
                        . trim(strip_tags(AnnouncementTemplate::render((string) $announcement->title, $user)));
                    $safeTitle = trim(strip_tags(AnnouncementTemplate::render((string) $announcement->title, $user)));
                    $safeBody = trim(strip_tags(AnnouncementTemplate::render((string) $announcement->body, $user)));
                    $lines = array_values(array_filter([
                        $safeTitle,
                        $safeBody,
                    ]));

                    if ($trackingEnabled) {
                        try {
                            $message = AdminEmailMessage::create([
                                'admin_id' => $adminId,
                                'recipient_user_id' => $user->id,
                                'announcement_id' => $announcement->id,
                                'recipient_email' => (string) $user->email,
                                'batch_id' => $batchId,
                                'is_broadcast' => $isBroadcast,
                                'subject' => $subject,
                                'headline' => $headline,
                                'lines' => $lines,
                                'action_text' => 'Open Dashboard',
                                'action_url' => url('/dashboard'),
                                'small_print' => 'This announcement was sent by PolyTraderX admin.',
                                'status' => 'pending',
                                'attempts' => 0,
                                'last_attempt_at' => null,
                                'success' => false,
                                'error_message' => null,
                                'sent_at' => null,
                            ]);
                            SendAdminEmailMessageJob::dispatch($message->id)
                                ->onQueue($emailQueue);
                            $count++;
                            continue;
                        } catch (\Throwable $e) {
                            Log::channel('simulator')->warning('Failed to queue tracked announcement email, falling back to direct queue', [
                                'announcement_id' => $announcement->id,
                                'admin_id' => $adminId,
                                'user_id' => $user->id,
                                'email' => $user->email,
                                'error' => $e->getMessage(),
                            ]);
                        }
                    }

                    try {
                        $this->queueDirectAnnouncementEmail(
                            toEmail: (string) $user->email,
                            subject: $subject,
                            headline: $headline,
                            lines: $lines,
                            emailQueue: $emailQueue
                        );
                        $count++;
                    } catch (\Throwable $e) {
                        Log::channel('simulator')->warning('Failed to queue fallback announcement email', [
                            'announcement_id' => $announcement->id,
                            'admin_id' => $adminId,
                            'user_id' => $user->id,
                            'email' => $user->email,
                            'tracking_enabled' => $trackingEnabled,
                            'error' => $e->getMessage(),
                        ]);
                    }
                }
            });

        return $count;
    }

    /**
     * @param array<int,string> $lines
     */
    private function queueDirectAnnouncementEmail(
        string $toEmail,
        string $subject,
        string $headline,
        array $lines,
        string $emailQueue
    ): void {
        Mail::to($toEmail)->queue(
            (new BrandedNotificationMail(
                subjectLine: $subject,
                headline: $headline,
                lines: $lines,
                actionText: 'Open Dashboard',
                actionUrl: url('/dashboard'),
                smallPrint: 'This announcement was sent by PolyTraderX admin.'
            ))->onQueue($emailQueue)
        );
    }

    /**
     * Base recipient query based on announcement audience targeting.
     */
    private function recipientUsersQuery(Announcement $announcement): Builder
    {
        $query = User::query();
        $audienceType = (string) ($announcement->audience_type ?? 'all');

        if ($audienceType === 'single' && $announcement->target_user_id !== null) {
            $query->whereKey((int) $announcement->target_user_id);
        }

        if ($audienceType === 'paid_active') {
            $query->where('subscription_plan', '!=', 'free')
                ->where(function (Builder $paid) {
                    $paid->where('is_lifetime', true)
                        ->orWhere('subscription_plan', 'lifetime')
                        ->orWhere(function (Builder $expiring) {
                            $expiring->whereNotNull('subscription_ends_at')
                                ->where('subscription_ends_at', '>', now());
                        });
                });
        }

        if ($audienceType === 'free_plan') {
            $query->where('subscription_plan', 'free');
        }

        return $query;
    }

    /**
     * Provide a recent user list for quick target selection in admin UI.
     */
    private function buildTargetUserOptions(?Announcement $announcement = null)
    {
        $users = User::query()
            ->select('id', 'name', 'email', 'account_id')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        $targetId = (int) ($announcement?->target_user_id ?? 0);
        if ($targetId > 0 && !$users->contains('id', $targetId)) {
            $targetUser = User::query()
                ->select('id', 'name', 'email', 'account_id')
                ->whereKey($targetId)
                ->first();

            if ($targetUser) {
                $users->prepend($targetUser);
            }
        }

        return $users;
    }
}
