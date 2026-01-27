<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    public function run(): void
    {
        $clients = Client::all();
        $services = [
            ['description' => 'Веб дизајн', 'price' => 15000],
            ['description' => 'Развој на веб апликација', 'price' => 45000],
            ['description' => 'SEO оптимизација', 'price' => 8000],
            ['description' => 'Одржување на веб страна (месечно)', 'price' => 5000],
            ['description' => 'Графички дизајн - Лого', 'price' => 12000],
            ['description' => 'E-commerce интеграција', 'price' => 25000],
            ['description' => 'Мобилна апликација', 'price' => 80000],
            ['description' => 'Консултации (час)', 'price' => 2500],
            ['description' => 'Хостинг (годишно)', 'price' => 6000],
            ['description' => 'SSL сертификат', 'price' => 3000],
        ];

        $statuses = ['draft', 'sent', 'paid', 'overdue', 'paid', 'paid', 'sent', 'paid'];

        $invoiceNumber = 1;

        // Create invoices for the last 6 months
        for ($month = 5; $month >= 0; $month--) {
            $invoicesThisMonth = rand(3, 6);

            for ($i = 0; $i < $invoicesThisMonth; $i++) {
                $client = $clients->random();
                $issueDate = Carbon::now()->subMonths($month)->subDays(rand(0, 28));
                $dueDate = $issueDate->copy()->addDays(30);
                $status = $statuses[array_rand($statuses)];

                // Old invoices should mostly be paid
                if ($month > 2) {
                    $status = rand(1, 10) > 2 ? 'paid' : 'overdue';
                }

                $invoice = Invoice::create([
                    'invoice_number' => 'INV-' . $issueDate->year . '-' . str_pad($invoiceNumber++, 4, '0', STR_PAD_LEFT),
                    'client_id' => $client->id,
                    'issue_date' => $issueDate,
                    'due_date' => $dueDate,
                    'status' => $status,
                    'tax_rate' => 18,
                    'notes' => 'Благодариме за соработката!',
                    'paid_date' => $status === 'paid' ? $issueDate->copy()->addDays(rand(5, 25)) : null,
                ]);

                // Add 1-4 items per invoice
                $itemCount = rand(1, 4);
                $selectedServices = array_rand($services, $itemCount);
                if (!is_array($selectedServices)) {
                    $selectedServices = [$selectedServices];
                }

                foreach ($selectedServices as $serviceIndex) {
                    $service = $services[$serviceIndex];
                    $quantity = rand(1, 3);

                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => $service['description'],
                        'quantity' => $quantity,
                        'unit_price' => $service['price'],
                        'total' => $quantity * $service['price'],
                    ]);
                }

                $invoice->calculateTotals();
            }
        }
    }
}
