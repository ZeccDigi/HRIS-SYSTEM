<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Approved</title>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%"
        style="max-width: 520px; margin: 40px auto; background-color: #ffffff; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.08); overflow: hidden;">

        {{-- Header --}}
        <tr>
            <td style="background: linear-gradient(135deg, #1e3a8a 0%, #1e40af 50%, #2563eb 100%); padding: 32px 40px; text-align: center;">
                <h1 style="margin: 0; color: #ffffff; font-size: 22px; font-weight: 700; letter-spacing: 0.5px;">
                    DMW HRIS
                </h1>
                <p style="margin: 6px 0 0; color: rgba(255,255,255,0.85); font-size: 13px; font-weight: 400;">
                    Account Notification
                </p>
            </td>
        </tr>

        {{-- Body --}}
        <tr>
            <td style="padding: 36px 40px 20px;">
                <p style="margin: 0 0 16px; color: #374151; font-size: 15px; line-height: 1.6;">
                    Hi <strong>{{ $userName }}</strong>,
                </p>

                <p style="margin: 0 0 16px; color: #374151; font-size: 15px; line-height: 1.6;">
                    Great news! Your <strong>DMW HRIS</strong> account has been reviewed and approved by the HR department.
                </p>

                {{-- Status Box --}}
                <div style="margin: 24px 0; text-align: center;">
                    <div style="display: inline-block; background: linear-gradient(135deg, #ecfdf5, #d1fae5); border: 2px solid #34d399; border-radius: 12px; padding: 20px 36px;">
                        <span style="font-size: 16px; font-weight: 700; color: #065f46;">
                            ✅ Your account has been approved!
                        </span>
                    </div>
                </div>

                <p style="margin: 20px 0 0; color: #374151; font-size: 15px; line-height: 1.6;">
                    You can now log in and access the system using the account credentials provided by HR.
                </p>

                {{-- Login Button --}}
                <div style="margin: 28px 0; text-align: center;">
                    <a href="{{ url('/login') }}" style="display: inline-block; background: linear-gradient(135deg, #1e3a8a, #2563eb); color: #ffffff; text-decoration: none; font-size: 15px; font-weight: 600; padding: 14px 40px; border-radius: 10px; letter-spacing: 0.3px;">
                        Log In Now
                    </a>
                </div>
            </td>
        </tr>

        {{-- Divider --}}
        <tr>
            <td style="padding: 0 40px;">
                <hr style="border: none; border-top: 1px solid #e5e7eb; margin: 0;">
            </td>
        </tr>

        {{-- Footer --}}
        <tr>
            <td style="padding: 20px 40px 28px; text-align: center;">
                <p style="margin: 0; color: #9ca3af; font-size: 12px; line-height: 1.5;">
                    This is an automated message. Please do not reply.
                    <br>&copy; {{ date('Y') }} Department of Migrant Workers — HRIS
                </p>
            </td>
        </tr>
    </table>
</body>
</html>
