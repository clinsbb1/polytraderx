<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminTelegramMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'recipient_user_id',
        'recipient_chat_id',
        'batch_id',
        'is_broadcast',
        'message',
        'image_path',
        'success',
        'error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'is_broadcast' => 'boolean',
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
}

