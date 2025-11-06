<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Welcome to {{ $business->name }}</title>
</head>

<body style="margin:0;padding:0;background-color:#f5f7fa;font-family:'Segoe UI', Arial, sans-serif;">
    <table align="center" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center" style="padding:30px 15px;">
                <!-- Outer container -->
                <table width="600" cellpadding="0" cellspacing="0"
                    style="border-collapse:collapse;background:#ffffff;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.1);"
                    role="presentation">
                    <!-- Header section -->
                    <tr>
                        <td align="center"
                            style="background:#0078d7;padding:30px 20px;color:#ffffff;border-top-left-radius:10px;border-top-right-radius:10px;">
                            <h1 style="margin:0;font-size:24px;font-weight:600;">Welcome to {{ $business->name }} 🎓
                            </h1>
                        </td>
                    </tr>
                    <!-- Body section -->
                    <tr>
                        <td style="padding:25px 30px;color:#333333;font-size:16px;line-height:1.6;">
                            <h2 style="margin-top:0;font-size:20px;font-weight:600;">Hello
                                {{ $user->getFullNameAttribute() }},</h2>
                            <p style="margin:0 0 15px;">We’re excited to have you join
                                <strong>{{ $business->name }}</strong>! Your student account has been successfully
                                created. You can now explore courses, track progress, and start learning right away.
                            </p>

                            <table cellpadding="0" cellspacing="0" width="100%"
                                style="background:#f0f6ff;border-left:4px solid #0078d7;padding:15px 20px;border-radius:6px;margin:20px 0;"
                                role="presentation">
                                <tr>
                                    <td style="font-size:16px;color:#333333;">
                                        <strong>Your Details:</strong><br>
                                        {{ $user->title ?? '' }} {{ $user->first_name }} {{ $user->last_name }}<br>
                                        Email: {{ $user->email }}<br>
                                        Contact: {{ $business->email ?? 'N/A' }}@if (!empty($business->phone))
                                            | {{ $business->phone }}
                                        @endif
                                        <br>
                                        Website: <a href="{{ $business->web_page }}" target="_blank"
                                            style="color:#0078d7;text-decoration:none;">{{ $business->web_page }}</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:0 0 20px;">Click below to log in and start your journey:</p>
                            <table border="0" cellpadding="0" cellspacing="0" align="center" role="presentation">
                                <tr>
                                    <td align="center" bgcolor="#005fa3" style="border-radius:6px;">
                                        <a href="{{ env('FRONT_END_URL') }}/dashboard" target="_blank"
                                            style="display:inline-block;padding:12px 25px;color:#ffffff;font-size:16px;font-weight:700;text-decoration:none;border-radius:6px;">Dashboard</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin-top:25px;">If you have any questions, just reply to this email — we’re
                                always happy to help.</p>
                            <p style="margin-top:25px;">Happy learning! 🌟<br>— The {{ $business->name }} Team</p>
                        </td>
                    </tr>
                    <!-- Footer section -->
                    <tr>
                        <td
                            style="text-align:center;font-size:13px;color:#888888;padding:20px;border-top:1px solid #eee;">
                            © {{ now()->year }} {{ $business->name }}. All rights reserved.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
