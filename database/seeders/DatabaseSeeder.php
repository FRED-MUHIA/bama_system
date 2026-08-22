<?php

namespace Database\Seeders;

use App\Models\CompanySetting;
use App\Models\PaymentMethod;
use App\Models\Signatory;
use App\Models\TemplateCategory;
use App\Models\TermsCondition;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@bama.co.ke'], [
            'name' => 'BAMA Admin',
            'username' => 'ZachariaM',
            'role' => 'admin',
            'is_active' => true,
            'password' => Hash::make('Zach@123'),
        ]);

        CompanySetting::firstOrCreate(['id' => 1], [
            'company_name' => 'BAMA',
            'phone' => '+254 700 000 000',
            'email' => 'admin@bama.co.ke',
            'address' => 'Nairobi, Kenya',
            'website' => 'https://bama.co.ke',
            'tax_name' => 'VAT',
            'tax_rate' => 16,
            'default_terms' => 'Payment is due by the stated due date. Services remain subject to BAMA standard terms.',
        ]);

        PaymentMethod::firstOrCreate(['name' => 'M-Pesa Paybill'], [
            'type' => 'mpesa',
            'details' => 'Paybill: 000000, Account: Invoice Number',
            'is_active' => true,
        ]);

        PaymentMethod::firstOrCreate(['name' => 'Bank Transfer'], [
            'type' => 'bank',
            'details' => 'Bank: Your Bank, Account Name: BAMA, Account No: 000000000',
            'is_active' => true,
        ]);

        PaymentMethod::firstOrCreate(['name' => 'Cash'], [
            'type' => 'cash',
            'details' => 'Cash payments accepted with official receipt.',
            'is_active' => true,
        ]);

        TermsCondition::firstOrCreate(['title' => 'Default Terms'], [
            'content' => 'Payment is due by the stated due date. Kindly quote the document number when making payment.',
            'is_default' => true,
        ]);

        $categories = [
            ['name' => 'Financial', 'description' => 'Invoices, payments, refunds and financial correspondence', 'icon' => 'bi-cash-coin', 'sort_order' => 1, 'is_system' => true],
            ['name' => 'Projects', 'description' => 'Proposals, handovers, completion, progress reports', 'icon' => 'bi-kanban', 'sort_order' => 2, 'is_system' => true],
            ['name' => 'Legal & Contracts', 'description' => 'Variations, extensions, notices and contracts', 'icon' => 'bi-file-earmark-text', 'sort_order' => 3, 'is_system' => true],
            ['name' => 'Warranty & Support', 'description' => 'Warranty confirmations, claims, maintenance', 'icon' => 'bi-shield-check', 'sort_order' => 4, 'is_system' => true],
            ['name' => 'General Business', 'description' => 'General correspondence, introductions, recommendations', 'icon' => 'bi-envelope-paper', 'sort_order' => 5, 'is_system' => true],
            ['name' => 'Procurement', 'description' => 'RFQs, purchase orders, supplier communications', 'icon' => 'bi-truck', 'sort_order' => 6, 'is_system' => true],
        ];

        foreach ($categories as $cat) {
            TemplateCategory::firstOrCreate(
                ['slug' => Str::slug($cat['name'])],
                $cat + ['business_id' => null]
            );
        }

        Signatory::firstOrCreate(['name' => 'Zacharia Mugai'], [
            'title' => 'Managing Director',
            'is_default' => true,
            'is_active' => true,
        ]);
    }
}
