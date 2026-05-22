<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Category;
use App\Models\Item;
use App\Models\Stock;
use App\Models\StoreConfig;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Users
        User::updateOrCreate(['email' => 'admin@inventory.com'], [
            'name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin',
        ]);
        User::updateOrCreate(['email' => 'hasan@inventory.com'], [
            'name' => 'Hasan', 'password' => Hash::make('password'), 'role' => 'staff',
        ]);

        // Categories — rice varieties
        $cats = [
            'মিনিকেট চাল',
            'নাজিরশাইল চাল',
            'বিরি ধান চাল',
            'আতব চাল',
            'পোলাও চাল',
            'অন্যান্য চাল',
        ];
        $catIds = [];
        foreach ($cats as $cat) {
            $catIds[] = Category::updateOrCreate(['name' => $cat])->id;
        }

        // Suppliers — rice mills / wholesalers
        foreach ([
            ['name' => 'ময়মনসিংহ রাইস মিল',    'phone' => '01711000001', 'address' => 'ময়মনসিংহ'],
            ['name' => 'কুমিল্লা অটো রাইস মিল', 'phone' => '01811000002', 'address' => 'কুমিল্লা'],
            ['name' => 'দিনাজপুর চাল সাপ্লাই',  'phone' => '01911000003', 'address' => 'দিনাজপুর'],
            ['name' => 'নওগাঁ এগ্রো ট্রেডার্স',  'phone' => '01611000004', 'address' => 'নওগাঁ'],
        ] as $s) {
            Supplier::updateOrCreate(['name' => $s['name']], $s);
        }

        // Customers — rice dealers / shops
        foreach ([
            ['name' => 'করিম স্টোর',        'phone' => '01700000001', 'address' => 'মিরপুর, ঢাকা'],
            ['name' => 'রহিম ট্রেডার্স',     'phone' => '01800000002', 'address' => 'যাত্রাবাড়ী, ঢাকা'],
            ['name' => 'আল-আমিন রাইস হাউস', 'phone' => '01900000003', 'address' => 'নারায়ণগঞ্জ'],
            ['name' => 'হাজী ব্রাদার্স',     'phone' => '01700000004', 'address' => 'চট্টগ্রাম'],
            ['name' => 'মেসার্স সালাম',      'phone' => '01800000005', 'address' => 'কুমিল্লা'],
            ['name' => 'নিউ ঢাকা রাইস',      'phone' => '01600000006', 'address' => 'গাজীপুর'],
        ] as $c) {
            Customer::updateOrCreate(['phone' => $c['phone']], $c);
        }

        // Items — rice in 50kg & 25kg sacks
        // cat index: 0=মিনিকেট, 1=নাজিরশাইল, 2=বিরি ধান, 3=আতব, 4=পোলাও, 5=অন্যান্য
        // unit: বস্তা (sack)
        $items = [
            // মিনিকেট
            ['name' => 'মিনিকেট চাল ৫০ কেজি বস্তা', 'cat' => 0, 'buy' => 2800, 'sell' => 2950, 'unit' => 'বস্তা', 'qty' => 80,  'min' => 10],
            ['name' => 'মিনিকেট চাল ২৫ কেজি বস্তা', 'cat' => 0, 'buy' => 1400, 'sell' => 1480, 'unit' => 'বস্তা', 'qty' => 50,  'min' =>  5],

            // নাজিরশাইল
            ['name' => 'নাজিরশাইল চাল ৫০ কেজি বস্তা', 'cat' => 1, 'buy' => 3200, 'sell' => 3380, 'unit' => 'বস্তা', 'qty' => 60,  'min' => 10],
            ['name' => 'নাজিরশাইল চাল ২৫ কেজি বস্তা', 'cat' => 1, 'buy' => 1600, 'sell' => 1700, 'unit' => 'বস্তা', 'qty' => 40,  'min' =>  5],

            // বিরি ধান (BRRI-28 / BRRI-29)
            ['name' => 'বিরি-২৮ চাল ৫০ কেজি বস্তা',  'cat' => 2, 'buy' => 2400, 'sell' => 2550, 'unit' => 'বস্তা', 'qty' => 100, 'min' => 15],
            ['name' => 'বিরি-২৯ চাল ৫০ কেজি বস্তা',  'cat' => 2, 'buy' => 2450, 'sell' => 2600, 'unit' => 'বস্তা', 'qty' => 90,  'min' => 15],
            ['name' => 'বিরি-২৮ চাল ২৫ কেজি বস্তা',  'cat' => 2, 'buy' => 1200, 'sell' => 1280, 'unit' => 'বস্তা', 'qty' => 60,  'min' =>  8],

            // আতব
            ['name' => 'আতব চাল ৫০ কেজি বস্তা',      'cat' => 3, 'buy' => 2200, 'sell' => 2350, 'unit' => 'বস্তা', 'qty' => 70,  'min' => 10],
            ['name' => 'আতব চাল ২৫ কেজি বস্তা',      'cat' => 3, 'buy' => 1100, 'sell' => 1180, 'unit' => 'বস্তা', 'qty' => 3,   'min' =>  5],

            // পোলাও
            ['name' => 'পোলাও চাল ৫০ কেজি বস্তা',    'cat' => 4, 'buy' => 4500, 'sell' => 4750, 'unit' => 'বস্তা', 'qty' => 30,  'min' =>  5],
            ['name' => 'পোলাও চাল ২৫ কেজি বস্তা',    'cat' => 4, 'buy' => 2250, 'sell' => 2400, 'unit' => 'বস্তা', 'qty' => 20,  'min' =>  3],

            // কাটারিভোগ (premium)
            ['name' => 'কাটারিভোগ চাল ৫০ কেজি বস্তা','cat' => 5, 'buy' => 5200, 'sell' => 5500, 'unit' => 'বস্তা', 'qty' => 15,  'min' =>  3],
            ['name' => 'কাটারিভোগ চাল ২৫ কেজি বস্তা','cat' => 5, 'buy' => 2600, 'sell' => 2750, 'unit' => 'বস্তা', 'qty' => 4,   'min' =>  3],

            // স্বর্ণা
            ['name' => 'স্বর্ণা চাল ৫০ কেজি বস্তা',  'cat' => 5, 'buy' => 2300, 'sell' => 2450, 'unit' => 'বস্তা', 'qty' => 55,  'min' => 10],
        ];

        // Clear old items & stock first to avoid conflicts from previous generic seed
        \App\Models\Stock::query()->delete();
        \App\Models\Item::query()->delete();
        \App\Models\Category::query()->delete();

        // Re-create categories after truncate
        $catIds = [];
        foreach ($cats as $cat) {
            $catIds[] = Category::create(['name' => $cat])->id;
        }

        foreach ($items as $row) {
            $item = Item::create([
                'name'           => $row['name'],
                'category_id'    => $catIds[$row['cat']],
                'purchase_price' => $row['buy'],
                'sale_price'     => $row['sell'],
                'unit'           => $row['unit'],
            ]);
            Stock::create([
                'item_id'      => $item->id,
                'quantity'     => $row['qty'],
                'min_quantity' => $row['min'],
            ]);
        }

        // Store config
        StoreConfig::set('store_name',    'আমার চালের দোকান');
        StoreConfig::set('store_owner',   'মোঃ মালিকের নাম');
        StoreConfig::set('store_tagline', 'পাইকারী চাউল বিক্রেতা ও কমিশন এজেন্ট');
        StoreConfig::set('store_phone',   '01700000000');
        StoreConfig::set('store_phone2',  '');
        StoreConfig::set('store_address', 'ঢাকা, বাংলাদেশ');
        StoreConfig::set('currency',      '৳');
    }
}
