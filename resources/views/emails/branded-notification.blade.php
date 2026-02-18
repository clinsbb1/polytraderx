<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subjectLine }}</title>
</head>
<body style="margin:0;padding:0;background:#0f172a;font-family:Arial,Helvetica,sans-serif;color:#0f172a;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#0f172a;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:14px;overflow:hidden;">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0ea5e9 0%,#22c55e 100%);padding:22px 24px;color:#ffffff;">
                            <div style="font-size:12px;letter-spacing:1px;opacity:0.9;text-transform:uppercase;">PolyTraderX</div>
                            <div style="font-size:24px;font-weight:700;line-height:1.3;margin-top:8px;">{{ $headline }}</div>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:22px 24px 12px 24px;">
                            @foreach($lines as $line)
                                <p style="margin:0 0 14px 0;font-size:15px;line-height:1.65;color:#334155;">{!! nl2br(e($line)) !!}</p>
                            @endforeach

                            @if($actionText && $actionUrl)
                                <div style="margin:20px 0 18px 0;">
                                    <a href="{{ $actionUrl }}" style="display:inline-block;background:#0ea5e9;color:#ffffff;text-decoration:none;padding:11px 18px;border-radius:8px;font-weight:700;font-size:14px;">
                                        {{ $actionText }}
                                    </a>
                                </div>
                            @endif

                            @if(!empty($meta))
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border:1px solid #e2e8f0;border-radius:10px;background:#f8fafc;margin:8px 0 14px 0;">
                                    @foreach($meta as $label => $value)
                                        <tr>
                                            <td style="padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#64748b;width:40%;">{{ $label }}</td>
                                            <td style="padding:10px 14px;border-bottom:1px solid #e2e8f0;font-size:13px;color:#0f172a;font-weight:600;">{{ $value }}</td>
                                        </tr>
                                    @endforeach
                                </table>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:0 24px 22px 24px;">
                            <p style="margin:0;font-size:12px;color:#94a3b8;line-height:1.5;">
                                {{ $smallPrint ?? 'This is an automated message from PolyTraderX.' }}
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
