<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>New Student Enrollment</title>


    <style>
        /* Client resets */
        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        a {
            text-decoration: none;
        }

        /* iOS blue link fix */
        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important;
        }

        /* Dark-mode friendly text colors (some clients respect) */
        @media (prefers-color-scheme: dark) {
            .bg-body {
                background: #0b0f12 !important;
            }

            .bg-card {
                background: #141a1f !important;
            }

            .text {
                color: #e7eaf0 !important;
            }

            .muted {
                color: #a8b3c1 !important;
            }

            .divider {
                border-color: #2a323a !important;
            }
        }

        /* Mobile tweaks */
        @media screen and (max-width:620px) {
            .container {
                width: 100% !important;
            }

            .px {
                padding-left: 20px !important;
                padding-right: 20px !important;
            }

            .stack {
                display: block !important;
                width: 100% !important;
            }

            .btn {
                width: 100% !important;
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background:#f4f4f6;" class="bg-body">
    <!-- Preheader (hidden preview text) -->
    <div style="display:none; max-height:0; overflow:hidden; opacity:0; mso-hide:all;">
        A new student just enrolled in your course. View the details and manage enrollment.
    </div>

    <table role="presentation" width="100%" style="background:#f4f4f6;" class="bg-body">
        <tr>
            <td align="center" style="padding:24px 12px;">
                <!-- Card -->
                <table role="presentation" width="600" class="container"
                    style="width:600px; max-width:600px; background:#ffffff; border-radius:10px; overflow:hidden; box-shadow:0 4px 12px rgba(0,0,0,0.08);"
                    cellspacing="0" cellpadding="0">
                    <!-- Header -->
                    <tr>
                        <td align="center"
                            style="padding:36px 24px; color:#ffffff; background:#10b981;
              background:linear-gradient(135deg,#10b981 0%,#059669 100%);">
                            <!-- Circle icon (emoji fallback) -->
                            <table role="presentation" cellspacing="0" cellpadding="0">
                                <tr>
                                    <td align="center"
                                        style="width:64px; height:64px; border-radius:32px; background:rgba(255,255,255,0.18); font-size:32px; line-height:64px;">
                                        🎓
                                    </td>
                                </tr>
                            </table>
                            <div
                                style="font-family:Arial,Helvetica,sans-serif; font-size:26px; font-weight:700; line-height:1.3; margin-top:16px;">
                                New Student Enrollment
                            </div>
                        </td>
                    </tr>

                    <!-- Greeting / Intro -->
                    <tr>
                        <td class="px"
                            style="padding:28px 28px 8px 28px; font-family:Arial,Helvetica,sans-serif; color:#333333;"
                            align="left">
                            <div class="text"
                                style="font-size:20px; font-weight:700; color:#10b981; margin-bottom:6px;">
                                Hi {{ $owner->full_name ?? 'Business Owner' }},
                            </div>
                            <div class="text" style="font-size:16px; line-height:1.6;">
                                Great news! A new student has enrolled in one of your courses. Here are the details:
                            </div>
                        </td>
                    </tr>

                    <!-- Student Info Card -->
                    <tr>
                        <td class="px" style="padding:16px 28px 0 28px;" align="left">
                            <table role="presentation" width="100%"
                                style="background:#f0fdf4; border-left:4px solid #10b981; border-radius:6px;">
                                <tr>
                                    <td style="padding:18px 18px;">
                                        <div
                                            style="font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:700; color:#333333; margin-bottom:10px;">
                                            Student Information</div>
                                        <table role="presentation" width="100%">
                                            <tr>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    Name</td>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    {{ $student->first_name }} {{ $student->last_name }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    Email</td>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    {{ $student->email }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0;">
                                                    Enrolled</td>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0;">
                                                    {{ now()->format('F j, Y \\a\\t g:i A') }}
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Course Info Card -->
                    <tr>
                        <td class="px" style="padding:16px 28px 0 28px;" align="left">
                            <table role="presentation" width="100%"
                                style="background:#f8fafc; border-left:4px solid #0ea5e9; border-radius:6px;">
                                <tr>
                                    <td style="padding:18px 18px;">
                                        <div
                                            style="font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:700; color:#333333; margin-bottom:10px;">
                                            Course Information</div>
                                        <table role="presentation" width="100%">
                                            <tr>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    Course</td>
                                                <td class="stack"
                                                    style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                    {{ $course->title }}
                                                </td>
                                            </tr>
                                            @if ($course->level)
                                                <tr>
                                                    <td class="stack"
                                                        style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                        Level</td>
                                                    <td class="stack"
                                                        style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0; border-bottom:1px solid #e5e7eb;">
                                                        {{ ucfirst($course->level) }}
                                                    </td>
                                                </tr>
                                            @endif
                                            @if ($course->duration)
                                                <tr>
                                                    <td class="stack"
                                                        style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#555555; width:120px; padding:8px 0;">
                                                        Duration</td>
                                                    <td class="stack"
                                                        style="font-family:Arial,Helvetica,sans-serif; font-size:14px; color:#333333; padding:8px 0;">
                                                        {{ $course->duration }}
                                                    </td>
                                                </tr>
                                            @endif
                                        </table>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <!-- Body text + CTA -->
                    <tr>
                        <td class="px"
                            style="padding:20px 28px 8px 28px; font-family:Arial,Helvetica,sans-serif; color:#333333;"
                            align="left">
                            <div class="text" style="font-size:16px; line-height:1.6;">
                                You can manage your courses and view all enrolled students from your dashboard.
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td align="center" style="padding:8px 28px 24px 28px;">
                            <![if !mso]><a class="btn"
                                href="{{ config('app.frontend_url', config('app.url')) }}/dashboard"
                                style="display:inline-block; padding:12px 28px; background:#10b981; color:#ffffff; border-radius:6px; font-family:Arial,Helvetica,sans-serif; font-size:16px; font-weight:700;">
                                View Dashboard
                            </a>
                            <![endif]>
                        </td>
                    </tr>

                    <tr>
                        <td class="px"
                            style="padding:0 28px 28px 28px; font-family:Arial,Helvetica,sans-serif; color:#333333;"
                            align="left">
                            <div class="text" style="font-size:16px; line-height:1.6;">
                                Keep up the great work!<br />
                                <strong>The {{ config('app.name') }} Team</strong>
                            </div>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" style="background:#f8f9fa; padding:18px 20px;">
                            <div class="muted"
                                style="font-family:Arial,Helvetica,sans-serif; font-size:13px; color:#666666; line-height:1.6;">
                                &copy; {{ date('Y') }} {{ config('app.name') }}. All rights reserved.<br />
                                You’re receiving this email because you are a College owner on our platform.
                            </div>
                        </td>
                    </tr>
                </table>
                <!-- /Card -->
            </td>
        </tr>
    </table>
</body>

</html>
