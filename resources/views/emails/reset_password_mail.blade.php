<!DOCTYPE html>
<html>

<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <style>
        /* Basic resets that most clients tolerate */
        body {
            margin: 0;
            padding: 0;
            background: #f6f7fb;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table {
            border-collapse: collapse;
        }

        img {
            border: 0;
            line-height: 100%;
            outline: none;
            text-decoration: none;
        }

        a {
            color: #0b5fff;
            text-decoration: none;
        }

        .container {
            width: 100%;
            background: #f6f7fb;
            padding: 24px 0;
        }

        .card {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.06);
        }

        .inner {
            padding: 28px;
            font-family: Arial, Helvetica, sans-serif;
            color: #333333;
        }

        .h1 {
            margin: 0 0 12px;
            font-size: 22px;
            line-height: 1.3;
            font-weight: 700;
            text-align: center;
        }

        .p {
            margin: 0 0 14px;
            font-size: 14px;
            line-height: 1.6;
        }

        .center {
            text-align: center;
        }

        .btn {
            display: inline-block;
            padding: 12px 22px;
            background: #0b5fff;
            color: #ffffff !important;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .note {
            margin-top: 14px;
            font-size: 12px;
            color: #666666;
            line-height: 1.5;
            background: #f2f6ff;
            border: 1px solid #e0e7ff;
            border-radius: 6px;
            padding: 10px 12px;
        }

        .footer {
            margin-top: 22px;
            font-size: 12px;
            color: #888888;
            text-align: center;
        }

        .mono {
            font-family: Consolas, Monaco, 'Courier New', monospace;
            word-break: break-all;
        }
    </style>
</head>

<body>
    <table role="presentation" class="container" width="100%" cellspacing="0" cellpadding="0">
        <tr>
            <td align="center">
                <table role="presentation" class="card" width="100%" cellspacing="0" cellpadding="0">
                    <tr>
                        <td class="inner">
                            <h1 class="h1">Reset Your Password</h1>

                            <p class="p">Hello {{ $user->full_name ?? 'User' }},</p>

                            <p class="p">We received a request to reset your password. Click the button below to
                                set a new one.</p>

                            <p class="center" style="margin:18px 0 8px;">
                                <a class="btn" href="{{ $url }}" target="_blank" rel="noopener">Reset
                                    Password</a>
                            </p>

                            <div class="note">
                                <strong>Security note:</strong> This link is valid for <strong>5 minutes</strong> from
                                when this email was sent. If it expires, start a new password reset from the sign-in
                                page.
                            </div>

                            <p class="p" style="margin-top:16px;">
                                Having trouble with the button? Copy and paste this link into your browser:
                            </p>
                            <p class="p mono">{{ $url }}</p>

                            <p class="p">If you didn’t request this, you can safely ignore this email—your password
                                won’t change.</p>

                            <div class="footer">
                                &copy; {{ date('Y') }} {{ $user->business->name ?? config('app.name') }}. All rights
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
