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
    ];

    protected $hidden = [
        'polymarket_api_key',
        'polymarket_api_secret',
        'polymarket_api_passphrase',
    ];

    protected function casts(): array
    {
        return [
            'polymarket_api_key' => 'encrypted',
            'polymarket_api_secret' => 'encrypted',
            'polymarket_api_passphrase' => 'encrypted',
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

    public function isConfigured(): bool
    {
        return $this->hasPolymarketKeys();
    }
}
