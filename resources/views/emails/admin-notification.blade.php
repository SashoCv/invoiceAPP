<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #374151;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }
        .header h2 {
            margin: 0;
            color: #111827;
            font-size: 20px;
        }
        .body-content {
            margin: 24px 0;
        }
        .body-content h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        .body-content h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #111827;
        }
        .body-content p {
            margin-bottom: 0.5rem;
        }
        .body-content ul, .body-content ol {
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
        }
        .body-content ul {
            list-style-type: disc;
        }
        .body-content ol {
            list-style-type: decimal;
        }
        .body-content blockquote {
            border-left: 3px solid #e5e7eb;
            padding-left: 1rem;
            margin-left: 0;
            color: #6b7280;
        }
        .footer {
            border-top: 1px solid #e5e7eb;
            padding-top: 16px;
            margin-top: 32px;
            font-size: 12px;
            color: #9ca3af;
        }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ config('app.name') }}</h2>
    </div>

    <div class="body-content">
        {!! $emailBody !!}
    </div>

    <div class="footer">
        &copy; {{ date('Y') }} {{ config('app.name') }}
    </div>
</body>
</html>
