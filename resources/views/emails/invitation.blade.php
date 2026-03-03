<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>You're invited to {{ $appName }}</title>
    <style>
        body { margin: 0; padding: 0; background: #f4f4f5; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; }
        .wrapper { max-width: 520px; margin: 40px auto; background: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #0e1340 0%, #2a1f6e 100%); padding: 40px 40px 32px; text-align: center; }
        .header h1 { color: #ffffff; font-size: 22px; font-weight: 700; margin: 0; }
        .header p { color: rgba(255,255,255,0.65); font-size: 14px; margin: 8px 0 0; }
        .body { padding: 36px 40px; }
        .greeting { font-size: 16px; color: #111827; font-weight: 600; margin: 0 0 12px; }
        .message { font-size: 14px; color: #6b7280; line-height: 1.7; margin: 0 0 28px; }
        .btn { display: inline-block; background: #6c63ff; color: #ffffff !important; text-decoration: none; font-size: 15px; font-weight: 700; padding: 14px 32px; border-radius: 12px; }
        .btn-wrap { text-align: center; margin-bottom: 28px; }
        .divider { border: none; border-top: 1px solid #e5e7eb; margin: 28px 0; }
        .meta { font-size: 12px; color: #9ca3af; line-height: 1.6; }
        .meta a { color: #6c63ff; word-break: break-all; }
        .expiry { display: inline-block; background: #fff7ed; color: #c2410c; font-size: 12px; font-weight: 600; padding: 4px 10px; border-radius: 6px; margin-top: 8px; }
        .footer { background: #f9fafb; padding: 20px 40px; text-align: center; font-size: 12px; color: #9ca3af; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="header">
            <h1>{{ $appName }}</h1>
            <p>Workspace Invitation</p>
        </div>
        <div class="body">
            <p class="greeting">Hi {{ $invitation->name }},</p>
            <p class="message">
                You've been invited by <strong>{{ $invitation->invitedBy->name }}</strong> to join
                <strong>{{ $appName }}</strong>
                @if($invitation->role)
                    as <strong>{{ $invitation->role->label }}</strong>
                @endif
                .<br><br>
                Click the button below to accept the invitation and set up your account.
            </p>
            <div class="btn-wrap">
                <a href="{{ $link }}" class="btn">Accept Invitation →</a>
            </div>
            <span class="expiry">⏳ This invitation expires in 72 hours</span>
            <hr class="divider">
            <p class="meta">
                If the button doesn't work, copy and paste this link into your browser:<br>
                <a href="{{ $link }}">{{ $link }}</a>
            </p>
            <p class="meta" style="margin-top:12px;">
                If you weren't expecting this invitation, you can safely ignore this email.
            </p>
        </div>
        <div class="footer">
            © {{ date('Y') }} {{ $appName }}. All rights reserved.
        </div>
    </div>
</body>
</html>