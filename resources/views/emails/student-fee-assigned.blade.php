<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>New fee</title>
</head>
<body style="font-family: Arial, sans-serif; background:#F6F4EF; padding:32px;">
    <div style="max-width:480px; margin:0 auto; background:#fff; border-radius:8px; padding:32px; border:1px solid #E5E0D5;">
        <h2 style="color:#5B3A9E; margin-top:0;">{{ $schoolName }}</h2>
        <p>Dear Parent/Guardian,</p>
        <p>A new fee has been recorded for <strong>{{ $studentName }}</strong>:</p>
        <table style="width:100%; border-collapse:collapse; margin:20px 0;">
            <tr>
                <td style="padding:8px 0; color:#777;">Fee</td>
                <td style="padding:8px 0; text-align:right; font-weight:bold;">{{ $feeName }}</td>
            </tr>
            <tr style="border-top:1px solid #E5E0D5;">
                <td style="padding:8px 0; color:#777;">Amount</td>
                <td style="padding:8px 0; text-align:right; font-weight:bold;">GHS {{ $amount }}</td>
            </tr>
            @if ($dueDate)
                <tr style="border-top:1px solid #E5E0D5;">
                    <td style="padding:8px 0; color:#777;">Due date</td>
                    <td style="padding:8px 0; text-align:right;">{{ $dueDate->format('d M Y') }}</td>
                </tr>
            @endif
        </table>
        <p style="font-size:13px; color:#777;">Please contact the school office regarding payment.</p>
    </div>
</body>
</html>
