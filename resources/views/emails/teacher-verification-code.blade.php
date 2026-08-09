<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Your verification code</title>
</head>
<body style="font-family: Arial, sans-serif; background:#F6F4EF; padding:32px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #E5E0D5;">
        <h2 style="color:#0D3B2E; margin-top:0;">{{ $schoolName }}</h2>
        <p>Hi {{ $teacherName }},</p>
        <p>Use the code below to finish logging in. It expires in 5 minutes.</p>
        <p style="font-size:28px; font-weight:bold; letter-spacing:4px; text-align:center; background:#F1EBFB; color:#5B3A9E; padding:16px; border-radius:6px;">
            {{ $code }}
        </p>
        <p style="font-size:13px; color:#777;">If you didn't try to log in, you can safely ignore this email.</p>
    </div>
</body>
</html>
