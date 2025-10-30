<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Reset Your Password</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            color: #333;
            padding: 20px;
        }
        .email-container {
            max-width: 600px;
            margin: auto;
            background: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            margin-bottom: 25px;
        }
        .btn {
            display: inline-block;
            padding: 12px 25px;
            background-color: #007bff;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .footer {
            font-size: 12px;
            color: #777;
            margin-top: 30px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h2>Reset Your Password</h2>
        </div>

        <p>Hello {{ $user->name ?? 'User' }},</p>

        <p>We received a request to reset your password. Click the button below to reset it:</p>

        <p style="text-align:center;">
            <a class="btn" href="{{ $url }}">Reset Password</a>
        </p>

        <p>If you did not request a password reset, please ignore this email.</p>

        <div class="footer">
            <p>&copy; {{ date('Y') }} {{ $user->business->name ?? config('app.name') }}. All rights reserved.</p>
        </div>
    </div>
</body>
</html>
