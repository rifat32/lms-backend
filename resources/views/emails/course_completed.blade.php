<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Course Completion</title>
    <style>
        /* Email resets */
        body {
            margin: 0;
            padding: 0;
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%
        }

        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt
        }

        img {
            border: 0;
            outline: none;
            -ms-interpolation-mode: bicubic
        }

        a {
            text-decoration: none
        }

        a[x-apple-data-detectors] {
            color: inherit !important;
            text-decoration: none !important
        }

        @media screen and (max-width:620px) {
            .container {
                width: 100% !important
            }

            .px {
                padding-left: 20px !important;
                padding-right: 20px !important
            }

            .btn {
                width: 100% !important
            }
        }
    </style>
</head>

<body style="margin:0; padding:0; background:#f5f8fa; font-family:Arial, Helvetica, sans-serif; color:#333333;">
    <!-- Preheader (hidden) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">
        You’ve completed {{ $course->title }} — download your certificate and celebrate the win.
    </div>

    <table role="presentation" width="100%" bgcolor="#f5f8fa">
        <tr>
            <td align="center" style="padding:24px 0;">
                <table role="presentation" width="600" class="container"
                    style="width:600px;max-width:600px;background:#ffffff;border-radius:10px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td align="center" style="padding:28px 24px; border-bottom:2px solid #4CAF50;">
                            <!-- Emoji “medal” in a circle (no flex) -->
                            <table role="presentation">
                                <tr>
                                    <td align="center"
                                        style="width:64px;height:64px;border-radius:32px;background:#e8f7ec;font-size:32px;line-height:64px;color:#2e7d32;">
                                        🎓
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size:26px;line-height:1.3;font-weight:700;color:#2e7d32;margin-top:12px;">
                                Congratulations!
                            </div>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="px" style="padding:28px; font-size:16px; line-height:1.6;">
                            <p style="margin:0 0 12px 0;">Dear {{ $user->name }},</p>
                            <p style="margin:0 0 16px 0;">
                                You’ve successfully completed the course:
                            </p>

                            <!-- Course title card -->
                            <table role="presentation" width="100%"
                                style="background:#f7fbff;border-left:4px solid #4CAF50;border-radius:6px;">
                                <tr>
                                    <td style="padding:16px 18px;">
                                        <div style="font-size:18px;font-weight:700;color:#333333;margin:0;">
                                            {{ $course->title }}
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0 0;">
                                Your hard work and dedication have paid off—great job!
                            </p>

                            <!-- Primary CTA: Download Certificate -->
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0"
                                style="margin:18px auto 8px auto;">
                                <tr>
                                    <td align="center" bgcolor="#4CAF50" style="border-radius:6px;">
                                        <![if !mso]><a class="btn"
                                            href="{{ config('app.frontend_url', config('app.url')) }}/dashboard?tab=certificates"
                                            style="display:inline-block;padding:12px 24px;background:#4CAF50;color:#ffffff !important;border-radius:6px;font-weight:700;font-size:16px;line-height:1;">
                                            Download Your Certificate
                                        </a>
                                        <![endif]>
                                    </td>
                                </tr>
                            </table>

                            <!-- Optional secondary CTA (remove if not needed) -->
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0"
                                style="margin:6px auto 0 auto;">
                                <tr>
                                    <td align="center">
                                        <a href="{{ config('app.frontend_url', config('app.url')) }}/dashboard?tab=certificates"
                                            style="display:inline-block;padding:10px 16px;color:#4CAF50;font-weight:600;font-size:14px;">
                                            Explore More Courses →
                                        </a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:18px 0 0 0;">
                                Keep learning and reaching new heights!
                            </p>

                            <p style="margin:18px 0 0 0;">
                                — The <strong>{{ config('app.name') }}</strong> Team
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" bgcolor="#f5f8fa" style="padding:16px 20px; font-size:13px; color:#777777;">
                            <div style="margin:0;">
                                &copy; {{ now()->year }} {{ config('app.name') }}. All rights reserved.
                            </div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
