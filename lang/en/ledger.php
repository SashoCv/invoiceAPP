<?php

return [
    'title' => 'Trade Ledger',
    'subtitle' => 'Form ET — daily trade record',

    // Filters
    'date_from' => 'From',
    'date_to' => 'To',
    'filter' => 'Show',
    'today' => 'Today',
    'this_month' => 'This month',
    'this_year' => 'This year',
    'export_pdf' => 'Print / PDF',

    // Table
    'row_no' => 'No.',
    'booking_date' => 'Booking date',
    'document' => 'Document name',
    'document_number' => 'Document number',
    'document_date' => 'Document date',
    'purchase_value' => 'Purchase value',
    'sales_value' => 'Sales value',
    'daily_turnover' => 'Daily turnover',
    'period_total' => 'Period total',
    'grand_total' => 'Grand total',
    'grand_total_hint' => 'Cumulative from January 1st through the end of the selected period',
    'margin_hint' => 'Difference (Sales − Purchase value) = :amount MKD. Goods issues (gratis/promotions/advertising) carry no purchase value here (already recorded on the receipt) and no daily turnover (nothing was paid), but their sales column shows the cost value of what was given away — for a record of how much was distributed as gratis.',
    'no_rows' => 'No records for the selected period',

    // Document types
    'type_receipt' => 'Goods receipt',
    'type_issue' => 'Goods issue',
    'type_invoice' => 'Invoice',
    'type_shopify' => 'Shopify',
    'type_fiscal' => 'Daily fiscal report',

    // Daily fiscal reports
    'fiscal_reports' => 'Daily fiscal reports',
    'fiscal_reports_hint' => 'Daily turnover is computed automatically from invoices (sent/paid/overdue) and paid Shopify orders. Enter a manual daily fiscal (Z) report to override the amount for that day.',
    'add_fiscal_report' => 'Add daily report',
    'edit_fiscal_report' => 'Edit daily report',
    'fiscal_date' => 'Date',
    'fiscal_number' => 'Report number',
    'fiscal_amount' => 'Amount (turnover)',
    'fiscal_notes' => 'Note',
    'delete_fiscal_title' => 'Delete daily report',
    'delete_fiscal_confirm' => 'Are you sure you want to delete this daily report?',
    'no_fiscal_reports' => 'No manual daily reports',
];
