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
        .details {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 16px;
            margin: 24px 0;
        }
        .details table {
            width: 100%;
        }
        .details td {
            padding: 4px 0;
        }
        .details .label {
            color: #6b7280;
            font-size: 14px;
        }
        .details .value {
            text-align: right;
            font-weight: 600;
            font-size: 14px;
        }
        .total {
            font-size: 18px;
            color: #111827;
        }
        .message {
            white-space: pre-wrap;
            margin: 24px 0;
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
        <h2>{{ $docLabel }} {{ $docNumber }}</h2>
    </div>

    <div class="message">{!! nl2br(e($bodyText)) !!}</div>

    <div class="details">
        <table>
            <tr>
                <td class="label">{{ $docLabel }}:</td>
                <td class="value">{{ $docNumber }}</td>
            </tr>
            @if($issueDate)
            <tr>
                <td class="label">{{ __('invoices.issue_date') }}:</td>
                <td class="value">{{ $issueDate }}</td>
            </tr>
            @endif
            @if($dueDate)
            <tr>
                <td class="label">{{ $dueDateLabel }}:</td>
                <td class="value">{{ $dueDate }}</td>
            </tr>
            @endif
            <tr>
                <td class="label total">{{ __('invoices.total') }}:</td>
                <td class="value total">{{ $total }} {{ $currency }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        {{ __('invoices.pdf_attached') }}
    </div>
</body>
</html>
