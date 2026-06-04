<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder — creates all demo/test accounts shown on the login page.
 * Safe to run multiple times (uses updateOrCreate).
 * Run on production: php artisan db:seed --class=DemoSeeder
 */
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Root ───────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'root@system.com'],
            [
                'name'     => 'Root Admin',
                'password' => Hash::make('password'),
                'role'     => 'root',
                'shop_id'  => null,
            ]
        );

        // ── 2. Reseller ───────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'resell@a.com'],
            [
                'name'     => 'নুমান (রিসেলার)',
                'password' => Hash::make('123456'),
                'role'     => 'reseller',
                'shop_id'  => null,
            ]
        );

        // ── 3. Super Admin ────────────────────────────────────────
        $superAdmin = User::updateOrCreate(
            ['email' => 'super@admin.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('password'),
                'role'     => 'super_admin',
                'shop_id'  => null,
            ]
        );

        // ── 4. Shops (owned by super admin) ───────────────────────
        $shop1 = Shop::updateOrCreate(
            ['id' => 1],
            [
                'name'          => 'প্রধান শাখা',
                'super_admin_id'=> $superAdmin->id,
                'is_active'     => true,
                'is_locked'     => false,
            ]
        );

        $shop2 = Shop::updateOrCreate(
            ['id' => 2],
            [
                'name'          => 'মিরপুর শাখা',
                'super_admin_id'=> $superAdmin->id,
                'is_active'     => true,
                'is_locked'     => false,
            ]
        );

        $shop3 = Shop::firstOrCreate(
            ['name' => 'উত্তরা শাখা'],
            [
                'super_admin_id'=> $superAdmin->id,
                'is_active'     => true,
                'is_locked'     => false,
            ]
        );

        $shop4 = Shop::firstOrCreate(
            ['name' => 'ধানমন্ডি শাখা'],
            [
                'super_admin_id'=> $superAdmin->id,
                'is_active'     => true,
                'is_locked'     => false,
            ]
        );

        // ── 5. Shop Admins ────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@inventory.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin123'),
                'role'     => 'admin',
                'shop_id'  => $shop1->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'mirpur@shop.com'],
            [
                'name'     => 'মিরপুর অ্যাডমিন',
                'password' => Hash::make('secret123'),
                'role'     => 'admin',
                'shop_id'  => $shop2->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'uttara.admin@pos.test'],
            [
                'name'     => 'উত্তরা অ্যাডমিন',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'shop_id'  => $shop3->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'dhanmondi.admin@pos.test'],
            [
                'name'     => 'ধানমন্ডি অ্যাডমিন',
                'password' => Hash::make('password'),
                'role'     => 'admin',
                'shop_id'  => $shop4->id,
            ]
        );

        // ── 6. Staff ──────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'hasan@inventory.com'],
            [
                'name'     => 'Hasan',
                'password' => Hash::make('hasan123'),
                'role'     => 'staff',
                'shop_id'  => $shop1->id,
            ]
        );

        User::updateOrCreate(
            ['email' => 'uttara.staff1@pos.test'],
            [
                'name'     => 'রহিম (স্টাফ)',
                'password' => Hash::make('password'),
                'role'     => 'staff',
                'shop_id'  => $shop3->id,
            ]
        );

        $this->command->info('✅ DemoSeeder: সব demo account তৈরি হয়েছে।');
        $this->command->table(
            ['Email', 'Password', 'Role'],
            [
                ['root@system.com',         'password',  'root'],
                ['resell@a.com',             '123456',    'reseller'],
                ['super@admin.com',          'password',  'super_admin'],
                ['admin@inventory.com',      'admin123',  'admin (প্রধান শাখা)'],
                ['mirpur@shop.com',          'secret123', 'admin (মিরপুর শাখা)'],
                ['uttara.admin@pos.test',    'password',  'admin (উত্তরা শাখা)'],
                ['dhanmondi.admin@pos.test', 'password',  'admin (ধানমন্ডি শাখা)'],
                ['hasan@inventory.com',      'hasan123',  'staff (প্রধান শাখা)'],
                ['uttara.staff1@pos.test',   'password',  'staff (উত্তরা শাখা)'],
            ]
        );
    }
}
