<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Reset Your Password</title>
</head>

<body style="margin:0; padding:0; background-color:#f6f7fb; font-family:'Segoe UI', Arial, sans-serif;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background-color:#f6f7fb;">
        <tr>
            <td align="center" style="padding:24px 0;">
                <table role="presentation" width="600" cellspacing="0" cellpadding="0"
                    style="border-collapse:collapse; background:#ffffff; border-radius:8px; box-shadow:0 0 10px rgba(0,0,0,0.06);">
                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="background:#0078d7; color:#ffffff; padding:28px 20px; border-top-left-radius:8px; border-top-right-radius:8px;">
                            <h1 style="margin:0; font-size:22px; line-height:1.3; font-weight:700;">Reset Your Password
                            </h1>
                        </td>
                    </tr>
                    <!-- Body -->
                    <tr>
                        <td style="padding:28px; color:#333333; font-size:14px; line-height:1.6;">
                            <p style="margin:0 0 14px;">Hello {{ $user->getFullNameAttribute() ?? 'User' }},</p>
                            <p style="margin:0 0 14px;">We received a request to reset your password. Click the button
                                below to set a new one.</p>
                            <div style="text-align:center; margin:18px 0 8px;">
                                <a href="{{ $url }}" target="_blank" rel="noopener"
                                    style="display:inline-block; padding:12px 22px; background:#005fa3; color:#ffffff !important; border-radius:6px; font-weight:600; font-size:14px; text-decoration:none;">Reset
                                    Password</a>
                            </div>
                            <div
                                style="margin-top:14px; font-size:12px; color:#666666; line-height:1.5; background:#f2f6ff; border:1px solid #e0e7ff; border-radius:6px; padding:10px 12px;">
                                <strong>Security note:</strong> This link is valid for <strong>5 minutes</strong> from
                                when this email was sent. If it expires, start a new password reset from the sign‑in
                                page.
                            </div>
                            <p style="margin-top:16px;">Having trouble with the button? Copy and paste this link into
                                your browser:</p>
                            <p
                                style="margin:0 0 14px; font-family:Consolas, Monaco, 'Courier New', monospace; word-break:break-all;">
                                {{ $url }}</p>
                            <p style="margin:0 0 14px;">If you didn’t request this, you can safely ignore this
                                email—your password won’t change.</p>
                            <p style="margin:0;">&nbsp;</p>
                            <div style="margin-top:22px; font-size:12px; color:#888888; text-align:center;">
                                &copy; {{ now()->year }} {{ $user->business->name ?? config('app.name') }}. All rights
                                reserved.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
