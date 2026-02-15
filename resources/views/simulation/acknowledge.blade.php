@extends('layouts.public')

@section('title', 'Simulation Platform Agreement — PolyTraderX')
@section('meta_robots', 'noindex, nofollow')

@section('extra-styles')
<style>
    .ptx-acknowledge-container {
        min-height: 100vh;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem 1rem;
        background: linear-gradient(135deg, #0a0e27 0%, #1a1f3a 100%);
    }

    .ptx-acknowledge-card {
        max-width: 700px;
        width: 100%;
        background: rgba(255, 255, 255, 0.03);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 20px;
        padding: 3rem;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
    }

    .ptx-acknowledge-header {
        text-align: center;
        margin-bottom: 2rem;
    }

    .ptx-acknowledge-header h1 {
        font-family: var(--font-display);
        font-size: 2rem;
        font-weight: 700;
        color: #fff;
        margin-bottom: 0.5rem;
    }

    .ptx-acknowledge-header .subtitle {
        color: rgba(255, 255, 255, 0.6);
        font-size: 1rem;
    }

    .ptx-acknowledge-content {
        margin-bottom: 2rem;
    }

    .ptx-acknowledge-content h2 {
        font-family: var(--font-display);
        font-size: 1.25rem;
        font-weight: 600;
        color: #fff;
        margin-bottom: 1.5rem;
    }

    .ptx-acknowledge-list {
        list-style: none;
        padding: 0;
        margin: 0 0 2rem 0;
    }

    .ptx-acknowledge-list li {
        display: flex;
        align-items: flex-start;
        margin-bottom: 1.25rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
    }

    .ptx-acknowledge-list li i {
        color: var(--accent);
        margin-right: 1rem;
        margin-top: 0.25rem;
        font-size: 1.25rem;
        flex-shrink: 0;
    }

    .ptx-acknowledge-checkbox {
        display: flex;
        align-items: center;
        background: rgba(255, 255, 255, 0.02);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 12px;
        padding: 1.25rem;
        margin-bottom: 1.5rem;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .ptx-acknowledge-checkbox:hover {
        background: rgba(255, 255, 255, 0.04);
        border-color: rgba(255, 255, 255, 0.12);
    }

    .ptx-acknowledge-checkbox input[type="checkbox"] {
        width: 24px;
        height: 24px;
        margin-right: 1rem;
        cursor: pointer;
        flex-shrink: 0;
    }

    .ptx-acknowledge-checkbox label {
        color: #fff;
        font-weight: 500;
        margin: 0;
        cursor: pointer;
        user-select: none;
    }

    .ptx-acknowledge-button {
        width: 100%;
        padding: 1rem;
        font-size: 1.1rem;
        font-weight: 600;
        border: none;
        border-radius: 12px;
        background: linear-gradient(135deg, var(--accent) 0%, #7c3aed 100%);
        color: #fff;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 8px 20px rgba(139, 92, 246, 0.3);
    }

    .ptx-acknowledge-button:hover:not(:disabled) {
        transform: translateY(-2px);
        box-shadow: 0 12px 30px rgba(139, 92, 246, 0.4);
    }

    .ptx-acknowledge-button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
        transform: none;
        box-shadow: none;
    }

    @media (max-width: 768px) {
        .ptx-acknowledge-card {
            padding: 2rem 1.5rem;
        }

        .ptx-acknowledge-header h1 {
            font-size: 1.5rem;
        }
    }
</style>
@endsection

@section('content')
<div class="ptx-acknowledge-container">
    <div class="ptx-acknowledge-card">
        <div class="ptx-acknowledge-header">
            <h1><i class="bi bi-shield-check text-accent me-2"></i> Simulation Platform Agreement</h1>
            <p class="subtitle">Please read and acknowledge the following before proceeding</p>
        </div>

        <div class="ptx-acknowledge-content">
            <h2>PolyTraderX is a Simulation-Only Platform</h2>

            <ul class="ptx-acknowledge-list">
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><strong>No Real Trading:</strong> PolyTraderX operates exclusively in simulation mode. All "trades" are simulated using real market data from Polymarket and Binance.</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><strong>No Real Money at Risk:</strong> You will never place real orders, risk real capital, or move actual funds. Your balances and P&L are simulated for strategy testing purposes only.</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><strong>Educational Purpose:</strong> This platform is designed to help you design, test, and optimize trading strategies without financial risk. Results are for analysis only.</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><strong>No Private Keys Required:</strong> We never ask for or store blockchain private keys. API credentials (if provided) are used only to fetch market data.</span>
                </li>
                <li>
                    <i class="bi bi-check-circle-fill"></i>
                    <span><strong>Simulated Performance ≠ Real Results:</strong> Simulation assumes perfect fills with zero slippage. Real trading performance would differ due to market conditions, fees, and execution risks.</span>
                </li>
            </ul>
        </div>

        <form method="POST" action="{{ route('simulation.accept') }}">
            @csrf

            <label class="ptx-acknowledge-checkbox" for="acknowledge-checkbox">
                <input type="checkbox" id="acknowledge-checkbox" name="acknowledge" value="1" required onchange="toggleSubmitButton()">
                <span>I understand and acknowledge that PolyTraderX is a simulation platform and no real trading will occur.</span>
            </label>

            @error('acknowledge')
                <div class="alert alert-danger mb-3">{{ $message }}</div>
            @enderror

            <button type="submit" id="submit-button" class="ptx-acknowledge-button" disabled>
                Continue to Dashboard
            </button>
        </form>
    </div>
</div>

<script>
    function toggleSubmitButton() {
        const checkbox = document.getElementById('acknowledge-checkbox');
        const button = document.getElementById('submit-button');
        button.disabled = !checkbox.checked;
    }
</script>
@endsection
