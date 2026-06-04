<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class RootUserSeeder extends Seeder
{
    /**
     * Creates the root (software owner) user.
     * Safe to run multiple times — will not duplicate.
     */
    public function run(): void
    {
        $exists = DB::table('users')->where('email', 'root@system.com')->exists();

        if (! $exists) {
            DB::table('users')->insert([
                'name'       => 'Root Admin',
                'email'      => 'root@system.com',
                'password'   => Hash::make('root1234'),
                'role'       => 'root',
                'shop_id'    => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $this->command->info('Root user created: root@system.com / root1234');
            $this->command->warn('⚠️  Change the root password immediately after first login!');
        } else {
            $this->command->info('Root user already exists — skipped.');
        }
    }
}
