<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $clients = [
            [
                'name' => 'Марко Петровски',
                'email' => 'marko@example.com',
                'phone' => '+389 70 123 456',
                'company' => 'Петровски ДООЕЛ',
                'address' => 'ул. Македонија 15',
                'city' => 'Скопје',
                'country' => 'Македонија',
                'tax_number' => 'MK4030012345678',
            ],
            [
                'name' => 'Ана Стојановска',
                'email' => 'ana@techsolutions.mk',
                'phone' => '+389 71 234 567',
                'company' => 'Tech Solutions DOO',
                'address' => 'бул. Партизански Одреди 88',
                'city' => 'Скопје',
                'country' => 'Македонија',
                'tax_number' => 'MK4030023456789',
            ],
            [
                'name' => 'Дејан Николовски',
                'email' => 'dejan@webagency.mk',
                'phone' => '+389 72 345 678',
                'company' => 'Web Agency',
                'address' => 'ул. 11 Октомври 25',
                'city' => 'Битола',
                'country' => 'Македонија',
                'tax_number' => 'MK4030034567890',
            ],
            [
                'name' => 'Елена Димитрова',
                'email' => 'elena@designstudio.mk',
                'phone' => '+389 75 456 789',
                'company' => 'Design Studio',
                'address' => 'ул. Кеј 13 Ноември 5',
                'city' => 'Охрид',
                'country' => 'Македонија',
                'tax_number' => 'MK4030045678901',
            ],
            [
                'name' => 'Стефан Јованов',
                'email' => 'stefan@consulting.mk',
                'phone' => '+389 78 567 890',
                'company' => 'Business Consulting',
                'address' => 'ул. Васил Главинов 12',
                'city' => 'Прилеп',
                'country' => 'Македонија',
                'tax_number' => 'MK4030056789012',
            ],
        ];

        foreach ($clients as $client) {
            Client::create($client);
        }
    }
}
