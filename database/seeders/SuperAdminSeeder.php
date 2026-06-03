<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Super Admin (no shop)
        User::updateOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
                'shop_id'  => null,
            ]
        );

        // 2. Create a default shop
        $shop = Shop::firstOrCreate(
            ['name' => 'প্রধান শাখা'],
            ['address' => 'ঢাকা', 'phone' => '01700000000', 'is_active' => true]
        );

        // 3. Assign all existing users (admin/staff) to this default shop
        User::whereNull('shop_id')
            ->where('role', '!=', 'super_admin')
            ->update(['shop_id' => $shop->id]);

        // 4. Assign all existing data rows to this default shop
        $tables = [
            'customers', 'customer_areas', 'customer_payments',
            'categories', 'items', 'stock',
            'suppliers', 'supplier_payments',
            'sales', 'purchases',
            'employees', 'extra_expenses', 'store_config',
            'sale_logs', 'sms_logs', 'chat_messages',
            'group_chat_messages', 'extra_cost_categories',
        ];

        foreach ($tables as $table) {
            \DB::table($table)->whereNull('shop_id')->update(['shop_id' => $shop->id]);
        }

        $this->command->info('✅ Super Admin তৈরি হয়েছে: super@admin.com / password');
        $this->command->info("✅ Default shop: {$shop->name} (ID: {$shop->id})");
    }
}
