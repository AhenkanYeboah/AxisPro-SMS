<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
</head>
<body style="font-family: Arial, sans-serif; background:#F6F4EF; padding:32px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #E5E0D5;">
        <h2 style="color:#5B3A9E; margin-top:0;">{{ $schoolName }}</h2>
        <p>Dear Parent/Guardian of {{ $studentName }},</p>
        <h3 style="margin-bottom:8px;">{{ $title }}</h3>
        <p style="white-space:pre-line;">{{ $body }}</p>
        <p style="font-size:13px; color:#777; margin-top:24px;">Please contact the school office if you have any questions.</p>
    </div>
</body>
</html>
