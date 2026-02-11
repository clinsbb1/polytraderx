<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserCredential extends Model
{
    protected $fillable = [
        'user_id',
        'polymarket_api_key',
        'polymarket_api_secret',
        'polymarket_api_passphrase',
        'polymarket_wallet_address',
        'polymarket_private_key',
        'telegram_bot_token',
        'telegram_chat_id',
        'anthropic_api_key',
        'binance_api_key',
    ];

    protected $hidden = [
        'polymarket_api_key',
        'polymarket_api_secret',
        'polymarket_api_passphrase',
        'polymarket_private_key',
        'telegram_bot_token',
        'anthropic_api_key',
        'binance_api_key',
    ];

    protected function casts(): array
    {
        return [
            'polymarket_api_key' => 'encrypted',
            'polymarket_api_secret' => 'encrypted',
            'polymarket_api_passphrase' => 'encrypted',
            'polymarket_private_key' => 'encrypted',
            'telegram_bot_token' => 'encrypted',
            'anthropic_api_key' => 'encrypted',
            'binance_api_key' => 'encrypted',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function hasPolymarketKeys(): bool
    {
        return !empty($this->polymarket_api_key)
            && !empty($this->polymarket_api_secret)
            && !empty($this->polymarket_api_passphrase)
            && !empty($this->polymarket_wallet_address);
    }

    public function hasTelegramKeys(): bool
    {
        return !empty($this->telegram_bot_token)
            && !empty($this->telegram_chat_id);
    }

    public function hasAnthropicKey(): bool
    {
        return !empty($this->anthropic_api_key);
    }

    public function isFullyConfigured(): bool
    {
        return $this->hasPolymarketKeys()
            && $this->hasTelegramKeys()
            && $this->hasAnthropicKey();
    }
}
