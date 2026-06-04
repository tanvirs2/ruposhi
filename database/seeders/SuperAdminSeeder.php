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
        // 1. Create Super Admin (shop_id stays null — shops point back via super_admin_id)
        $superAdmin = User::updateOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
                'shop_id'  => null,
            ]
        );

        // 2. Create a default shop owned by this super_admin
        $shop = Shop::firstOrCreate(
            ['name' => 'প্রধান শাখা'],
            ['address' => 'ঢাকা', 'phone' => '01700000000', 'is_active' => true, 'super_admin_id' => $superAdmin->id]
        );

        // Backfill super_admin_id if shop already existed without it
        if (!$shop->super_admin_id) {
            $shop->update(['super_admin_id' => $superAdmin->id]);
        }

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

        // 5. Backfill line-item / extra-cost child rows from their parent.
        //    The line-item migration ran during `migrate`, while parents still
        //    had shop_id = NULL — so existing children stayed NULL. Now that the
        //    parents are assigned, copy shop_id down. Idempotent (only NULLs).
        $childMap = [
            'sale_items'           => ['parent' => 'sales',     'fk' => 'sale_id'],
            'purchase_items'       => ['parent' => 'purchases', 'fk' => 'purchase_id'],
            'sale_extra_costs'     => ['parent' => 'sales',     'fk' => 'sale_id'],
            'purchase_extra_costs' => ['parent' => 'purchases', 'fk' => 'purchase_id'],
        ];
        foreach ($childMap as $child => $info) {
            if (\Schema::hasTable($child) && \Schema::hasColumn($child, 'shop_id')) {
                \DB::statement("
                    UPDATE {$child} c
                    JOIN {$info['parent']} p ON c.{$info['fk']} = p.id
                    SET c.shop_id = p.shop_id
                    WHERE c.shop_id IS NULL
                ");
            }
        }

        $this->command->info('✅ Super Admin তৈরি হয়েছে: super@admin.com / password');
        $this->command->info("✅ Default shop: {$shop->name} (ID: {$shop->id})");
    }
}
