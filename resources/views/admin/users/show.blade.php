@extends('layouts.super-admin')

@section('title', 'User: ' . $user->name)

@section('content')
<div class="mb-3">
    <a href="/admin/users" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>Back to Users
    </a>
</div>

<div class="row g-4">
    <!-- Profile Card -->
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0"><i class="bi bi-person me-2"></i>Profile</h6>
                <form method="POST" action="/admin/users/{{ $user->id }}/impersonate" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-sm btn-outline-dark" onclick="return confirm('Impersonate {{ $user->name }}? You will be logged in as this user.')">
                        <i class="bi bi-incognito me-1"></i>Impersonate
                    </button>
                </form>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label small text-muted mb-0">Name</label>
                    <div class="fw-semibold">{{ $user->name }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-0">Email</label>
                    <div>{{ $user->email }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-0">Account ID</label>
                    <div><code>{{ $user->account_id }}</code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-0">Timezone</label>
                    <div>{{ $user->timezone ?? 'Not set' }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small text-muted mb-0">Google Account</label>
                    <div>{{ $user->google_id ? 'Linked' : 'Not linked' }}</div>
                </div>
                <div>
                    <label class="form-label small text-muted mb-0">Registered</label>
                    <div class="small">{{ $user->created_at->format('M j, Y H:i') }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Subscription & Actions -->
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-credit-card me-2"></i>Subscription & Actions</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <strong>Status:</strong>
                        @if($user->is_active)
                            <span class="badge bg-success">Active</span>
                        @else
                            <span class="badge bg-danger">Inactive</span>
                        @endif
                    </div>
                    <form method="POST" action="/admin/users/{{ $user->id }}/toggle-active" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-sm {{ $user->is_active ? 'btn-outline-danger' : 'btn-outline-success' }}" onclick="return confirm('{{ $user->is_active ? 'Deactivate' : 'Activate' }} this user?')">
                            <i class="bi {{ $user->is_active ? 'bi-x-circle' : 'bi-check-circle' }} me-1"></i>
                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                        </button>
                    </form>
                </div>

                @if($user->trial_ends_at)
                    <p class="small mb-1"><strong>Trial Ends:</strong> {{ $user->trial_ends_at->format('M j, Y') }}</p>
                @endif
                @if($user->subscription_ends_at)
                    <p class="small mb-2"><strong>Subscription Ends:</strong> {{ $user->subscription_ends_at->format('M j, Y') }}</p>
                @endif

                <hr>

                <!-- Change Plan -->
                <form method="POST" action="/admin/users/{{ $user->id }}/change-plan" class="mb-3">
                    @csrf
                    <label class="form-label small fw-semibold">Change Plan</label>
                    <div class="input-group input-group-sm">
                        <select name="subscription_plan" class="form-select form-select-sm">
                            @foreach($availablePlans as $planOption)
                                @continue(!$planOption->is_active && $user->subscription_plan !== $planOption->slug)
                                <option value="{{ $planOption->slug }}" {{ $user->subscription_plan === $planOption->slug ? 'selected' : '' }}>
                                    {{ $planOption->name }}{{ !$planOption->is_active ? ' (inactive)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>

                <!-- Change Subscription End Date -->
                <form method="POST" action="/admin/users/{{ $user->id }}/change-plan" class="mb-0">
                    @csrf
                    <input type="hidden" name="subscription_plan" value="{{ $user->subscription_plan }}">
                    <label class="form-label small fw-semibold">Subscription Ends At</label>
                    <div class="input-group input-group-sm">
                        <input type="date" name="subscription_ends_at" class="form-control form-control-sm" value="{{ $user->subscription_ends_at?->format('Y-m-d') }}">
                        <button type="submit" class="btn btn-sm btn-primary">Save</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Credentials Status -->
        <div class="card mb-4">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Credentials Status</h6></div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="small">Telegram</span>
                    @if($user->hasTelegramLinked())
                        <span class="badge bg-success"><i class="bi bi-check me-1"></i>Linked</span>
                    @else
                        <span class="badge bg-secondary">Not linked</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Grant Complimentary Subscription -->
        <div class="card">
            <div class="card-header bg-success bg-opacity-10">
                <h6 class="mb-0 text-success">
                    <i class="bi bi-gift me-2"></i>Grant Complimentary Subscription
                </h6>
            </div>
            <div class="card-body">
                <div id="grantSubSection" style="display: none;">
                    <form method="POST" action="/admin/users/{{ $user->id }}/grant-free-subscription">
                        @csrf
                        <div class="mb-2">
                            <label class="form-label small">Plan</label>
                            <select name="plan_slug" class="form-select form-select-sm">
                                @foreach($availablePlans as $planOption)
                                    @continue(!$planOption->is_active)
                                    @continue($planOption->slug === 'free' && !$freeModeEnabled)
                                    <option value="{{ $planOption->slug }}">{{ $planOption->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Duration</label>
                            <select name="duration_days" class="form-select form-select-sm">
                                <option value="30">30 days</option>
                                <option value="90">90 days</option>
                                <option value="180">180 days</option>
                                <option value="365">1 year</option>
                                <option value="3650">10 years (lifetime)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Notes (optional)</label>
                            <input type="text" name="notes" class="form-control form-control-sm" placeholder="e.g. Beta tester reward">
                        </div>
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Grant complimentary subscription to {{ $user->name }}?')">
                                <i class="bi bi-check-lg me-1"></i>Submit
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="document.getElementById('grantSubSection').style.display='none'; document.getElementById('grantSubBtn').style.display='';">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
                <button type="button" id="grantSubBtn" class="btn btn-sm btn-success" onclick="document.getElementById('grantSubSection').style.display='block'; this.style.display='none';">
                    <i class="bi bi-gift me-1"></i>Grant Complimentary Subscription
                </button>
            </div>
        </div>
    </div>

    <!-- User Stats -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-bar-chart me-2"></i>User Statistics</h6></div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col">
                        <div class="fw-bold fs-4">{{ $tradeStats['total'] }}</div>
                        <div class="text-muted small">Total Trades</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-4 text-success">{{ $tradeStats['won'] }}</div>
                        <div class="text-muted small">Won</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-4 text-danger">{{ $tradeStats['lost'] }}</div>
                        <div class="text-muted small">Lost</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-4 text-info">{{ $tradeStats['open'] }}</div>
                        <div class="text-muted small">Open</div>
                    </div>
                    <div class="col">
                        @php
                            $winRate = $tradeStats['total'] > 0 ? round(($tradeStats['won'] / $tradeStats['total']) * 100, 1) : 0;
                        @endphp
                        <div class="fw-bold fs-4">{{ $winRate }}%</div>
                        <div class="text-muted small">Win Rate</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-4 {{ (float)$tradeStats['total_pnl'] >= 0 ? 'text-success' : 'text-danger' }}">
                            ${{ number_format((float)$tradeStats['total_pnl'], 2) }}
                        </div>
                        <div class="text-muted small">Total P&L</div>
                    </div>
                    <div class="col">
                        <div class="fw-bold fs-5">
                            {{ $user->last_bot_heartbeat ? $user->last_bot_heartbeat->diffForHumans() : 'Offline' }}
                        </div>
                        <div class="text-muted small">Bot Status</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Trades -->
    <div class="col-12">
        <div class="card">
            <div class="card-header"><h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Trades (Last 20)</h6></div>
            <div class="card-body p-0">
                @php
                    $recentTrades = $user->trades()->latest()->take(20)->get();
                @endphp
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Market</th>
                                <th>Side</th>
                                <th>Amount</th>
                                <th>Entry Price</th>
                                <th>Exit Price</th>
                                <th>P&L</th>
                                <th>Status</th>
                                <th>Opened</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentTrades as $trade)
                            <tr>
                                <td class="small"><code>{{ $trade->id }}</code></td>
                                <td class="small">{{ $trade->market_slug ?? $trade->condition_id ?? '—' }}</td>
                                <td>
                                    <span class="badge bg-{{ $trade->side === 'buy' ? 'success' : 'danger' }}">{{ strtoupper($trade->side ?? '—') }}</span>
                                </td>
                                <td class="small">${{ number_format((float)($trade->amount ?? 0), 2) }}</td>
                                <td class="small">{{ $trade->entry_price ? number_format((float)$trade->entry_price, 4) : '—' }}</td>
                                <td class="small">{{ $trade->exit_price ? number_format((float)$trade->exit_price, 4) : '—' }}</td>
                                <td class="small fw-semibold {{ (float)($trade->pnl ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                    ${{ number_format((float)($trade->pnl ?? 0), 2) }}
                                </td>
                                <td>
                                    @php
                                        $statusColors = ['open' => 'primary', 'won' => 'success', 'lost' => 'danger', 'closed' => 'secondary', 'cancelled' => 'dark'];
                                    @endphp
                                    <span class="badge bg-{{ $statusColors[$trade->status] ?? 'secondary' }}">{{ $trade->status ?? '—' }}</span>
                                </td>
                                <td class="small text-muted">{{ $trade->created_at->format('M j H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">No trades recorded yet.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
