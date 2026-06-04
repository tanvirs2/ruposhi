<?php

namespace Database\Seeders;

use App\Models\Shop;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data: 5 shops, each with 5 users (1 admin + 4 staff).
 *
 * IMPORTANT: Bengali names live in this UTF-8 file on purpose.
 * Never create Bengali records via inline `tinker --execute` on the
 * Windows console — the console code page corrupts Bengali to "?".
 *
 * Run:  php artisan db:seed --class=DemoShopsSeeder
 * Idempotent: shops match on name, users match on email.
 */
class DemoShopsSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $shops = [
            [
                'name'    => 'উত্তরা শাখা',
                'slug'    => 'uttara',
                'address' => 'উত্তরা, ঢাকা',
                'phone'   => '01700000001',
                'admin'   => 'রহিম উদ্দিন',
                'staff'   => ['সুমন আহমেদ', 'জাহিদ হাসান', 'নাসির খান', 'ফারুক মিয়া'],
            ],
            [
                'name'    => 'ধানমন্ডি শাখা',
                'slug'    => 'dhanmondi',
                'address' => 'ধানমন্ডি, ঢাকা',
                'phone'   => '01700000002',
                'admin'   => 'কামাল হোসেন',
                'staff'   => ['বাবুল আক্তার', 'রাসেল মাহমুদ', 'তানভীর আলম', 'ইমরান হক'],
            ],
            [
                'name'    => 'গুলশান শাখা',
                'slug'    => 'gulshan',
                'address' => 'গুলশান, ঢাকা',
                'phone'   => '01700000003',
                'admin'   => 'জসিম উদ্দিন',
                'staff'   => ['সোহেল রানা', 'মামুন রশিদ', 'আরিফ চৌধুরী', 'শাকিল আহমেদ'],
            ],
            [
                'name'    => 'মতিঝিল শাখা',
                'slug'    => 'motijheel',
                'address' => 'মতিঝিল, ঢাকা',
                'phone'   => '01700000004',
                'admin'   => 'আনোয়ার হোসেন',
                'staff'   => ['রতন মিয়া', 'বিল্লাল হোসেন', 'জাকির হোসেন', 'মিজানুর রহমান'],
            ],
            [
                'name'    => 'চট্টগ্রাম শাখা',
                'slug'    => 'chittagong',
                'address' => 'আগ্রাবাদ, চট্টগ্রাম',
                'phone'   => '01700000005',
                'admin'   => 'নুরুল ইসলাম',
                'staff'   => ['হারুন রশিদ', 'সেলিম উল্লাহ', 'কবির আহমেদ', 'দেলোয়ার হোসেন'],
            ],
        ];

        foreach ($shops as $info) {
            $shop = Shop::firstOrCreate(
                ['name' => $info['name']],
                [
                    'address'   => $info['address'],
                    'phone'     => $info['phone'],
                    'is_active' => true,
                ]
            );

            // 1 admin
            User::updateOrCreate(
                ['email' => $info['slug'].'.admin@pos.test'],
                [
                    'name'     => $info['admin'],
                    'password' => $password,
                    'role'     => 'admin',
                    'shop_id'  => $shop->id,
                ]
            );

            // 4 staff
            foreach ($info['staff'] as $i => $staffName) {
                User::updateOrCreate(
                    ['email' => $info['slug'].'.staff'.($i + 1).'@pos.test'],
                    [
                        'name'     => $staffName,
                        'password' => $password,
                        'role'     => 'staff',
                        'shop_id'  => $shop->id,
                    ]
                );
            }

            $this->command->info("Shop '{$info['name']}' + 5 users ready.");
        }
    }
}
