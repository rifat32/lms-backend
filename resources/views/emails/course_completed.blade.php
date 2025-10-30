<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Course Completion</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f8fa;
            color: #333;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 40px auto;
            background: #ffffff;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #4CAF50;
            padding-bottom: 10px;
        }
        .header h1 {
            color: #4CAF50;
        }
        .content {
            margin-top: 25px;
            line-height: 1.6;
            font-size: 16px;
        }
        .button {
            display: inline-block;
            background: #4CAF50;
            color: #fff;
            padding: 12px 20px;
            margin-top: 25px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            font-size: 13px;
            text-align: center;
            color: #777;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🎓 Congratulations!</h1>
        </div>

        <div class="content">
            <p>Dear {{ $user->name }},</p>
            <p>We’re thrilled to inform you that you’ve successfully completed the course:</p>

            <h2 style="color: #333;">{{ $course->title }}</h2>

            <p>Your hard work and dedication have truly paid off. Great job!</p>

            <a href="{{ url('/courses/' . $course->id . '/certificate') }}" class="button">
                Download Your Certificate
            </a>

            <p style="margin-top: 30px;">Keep learning and reaching new heights!</p>
        </div>

        <div class="footer">
            <p>— The {{ config('app.name') }} Team</p>
        </div>
    </div>
</body>
</html>
