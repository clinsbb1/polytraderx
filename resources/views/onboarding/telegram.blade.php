@extends('layouts.public')

@section('title', 'Telegram Setup — PolyTraderX')

@section('content')
<section class="py-5">
    <div class="container" style="max-width:600px">
        <!-- Progress -->
        <div class="d-flex justify-content-center mb-4">
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-success rounded-pill px-3"><i class="bi bi-check"></i></span>
                <span class="text-muted">—</span>
                <span class="badge bg-primary rounded-pill px-3">3</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">4</span>
                <span class="text-muted">—</span>
                <span class="badge bg-secondary rounded-pill px-3">5</span>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h2 class="fw-bold mb-3">Telegram Notifications</h2>
                <p class="text-muted">Set up Telegram to receive trade alerts, daily P&L summaries, and error notifications.</p>

                <div class="alert alert-info small">
                    <i class="bi bi-info-circle me-1"></i>
                    <strong>How to set up:</strong>
                    <ol class="mb-0 mt-1">
                        <li>Message <code>@BotFather</code> on Telegram and create a new bot</li>
                        <li>Copy the bot token provided</li>
                        <li>Start a chat with your bot, then get your chat ID from <code>@userinfobot</code></li>
                    </ol>
                </div>

                <form method="POST" action="/onboarding/telegram">
                    @csrf

                    <div class="mb-3">
                        <label for="telegram_bot_token" class="form-label fw-semibold">Bot Token</label>
                        <input type="password" id="telegram_bot_token" name="telegram_bot_token" class="form-control" placeholder="123456:ABC-DEF..." value="{{ old('telegram_bot_token') }}">
                        @if($credential && $credential->telegram_bot_token)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Token already saved</div>
                        @endif
                        @error('telegram_bot_token') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label for="telegram_chat_id" class="form-label fw-semibold">Chat ID</label>
                        <input type="text" id="telegram_chat_id" name="telegram_chat_id" class="form-control" placeholder="Your Telegram chat ID" value="{{ old('telegram_chat_id') }}">
                        @if($credential && $credential->telegram_chat_id)
                            <div class="text-success small mt-1"><i class="bi bi-check-circle"></i> Chat ID already saved</div>
                        @endif
                        @error('telegram_chat_id') <div class="text-danger small mt-1">{{ $message }}</div> @enderror
                    </div>

                    <div class="d-flex justify-content-between">
                        <a href="/onboarding/polymarket" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Back</a>
                        <div>
                            <a href="/onboarding/anthropic" class="btn btn-outline-secondary me-2">Skip</a>
                            <button type="submit" class="btn btn-ptx">Next: AI Setup <i class="bi bi-arrow-right"></i></button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
@endsection
