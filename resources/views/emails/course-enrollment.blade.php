<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Course Enrollment Confirmation</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f4f4f6;
            font-family: Arial, Helvetica, sans-serif;
            color: #333333;
        }

        table {
            border-spacing: 0;
            width: 100%;
        }

        .wrapper {
            width: 100%;
            table-layout: fixed;
            background-color: #f4f4f6;
            padding: 20px 0;
        }

        .main {
            background-color: #ffffff;
            max-width: 600px;
            margin: 0 auto;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px 30px;
            text-align: center;
            color: #ffffff;
        }

        .header-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 64px;
            height: 64px;
            margin-bottom: 20px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            font-size: 32px;
            line-height: 1;
        }

        .header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .content {
            padding: 30px;
            font-size: 16px;
            line-height: 1.6;
        }

        .content h2 {
            margin-top: 0;
            color: #667eea;
            font-size: 22px;
            font-weight: 600;
        }

        .course-card {
            background-color: #f8faff;
            border-left: 4px solid #667eea;
            padding: 20px;
            border-radius: 4px;
            margin: 25px 0;
        }

        .course-card h3 {
            margin: 0 0 10px;
            font-size: 20px;
            color: #333333;
            font-weight: 600;
        }

        .course-card p {
            margin: 5px 0;
            font-size: 15px;
            color: #555555;
        }

        .cta {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #ffffff;
            padding: 12px 28px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            margin: 20px 0 0;
        }

        .cta:hover {
            filter: brightness(1.05);
        }

        .footer {
            background-color: #f8f9fa;
            text-align: center;
            padding: 20px;
            font-size: 13px;
            color: #666666;
        }

        .footer p {
            margin: 6px 0;
        }

        /* Responsive adjustments */
        @media (max-width: 620px) {
            .header {
                padding: 30px 20px;
            }

            .content {
                padding: 20px;
            }

            .cta {
                width: 100%;
                text-align: center;
            }
        }
    </style>
</head>

<body>
    <table class="wrapper" role="presentation">
        <tr>
            <td>
                <table class="main" role="presentation">
                    <!-- Header section -->
                    <tr>
                        <td class="header">
                            <div class="header-icon">✓</div>
                            <h1>Enrollment Successful!</h1>
                        </td>
                    </tr>
                    <!-- Content section -->
                    <tr>
                        <td class="content">
                            <h2>Hi {{ $user->first_name ?? 'Student' }},</h2>
                            <p>
                                Congratulations on joining your new course! We're thrilled to have you on
                                board and can't wait to see what you accomplish.
                            </p>
                            <div class="course-card">
                                <h3>{{ $course->title }}</h3>
                                @if ($course->description)
                                    <p>{{ Str::limit($course->description, 150) }}</p>
                                @endif
                                @if ($course->level)
                                    <p><strong>Level:</strong> {{ ucfirst($course->level) }}</p>
                                @endif
                                @if ($course->duration)
                                    <p><strong>Duration:</strong> {{ $course->duration }}</p>
                                @endif
                            </div>
                            <p>
                                You now have full access to all materials, lessons and resources. Dive
                                in and start learning at your own pace.
                            </p>
                            <p style="text-align: center;">
                                <a href="{{ config('app.frontend_url', config('app.url')) }}/dashboard?tab=my-courses"
                                    class="cta">Start Learning Now</a>
                            </p>
                            <p>
                                If you have any questions or need support, feel free to reach out to
                                our team. We're here to help!
                            </p>
                            <p>
                                Happy learning,<br /><strong>The
                                    {{ config('app.name') }} Team</strong>
                            </p>
                        </td>
                    </tr>
                    <!-- Footer section -->
                    <tr>
                        <td class="footer">
                            <p>
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights
                                reserved.
                            </p>
                            <p>
                                You're receiving this email because you enrolled in a course on our
                                platform.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
