<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        @page {
            size: A4;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        /* Preserve empty paragraphs in rich text content */
        .prose p {
            min-height: 1.5em;
        }
        .prose p:empty::before {
            content: '\00a0';
        }
        .prose p br {
            display: block;
            content: "";
        }
    </style>
</head>
<body class="bg-white">
    <div class="max-w-4xl mx-auto">
        @if($template === 'classic')
            @include('pdf.browsershot.classic')
        @elseif($template === 'modern')
            @include('pdf.browsershot.modern')
        @else
            @include('pdf.browsershot.minimal')
        @endif
    </div>
</body>
</html>
