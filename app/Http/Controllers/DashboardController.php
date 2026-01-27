<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Invoice;
use Carbon\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $totalInvoices = Invoice::count();
        $paidInvoices = Invoice::where('status', 'paid')->count();
        $pendingInvoices = Invoice::whereIn('status', ['draft', 'sent'])->count();
        $overdueInvoices = Invoice::where('status', 'overdue')->count();

        $totalRevenue = Invoice::where('status', 'paid')->sum('total');
        $pendingAmount = Invoice::whereIn('status', ['draft', 'sent'])->sum('total');
        $overdueAmount = Invoice::where('status', 'overdue')->sum('total');

        $totalClients = Client::count();

        // Monthly revenue for chart (last 6 months)
        $monthlyRevenue = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $revenue = Invoice::where('status', 'paid')
                ->whereYear('paid_date', $date->year)
                ->whereMonth('paid_date', $date->month)
                ->sum('total');

            $monthlyRevenue[] = [
                'month' => $date->translatedFormat('M Y'),
                'revenue' => $revenue,
            ];
        }

        // Recent invoices
        $recentInvoices = Invoice::with('client')
            ->latest()
            ->take(5)
            ->get();

        // Invoice status distribution for pie chart
        $statusDistribution = [
            'paid' => $paidInvoices,
            'pending' => $pendingInvoices,
            'overdue' => $overdueInvoices,
        ];

        return view('dashboard', compact(
            'totalInvoices',
            'paidInvoices',
            'pendingInvoices',
            'overdueInvoices',
            'totalRevenue',
            'pendingAmount',
            'overdueAmount',
            'totalClients',
            'monthlyRevenue',
            'recentInvoices',
            'statusDistribution'
        ));
    }
}
