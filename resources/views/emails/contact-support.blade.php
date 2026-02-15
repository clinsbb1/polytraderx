<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Support Request</title>
</head>
<body style="font-family:Arial,Helvetica,sans-serif;background:#f8fafc;color:#0f172a;padding:20px;">
    <div style="max-width:700px;margin:0 auto;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:20px;">
        <h2 style="margin-top:0;">New Contact Request</h2>

        <table style="width:100%;border-collapse:collapse;margin-bottom:16px;">
            <tr>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#64748b;">Name</td>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;">{{ $user->name }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#64748b;">Email</td>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;">{{ $user->email }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#64748b;">Account ID</td>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;">{{ $user->account_id }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#64748b;">Plan</td>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;">{{ ucfirst((string) $user->subscription_plan) }}</td>
            </tr>
            <tr>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;color:#64748b;">Topic</td>
                <td style="padding:8px;border-bottom:1px solid #e2e8f0;font-weight:600;">{{ ucfirst($topic) }}</td>
            </tr>
            <tr>
                <td style="padding:8px;color:#64748b;">Submitted At</td>
                <td style="padding:8px;font-weight:600;">{{ now()->toDayDateTimeString() }}</td>
            </tr>
        </table>

        <h3 style="margin:0 0 8px 0;">Message</h3>
        <div style="white-space:pre-wrap;line-height:1.6;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:12px;">{{ $issueMessage }}</div>

        @if($screenshot)
            <p style="margin:14px 0 0 0;color:#64748b;font-size:13px;">
                Screenshot attached: <strong>{{ $screenshot->getClientOriginalName() }}</strong>
            </p>
        @endif
    </div>
</body>
</html>
