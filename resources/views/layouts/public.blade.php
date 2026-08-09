<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'AxisPro School Management System')</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Cormorant+Garamond:wght@700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    @if (session('status'))
        <div class="card card-padded" style="margin:16px 40px; background:#F1EBFB; border-left:4px solid #5B3A9E;">
            {{ session('status') }}
        </div>
    @endif

    @yield('content')

    <footer class="site-signature">
        <p>
            <a href="https://www.facebook.com/share/19E5UdnunZ/" target="_blank" rel="noopener noreferrer" style="color:inherit; text-decoration:none;">
                <img src="{{ asset('images/axispro-logo.webp') }}" alt="AxisPro" style="height:16px; width:16px; object-fit:contain; vertical-align:middle; margin-right:4px;">
                Powered by <strong>AxisPro School Management System</strong>
            </a>
        </p>
    </footer>
</body>
</html>
