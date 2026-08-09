<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Entrance exam scheduled</title>
</head>
<body style="font-family: Arial, sans-serif; background:#F6F4EF; padding:32px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #E5E0D5;">
        <h2 style="color:#0D3B2E; margin-top:0;">{{ $schoolName }}</h2>
        <p>Hi {{ $studentName }},</p>
        <p>Your entrance exam has been scheduled{{ $examDate ? ' for '.$examDate->format('F j, Y') : '' }}.</p>
        <p>Log in to your applicant dashboard to view the exam and submit your answers online:</p>
        <p style="text-align:center; margin:24px 0;">
            <a href="{{ $loginUrl }}" style="background:#5B3A9E; color:#fff; padding:12px 24px; border-radius:6px; text-decoration:none; font-weight:bold;">Log In to Take Exam</a>
        </p>
        <p style="font-size:13px; color:#777;">If you weren't expecting this, please contact the school office.</p>
    </div>
</body>
</html>
