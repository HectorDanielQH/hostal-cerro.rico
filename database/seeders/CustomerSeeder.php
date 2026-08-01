<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'user_id' => null,
                'full_name' => 'Juan Perez Mamani',
                'document_type' => 'ci',
                'document_number' => '1234567',
                'nationality' => 'Bolivia',
                'phone' => '70000001',
                'whatsapp' => null,
                'email' => 'juan@example.com',
                'city' => 'Potosi',
                'country' => 'Bolivia',
                'is_foreign' => false,
                'is_company' => false,
                'is_active' => true,
            ],
            [
                'user_id' => null,
                'full_name' => 'Maria Fernandez',
                'document_type' => 'passport',
                'document_number' => 'P123456',
                'nationality' => 'Argentina',
                'phone' => null,
                'whatsapp' => '70000002',
                'email' => 'maria@example.com',
                'city' => 'Buenos Aires',
                'country' => 'Argentina',
                'is_foreign' => true,
                'is_company' => false,
                'is_active' => true,
            ],
            [
                'user_id' => null,
                'full_name' => 'Empresa Minera Andina SRL',
                'document_type' => 'nit',
                'document_number' => '987654321',
                'nationality' => 'Bolivia',
                'phone' => null,
                'whatsapp' => null,
                'email' => 'contacto@andina.test',
                'city' => 'Potosi',
                'country' => 'Bolivia',
                'is_foreign' => false,
                'is_company' => true,
                'company_name' => 'Empresa Minera Andina SRL',
                'tax_number' => '987654321',
                'is_active' => true,
            ],
        ];

        foreach ($customers as $customer) {
            Customer::updateOrCreate(
                [
                    'full_name' => $customer['full_name'],
                    'document_number' => $customer['document_number'] ?? null,
                ],
                $customer
            );
        }
    }
}
