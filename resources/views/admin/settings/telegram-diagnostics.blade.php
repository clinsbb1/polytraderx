@extends('layouts.super-admin')

@section('title', 'Telegram Diagnostics')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0">Telegram Diagnostics</h5>
    <div class="d-flex gap-2">
        <a href="/admin/settings/telegram-diagnostics" class="btn btn-primary btn-sm">
            <i class="bi bi-arrow-clockwise me-1"></i>Run Again
        </a>
        <a href="/admin/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>Back to Settings
        </a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header">
        <h6 class="mb-0">Configuration Checks</h6>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width: 260px;">Bot Token Configured</th>
                        <td>
                            @if($report['bot_token_configured'])
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Bot Username</th>
                        <td>{{ $report['bot_username'] ? '@' . $report['bot_username'] : '(empty)' }}</td>
                    </tr>
                    <tr>
                        <th>Webhook Secret Configured</th>
                        <td>
                            @if($report['webhook_secret_configured'])
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-danger">No</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Expected Webhook URL</th>
                        <td><code>{{ $report['expected_webhook_url'] }}</code></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h6 class="mb-0">Telegram API Status</h6>
    </div>
    <div class="card-body">
        @if($report['http_error'])
            <div class="alert alert-danger mb-3">
                <strong>HTTP Error:</strong> {{ $report['http_error'] }}
            </div>
        @endif

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <tbody>
                    <tr>
                        <th style="width: 260px;">getMe.ok</th>
                        <td>{{ is_null($report['get_me_ok']) ? '(not run)' : ($report['get_me_ok'] ? 'true' : 'false') }}</td>
                    </tr>
                    <tr>
                        <th>getMe.username</th>
                        <td>{{ $report['get_me_username'] ? '@' . $report['get_me_username'] : '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>getMe.error</th>
                        <td>{{ $report['get_me_error'] ?? '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>getWebhookInfo.ok</th>
                        <td>{{ is_null($report['get_webhook_info_ok']) ? '(not run)' : ($report['get_webhook_info_ok'] ? 'true' : 'false') }}</td>
                    </tr>
                    <tr>
                        <th>webhook.url</th>
                        <td>{{ $report['webhook_url'] ?? '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>webhook.pending_update_count</th>
                        <td>{{ is_null($report['pending_update_count']) ? '(none)' : $report['pending_update_count'] }}</td>
                    </tr>
                    <tr>
                        <th>webhook.last_error_date</th>
                        <td>{{ $report['last_error_date'] ?? '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>webhook.last_error_message</th>
                        <td>{{ $report['last_error_message'] ?? '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>webhook.ip_address</th>
                        <td>{{ $report['ip_address'] ?? '(none)' }}</td>
                    </tr>
                    <tr>
                        <th>webhook.max_connections</th>
                        <td>{{ $report['max_connections'] ?? '(none)' }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
