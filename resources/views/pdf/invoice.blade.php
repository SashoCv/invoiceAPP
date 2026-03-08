<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $title }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10pt;
            color: #111827;
            line-height: 1.4;
        }

        .page {
            padding: 30px;
        }

        /* Classic Template Styles */
        .classic .header {
            display: table;
            width: 100%;
            border-bottom: 3px solid #1e40af;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .classic .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .classic .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .classic .company-name {
            font-size: 16pt;
            font-weight: bold;
            color: #1e40af;
            margin-bottom: 8px;
        }

        .classic .company-info {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .classic .doc-title {
            font-size: 22pt;
            font-weight: bold;
            color: #1e40af;
        }

        .classic .doc-number {
            font-size: 10pt;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Info Section */
        .classic .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .classic .info-left,
        .classic .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .classic .section-title {
            font-size: 9pt;
            font-weight: bold;
            color: #1e40af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }

        .classic .client-name {
            font-size: 11pt;
            font-weight: bold;
            color: #111827;
            margin-bottom: 5px;
        }

        .classic .client-info {
            font-size: 9pt;
            color: #6b7280;
            line-height: 1.5;
        }

        .classic .details-table {
            font-size: 9pt;
        }

        .classic .details-table td {
            padding: 3px 0;
        }

        .classic .details-table .label {
            color: #6b7280;
            padding-right: 15px;
        }

        .classic .details-table .value {
            font-weight: bold;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .classic .items-table th {
            background-color: #1e40af;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 12px 10px;
            text-align: left;
        }

        .classic .items-table th.right {
            text-align: right;
        }

        .classic .items-table td {
            padding: 10px;
            font-size: 9pt;
            border-bottom: 1px solid #e5e7eb;
        }

        .classic .items-table td.right {
            text-align: right;
        }

        .classic .items-table tr:nth-child(even) td {
            background-color: #f9fafb;
        }

        /* Totals */
        .classic .totals {
            width: 250px;
            margin-left: auto;
            margin-bottom: 20px;
        }

        .classic .totals-row {
            display: table;
            width: 100%;
            padding: 4px 0;
        }

        .classic .totals-row .label {
            display: table-cell;
            text-align: right;
            color: #6b7280;
            font-size: 9pt;
            padding-right: 15px;
        }

        .classic .totals-row .value {
            display: table-cell;
            text-align: right;
            font-size: 9pt;
        }

        .classic .totals-row.total {
            border-top: 2px solid #1e40af;
            margin-top: 8px;
            padding-top: 8px;
        }

        .classic .totals-row.total .label,
        .classic .totals-row.total .value {
            font-size: 12pt;
            font-weight: bold;
            color: #1e40af;
        }

        /* Bank Info */
        .classic .bank-section {
            margin-bottom: 15px;
        }

        .classic .bank-info {
            font-size: 9pt;
            color: #4b5563;
        }

        .classic .bank-name {
            font-weight: bold;
            margin-bottom: 3px;
        }

        /* Notes */
        .classic .notes-section {
            border-top: 1px solid #e5e7eb;
            padding-top: 15px;
            margin-top: 15px;
        }

        .classic .notes-text {
            font-size: 9pt;
            color: #6b7280;
            white-space: pre-wrap;
        }

        /* Footer */
        .classic .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 8pt;
            color: #9ca3af;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }

        /* Modern Template Styles */
        .modern .header {
            background-color: #7c3aed;
            color: white;
            padding: 25px;
            margin: -30px -30px 0 -30px;
        }

        .modern .header-content {
            display: table;
            width: 100%;
        }

        .modern .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: middle;
        }

        .modern .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: middle;
            text-align: right;
        }

        .modern .company-name {
            font-size: 18pt;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .modern .company-info {
            font-size: 9pt;
            opacity: 0.85;
            line-height: 1.5;
        }

        .modern .doc-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 15px 25px;
            border-radius: 12px;
        }

        .modern .doc-title {
            font-size: 18pt;
            font-weight: bold;
        }

        .modern .doc-number {
            font-size: 9pt;
            opacity: 0.85;
            margin-top: 5px;
        }

        /* Modern Cards */
        .modern .cards-section {
            display: table;
            width: 100%;
            margin: 20px 0;
            table-layout: fixed;
        }

        .modern .card {
            display: table-cell;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            vertical-align: top;
        }

        .modern .card:first-child {
            border-radius: 8px 0 0 8px;
        }

        .modern .card:last-child {
            border-radius: 0 8px 8px 0;
        }

        .modern .card-label {
            font-size: 8pt;
            font-weight: bold;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .modern .card-value {
            font-weight: bold;
            color: #111827;
        }

        .modern .card-subvalue {
            font-size: 9pt;
            color: #6b7280;
            margin-top: 3px;
        }

        .modern .card-amount {
            font-size: 16pt;
            font-weight: bold;
        }

        /* Modern Table */
        .modern .items-table th {
            background-color: #8b5cf6;
            color: white;
            font-size: 9pt;
            font-weight: bold;
            padding: 12px 15px;
            text-align: left;
        }

        .modern .items-table th.right {
            text-align: right;
        }

        .modern .items-table td {
            padding: 12px 15px;
            font-size: 9pt;
            border-bottom: 1px solid #f3f4f6;
        }

        .modern .items-table td.right {
            text-align: right;
        }

        .modern .items-table tr:nth-child(even) td {
            background-color: #faf5ff;
        }

        /* Modern Totals */
        .modern .totals-section {
            background: #f9fafb;
            padding: 15px;
            margin-top: -1px;
        }

        .modern .totals {
            width: 250px;
            margin-left: auto;
        }

        .modern .totals-row {
            display: table;
            width: 100%;
            padding: 4px 0;
        }

        .modern .totals-row .label,
        .modern .totals-row .value {
            display: table-cell;
            font-size: 9pt;
            color: #6b7280;
        }

        .modern .totals-row .label {
            text-align: left;
        }

        .modern .totals-row .value {
            text-align: right;
        }

        .modern .totals-row.total {
            border-top: 2px solid #c4b5fd;
            margin-top: 8px;
            padding-top: 8px;
        }

        .modern .totals-row.total .label,
        .modern .totals-row.total .value {
            font-size: 14pt;
            font-weight: bold;
            color: #7c3aed;
        }

        /* Modern Bank & Notes */
        .modern .bottom-cards {
            display: table;
            width: 100%;
            margin-top: 20px;
        }

        .modern .bottom-card {
            display: table-cell;
            width: 50%;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            padding: 15px;
            vertical-align: top;
        }

        .modern .bottom-card:first-child {
            border-radius: 8px 0 0 8px;
            border-right: none;
        }

        .modern .bottom-card:last-child {
            border-radius: 0 8px 8px 0;
        }

        .modern .bottom-card-label {
            font-size: 8pt;
            font-weight: bold;
            color: #7c3aed;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        /* Formal Template Styles */
        .formal .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }

        .formal .header-left {
            display: table-cell;
            width: 33%;
            vertical-align: top;
        }

        .formal .header-right {
            display: table-cell;
            width: 67%;
            vertical-align: top;
            text-align: right;
            font-size: 9pt;
        }

        .formal .items-table th {
            font-size: 9pt;
            font-weight: bold;
            padding: 6px 3px;
            text-align: left;
        }

        .formal .items-table th.right {
            text-align: right;
        }

        .formal .items-table td {
            padding: 5px 3px;
            font-size: 9pt;
        }

        .formal .items-table td.right {
            text-align: right;
        }

        /* Minimal Template Styles */
        .minimal .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .minimal .header-left {
            display: table-cell;
            width: 60%;
            vertical-align: top;
        }

        .minimal .header-right {
            display: table-cell;
            width: 40%;
            vertical-align: top;
            text-align: right;
        }

        .minimal .company-name {
            font-size: 14pt;
            color: #374151;
            margin-bottom: 8px;
        }

        .minimal .company-info {
            font-size: 9pt;
            color: #9ca3af;
            line-height: 1.5;
        }

        .minimal .doc-label {
            font-size: 9pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .minimal .doc-number {
            font-size: 16pt;
            color: #374151;
            margin-top: 5px;
        }

        /* Minimal Info */
        .minimal .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .minimal .info-left,
        .minimal .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .minimal .section-label {
            font-size: 8pt;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
        }

        .minimal .client-name {
            font-size: 11pt;
            color: #374151;
            margin-bottom: 5px;
        }

        .minimal .client-info {
            font-size: 9pt;
            color: #9ca3af;
            line-height: 1.5;
        }

        /* Minimal Table */
        .minimal .items-table {
            border-top: 1px solid #e5e7eb;
        }

        .minimal .items-table th {
            font-size: 8pt;
            font-weight: normal;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 12px 0;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }

        .minimal .items-table th.right {
            text-align: right;
        }

        .minimal .items-table td {
            padding: 12px 0;
            font-size: 10pt;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .minimal .items-table td.right {
            text-align: right;
        }

        /* Minimal Totals */
        .minimal .totals {
            width: 200px;
            margin-left: auto;
            margin-top: 20px;
            padding-top: 20px;
            border-top: 2px solid #111827;
        }

        .minimal .totals-row {
            display: table;
            width: 100%;
            padding: 4px 0;
        }

        .minimal .totals-row .label,
        .minimal .totals-row .value {
            display: table-cell;
            font-size: 9pt;
            color: #9ca3af;
        }

        .minimal .totals-row .value {
            text-align: right;
        }

        .minimal .totals-row.total .label,
        .minimal .totals-row.total .value {
            font-size: 12pt;
            font-weight: bold;
            color: #374151;
            padding-top: 8px;
        }

        /* Minimal Footer */
        .minimal .footer {
            position: fixed;
            bottom: 30px;
            left: 30px;
            right: 30px;
            text-align: center;
            font-size: 8pt;
            color: #d1d5db;
        }
    </style>
</head>
<body>
    <div class="page {{ $template }}">
        @if($template === 'classic')
            @include('pdf.templates.classic')
        @elseif($template === 'modern')
            @include('pdf.templates.modern')
        @elseif($template === 'formal')
            @include('pdf.templates.formal')
        @else
            @include('pdf.templates.minimal')
        @endif
    </div>
</body>
</html>
