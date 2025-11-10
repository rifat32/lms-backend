<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title>Reset Your Password</title>
    <!-- Basic resets -->
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

<body style="margin:0; padding:0; background-color:#f6f7fb; font-family:'Segoe UI', Arial, sans-serif;">
    <!-- Preheader (hidden) -->
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;mso-hide:all;">
        Use this link to reset your password. It expires in 5 minutes.
    </div>

    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" bgcolor="#f6f7fb">
        <tr>
            <td align="center" style="padding:24px 0;">
                <table role="presentation" width="600" class="container" cellspacing="0" cellpadding="0"
                    style="width:600px;max-width:600px;background:#ffffff;border-radius:8px;">
                    <!-- Header -->
                    <tr>
                        <td align="center" bgcolor="#0078d7"
                            style="padding:28px 20px;color:#ffffff;border-top-left-radius:8px;border-top-right-radius:8px;">
                            <h1 style="margin:0;font-size:22px;line-height:1.3;font-weight:700;">Reset Your Password
                            </h1>
                        </td>
                    </tr>

                    <!-- Body -->
                    <tr>
                        <td class="px" style="padding:28px;color:#333333;font-size:14px;line-height:1.6;">
                            <p style="margin:0 0 14px;">Hello {{ $user->full_name ?? 'User' }},</p>

                            <p style="margin:0 0 14px;">We received a request to reset your password. Click the button
                                below to set a new one.</p>

                            <!-- Bulletproof button -->
                            <table role="presentation" align="center" cellpadding="0" cellspacing="0"
                                style="margin:18px 0 8px;">
                                <tr>
                                    <td align="center" bgcolor="#005fa3" style="border-radius:6px;">
                                        <!--[if mso]>
                      <v:roundrect xmlns:v="urn:schemas-microsoft-com:vml" href="{{ $url }}" style="height:44px;v-text-anchor:middle;width:220px;" arcsize="12%" stroke="f" fillcolor="#005fa3">
                        <w:anchorlock/>
                        <center style="color:#ffffff;font-family:Segoe UI,Arial,sans-serif;font-size:14px;font-weight:600;">Reset Password</center>
                      </v:roundrect>
                    <![endif]-->
                                        <![if !mso]><a class="btn" href="{{ $url }}" target="_blank"
                                            rel="noopener"
                                            style="display:inline-block;padding:12px 22px;background:#005fa3;color:#ffffff !important;border-radius:6px;font-weight:600;font-size:14px;">
                                            Reset Password
                                        </a>
                                        <![endif]>
                                    </td>
                                </tr>
                            </table>

                            <div
                                style="margin-top:14px;font-size:12px;color:#666;line-height:1.5;background:#f2f6ff;border:1px solid #e0e7ff;border-radius:6px;padding:10px 12px;">
                                <strong>Security note:</strong> This link is valid for <strong>5 minutes</strong> from
                                when this email was sent. If it expires, start a new password reset from the sign-in
                                page.
                            </div>

                            <p style="margin-top:16px;">Having trouble with the button? Copy and paste this link into
                                your browser:</p>
                            <p
                                style="margin:0 0 14px;font-family:Consolas,Monaco,'Courier New',monospace;word-break:break-all;">
                                {{ $url }}</p>

                            <p style="margin:0 0 14px;">If you didn’t request this, you can safely ignore this
                                email—your password won’t change.</p>

                            <div style="margin-top:22px;font-size:12px;color:#888;text-align:center;">
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
