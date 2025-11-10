<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Course Enrollment Confirmation</title>
    <style>
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

<body style="margin:0;padding:0;background:#f4f4f6;font-family:Arial,Helvetica,sans-serif;color:#333333;">
    <table role="presentation" width="100%" bgcolor="#f4f4f6">
        <tr>
            <td align="center" style="padding:20px 0;">
                <table role="presentation" width="600" class="container"
                    style="width:600px;max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">
                    <!-- Header -->
                    <tr>
                        <td align="center" bgcolor="#667eea"
                            style="padding:40px 30px;color:#ffffff;background:#667eea;">
                            <!-- icon: no flex, just a box -->
                            <table role="presentation">
                                <tr>
                                    <td align="center"
                                        style="width:64px;height:64px;border-radius:32px;background:rgba(255,255,255,0.2);font-size:32px;line-height:64px;">
                                        ✓
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size:28px;font-weight:700;margin-top:16px;line-height:1.3;">
                                Enrollment Successful!
                            </div>
                        </td>
                    </tr>

                    <!-- Content -->
                    <tr>
                        <td class="px" style="padding:30px;font-size:16px;line-height:1.6;">
                            <div style="color:#667eea;font-size:22px;font-weight:700;margin:0 0 10px 0;">
                                Hi {{ $user->full_name ?? 'Student' }},
                            </div>

                            <p style="margin:0 0 16px 0;">
                                Congratulations on joining your new course! We're thrilled to have you on board and
                                can't wait to see what you accomplish.
                            </p>

                            <!-- Course card -->
                            <table role="presentation" width="100%"
                                style="background:#f8faff;border-left:4px solid #667eea;border-radius:4px;">
                                <tr>
                                    <td style="padding:20px;">
                                        <div style="font-size:20px;font-weight:700;color:#333333;margin:0 0 10px 0;">
                                            {{ $course->title }}
                                        </div>
                                        @if ($course->description)
                                            <p style="margin:5px 0 10px 0;font-size:15px;color:#555555;">
                                                {{ Str::limit($course->description, 150) }}
                                            </p>
                                        @endif
                                        @if ($course->level)
                                            <p style="margin:5px 0;font-size:15px;color:#555555;">
                                                <strong>Level:</strong> {{ ucfirst($course->level) }}
                                            </p>
                                        @endif
                                        @if ($course->duration)
                                            <p style="margin:5px 0;font-size:15px;color:#555555;">
                                                <strong>Duration:</strong> {{ $course->duration }}
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0;">
                                You now have full access to all materials, lessons and resources. Dive in and start
                                learning at your own pace.
                            </p>

                            <!-- Button (bulletproof) -->
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0"
                                style="margin:10px auto 0 auto;">
                                <tr>
                                    <td align="center" bgcolor="#667eea" style="border-radius:6px;">
                                        <!--[if mso]>
                      <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ config('app.frontend_url', config('app.url')) }}/dashboard?tab=my-courses" style="height:44px;v-text-anchor:middle;width:260px;" arcsize="12%" stroke="f" fillcolor="#667eea">
                        <w:anchorlock/>
                        <center style="color:#ffffff;font-family:Arial,Helvetica,sans-serif;font-size:16px;font-weight:700;">
                          Start Learning Now
                        </center>
                      </v:roundrect>
                    <![endif]-->
                                        <![if !mso]><a class="btn"
                                            href="{{ config('app.frontend_url', config('app.url')) }}/dashboard?tab=my-courses"
                                            style="display:inline-block;padding:12px 28px;color:#ffffff;font-weight:700;font-size:16px;line-height:1;background:#667eea;border-radius:6px;">
                                            Start Learning Now
                                        </a>
                                        <![endif]>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:16px 0 0 0;">
                                If you have any questions or need support, feel free to reach out to our team. We're
                                here to help!
                            </p>
                            <p style="margin:16px 0 0 0;">
                                Happy learning,<br /><strong>The {{ config('app.name') }} Team</strong>
                            </p>
                        </td>
                    </tr>

                    <!-- Footer -->
                    <tr>
                        <td align="center" bgcolor="#f8f9fa" style="padding:20px;font-size:13px;color:#666666;">
                            <p style="margin:0 0 6px 0;">&copy; {{ date('Y') }} {{ config('app.name') }}. All rights
                                reserved.</p>
                            <p style="margin:0;">You're receiving this email because you enrolled in a course on our
                                platform.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>

</html>
