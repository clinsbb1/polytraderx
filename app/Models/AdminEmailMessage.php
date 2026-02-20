<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEmailMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'recipient_user_id',
        'announcement_id',
        'recipient_email',
        'batch_id',
        'is_broadcast',
        'subject',
        'headline',
        'lines',
        'action_text',
        'action_url',
        'small_print',
        'status',
        'attempts',
        'last_attempt_at',
        'success',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_broadcast' => 'boolean',
            'lines' => 'array',
            'attempts' => 'integer',
            'last_attempt_at' => 'datetime',
            'success' => 'boolean',
            'sent_at' => 'datetime',
        ];
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class, 'announcement_id');
    }
}
