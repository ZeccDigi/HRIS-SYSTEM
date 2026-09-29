<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DMW HRIS Account Created</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif;">
    <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%" style="max-width:560px;margin:40px auto;background:#ffffff;border-radius:16px;box-shadow:0 4px 24px rgba(0,0,0,0.08);overflow:hidden;">
        <tr>
            <td style="background:#1e3a8a;padding:30px 40px;text-align:center;">
                <h1 style="margin:0;color:#ffffff;font-size:22px;font-weight:700;">DMW HRIS</h1>
                <p style="margin:6px 0 0;color:rgba(255,255,255,0.86);font-size:13px;">Account Created</p>
            </td>
        </tr>
        <tr>
            <td style="padding:34px 40px 24px;color:#374151;font-size:15px;line-height:1.6;">
                <p style="margin:0 0 16px;">Hi <strong>{{ $userName }}</strong>,</p>
                <p style="margin:0 0 16px;">HR has created your DMW HRIS account. You may now log in using the details below.</p>

                <div style="background:#f8fafc;border:1px solid #e5e7eb;border-radius:12px;padding:18px 20px;margin:22px 0;">
                    <p style="margin:0 0 10px;"><strong>HRIS Username:</strong> {{ $hrisUsername }}</p>
                    <p style="margin:0;"><strong>Temporary Password:</strong> {{ $temporaryPassword }}</p>
                </div>

                <p style="margin:0 0 16px;color:#4b5563;font-size:13px;">The @dmw.gov.ph value above is only your HRIS sign-in username. It is not an email inbox.</p>

                <p style="margin:0 0 16px;color:#991b1b;font-weight:600;">For your security, you will be asked to change this temporary password after logging in.</p>

                <div style="margin:28px 0;text-align:center;">
                    <a href="{{ url('/login') }}" style="display:inline-block;background:#1e3a8a;color:#ffffff;text-decoration:none;font-size:15px;font-weight:600;padding:13px 36px;border-radius:10px;">Log In</a>
                </div>

                <p style="margin:0;color:#6b7280;font-size:13px;">If you did not expect this account, please contact HR immediately.</p>
            </td>
        </tr>
        <tr>
            <td style="padding:18px 40px 28px;text-align:center;border-top:1px solid #e5e7eb;">
                <p style="margin:0;color:#9ca3af;font-size:12px;line-height:1.5;">This is an automated message. Please do not reply.</p>
            </td>
        </tr>
    </table>
</body>
</html>
