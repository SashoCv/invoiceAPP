<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Expense;
use App\Models\Invoice;
use App\Services\CurrencyConverter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $displayCurrency = $user->agency?->display_currency ?? 'MKD';
        $converter = new CurrencyConverter();

        // Date range filter
        $from = $request->get('from', Carbon::now()->startOfYear()->format('Y-m-d'));
        $to = $request->get('to', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $fromDate = Carbon::parse($from)->startOfDay();
        $toDate = Carbon::parse($to)->endOfDay();

        $totalInvoices = $user->invoices()->count();
        $paidInvoices = $user->invoices()->where('status', 'paid')->count();
        $pendingInvoices = $user->invoices()->whereIn('status', ['draft', 'sent'])->count();
        $overdueInvoices = $user->invoices()->where('status', 'overdue')->count();

        // Revenue — convert each invoice individually
        $paidInvoiceRows = $user->invoices()->where('status', 'paid')
            ->whereBetween('issue_date', [$fromDate, $toDate])
            ->get(['total', 'currency', 'issue_date']);

        $totalRevenue = $paidInvoiceRows->sum(fn ($inv) =>
            $converter->convert($inv->total, $inv->currency, $displayCurrency, $inv->issue_date)
        );

        // Pending & overdue amounts — convert each invoice
        $pendingInvoiceRows = $user->invoices()->whereIn('status', ['draft', 'sent'])
            ->get(['total', 'currency', 'issue_date']);
        $pendingAmount = $pendingInvoiceRows->sum(fn ($inv) =>
            $converter->convert($inv->total, $inv->currency, $displayCurrency, $inv->issue_date)
        );

        $overdueInvoiceRows = $user->invoices()->where('status', 'overdue')
            ->get(['total', 'currency', 'issue_date']);
        $overdueAmount = $overdueInvoiceRows->sum(fn ($inv) =>
            $converter->convert($inv->total, $inv->currency, $displayCurrency, $inv->issue_date)
        );

        // Expenses — assumed MKD
        $expenseRows = $user->expenses()
            ->whereBetween('date', [$fromDate, $toDate])
            ->get(['amount', 'date']);
        $totalExpenses = $expenseRows->sum(fn ($exp) =>
            $converter->convert($exp->amount, 'MKD', $displayCurrency, $exp->date)
        );

        $totalClients = $user->clients()->count();

        // Monthly revenue + expenses for chart
        $monthlyData = [];
        $current = $fromDate->copy()->startOfMonth();
        $end = $toDate->copy()->startOfMonth();

        while ($current->lte($end)) {
            $monthInvoices = $user->invoices()->where('status', 'paid')
                ->whereYear('issue_date', $current->year)
                ->whereMonth('issue_date', $current->month)
                ->get(['total', 'currency', 'issue_date']);

            $revenue = $monthInvoices->sum(fn ($inv) =>
                $converter->convert($inv->total, $inv->currency, $displayCurrency, $inv->issue_date)
            );

            $monthExpenses = $user->expenses()
                ->whereYear('date', $current->year)
                ->whereMonth('date', $current->month)
                ->get(['amount', 'date']);

            $expenses = $monthExpenses->sum(fn ($exp) =>
                $converter->convert($exp->amount, 'MKD', $displayCurrency, $exp->date)
            );

            $monthlyData[] = [
                'month' => $current->translatedFormat('M Y'),
                'revenue' => round($revenue, 2),
                'expenses' => round($expenses, 2),
            ];

            $current->addMonth();
        }

        // Recent invoices with converted totals
        $recentInvoices = $user->invoices()->with('client')
            ->latest()
            ->take(5)
            ->get();

        $recentInvoices->each(function ($invoice) use ($converter, $displayCurrency) {
            $invoice->converted_total = round(
                $converter->convert($invoice->total, $invoice->currency, $displayCurrency, $invoice->issue_date),
                2
            );
        });

        // Invoice status distribution
        $statusDistribution = [
            'paid' => $paidInvoices,
            'pending' => $pendingInvoices,
            'overdue' => $overdueInvoices,
        ];

        return Inertia::render('Dashboard', [
            'totalInvoices' => $totalInvoices,
            'paidInvoices' => $paidInvoices,
            'pendingInvoices' => $pendingInvoices,
            'overdueInvoices' => $overdueInvoices,
            'totalRevenue' => round($totalRevenue, 2),
            'totalExpenses' => round($totalExpenses, 2),
            'pendingAmount' => round($pendingAmount, 2),
            'overdueAmount' => round($overdueAmount, 2),
            'totalClients' => $totalClients,
            'monthlyData' => $monthlyData,
            'recentInvoices' => $recentInvoices,
            'statusDistribution' => $statusDistribution,
            'displayCurrency' => $displayCurrency,
            'from' => $fromDate->format('Y-m-d'),
            'to' => $toDate->format('Y-m-d'),
        ]);
    }
}
