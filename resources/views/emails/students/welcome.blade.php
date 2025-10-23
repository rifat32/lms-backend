<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to {{ config('app.name') }}</title>
    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Segoe UI', Arial, sans-serif;
            margin: 0;
            padding: 0;
        }
        .email-container {
            max-width: 600px;
            background-color: #ffffff;
            margin: 30px auto;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .email-header {
            background: linear-gradient(135deg, #0078d7, #00a6fb);
            color: #fff;
            text-align: center;
            padding: 30px 20px;
        }
        .email-header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
        }
        .email-body {
            padding: 25px 30px;
            color: #333;
            line-height: 1.7;
        }
        .email-body h2 {
            color: #0078d7;
            margin-top: 0;
        }
        .details {
            background: #f0f6ff;
            border-left: 4px solid #0078d7;
            padding: 15px 20px;
            border-radius: 6px;
            margin: 20px 0;
        }
        .button {
            display: inline-block;
            background-color: #0078d7;
            color: #fff;
            text-decoration: none;
            padding: 12px 25px;
            border-radius: 6px;
            margin-top: 15px;
            font-weight: 500;
        }
        .button:hover {
            background-color: #005fa3;
        }
        .email-footer {
            text-align: center;
            font-size: 13px;
            color: #888;
            padding: 20px;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="email-header">
            <h1>Welcome to {{ config('app.name') }} 🎓</h1>
        </div>
        <div class="email-body">
            <h2>Hello {{ $user->first_name }},</h2>
            <p>
                We’re excited to have you join our learning platform!
                Your student account has been successfully created.
                You can now explore courses, track progress, and start learning right away.
            </p>

            <div class="details">
                <strong>Your Details:</strong><br>
                Name: {{ $user->title }} {{ $user->first_name }} {{ $user->last_name }}<br>
                Email: {{ $user->email }}<br>
                Role: Student
            </div>

            <p>Click below to log in and start your journey:</p>

            <a href="{{ config('app.url') }}/login" class="button">Go to Dashboard</a>

            <p style="margin-top: 25px;">
                If you have any questions, just reply to this email — we’re always happy to help.
                <br><br>
                Happy learning! 🌟
                <br>
                — The {{ config('app.name') }} Team
            </p>
        </div>

        <div class="email-footer">
            &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.
        </div>
    </div>
</body>
</html>
