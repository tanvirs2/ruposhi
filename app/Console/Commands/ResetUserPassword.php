<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetUserPassword extends Command
{
    protected $signature = 'pos:reset-password
                            {email? : যে ব্যবহারকারীর পাসওয়ার্ড রিসেট করবেন}
                            {password? : নতুন পাসওয়ার্ড (না দিলে random তৈরি হবে)}
                            {--list : সকল ব্যবহারকারী দেখুন}
                            {--role= : নির্দিষ্ট role-এর user দেখুন (root/reseller/super_admin/admin/staff)}';

    protected $description = 'POS ব্যবহারকারীর পাসওয়ার্ড রিসেট করুন (support/admin use)';

    public function handle(): int
    {
        // ── List mode ─────────────────────────────────────────────
        if ($this->option('list')) {
            return $this->listUsers();
        }

        // ── Reset mode ────────────────────────────────────────────
        $email = $this->argument('email');

        if (!$email) {
            $this->listUsers();
            $email = $this->ask('কোন email-এর পাসওয়ার্ড রিসেট করবেন?');
        }

        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌  '{$email}' — এই email-এর কোনো ব্যবহারকারী পাওয়া যায়নি।");
            return 1;
        }

        // Show user info
        $this->info("👤  পাওয়া গেছে:");
        $this->table(
            ['নাম', 'Email', 'Role', 'Shop ID'],
            [[$user->name, $user->email, $user->role, $user->shop_id ?? 'N/A']]
        );

        $password = $this->argument('password');

        // Only ask to confirm when running interactively AND no password was pre-supplied
        if (!$password && $this->input->isInteractive()) {
            if (!$this->confirm("এই ব্যবহারকারীর পাসওয়ার্ড রিসেট করবেন?", true)) {
                $this->line('বাতিল করা হয়েছে।');
                return 0;
            }
        }

        if (!$password) {
            // Generate a readable random password
            $password = Str::random(8);
            $generated = true;
        } else {
            $generated = false;
        }

        if (strlen($password) < 6) {
            $this->error('❌  পাসওয়ার্ড কমপক্ষে ৬ অক্ষর হতে হবে।');
            return 1;
        }

        $user->update(['password' => Hash::make($password)]);

        $this->newLine();
        $this->line('┌─────────────────────────────────────────┐');
        $this->line('│  ✅  পাসওয়ার্ড সফলভাবে পরিবর্তিত হয়েছে   │');
        $this->line('├─────────────────────────────────────────┤');
        $this->line("│  নাম    : {$user->name}");
        $this->line("│  Email  : {$user->email}");
        $this->line("│  Role   : {$user->role}");
        $this->line("│  পাসওয়ার্ড: {$password}" . ($generated ? '  (auto-generated)' : ''));
        $this->line('└─────────────────────────────────────────┘');

        if ($generated) {
            $this->warn("⚠️  উপরের পাসওয়ার্ডটি নোট করুন — পরে আর দেখা যাবে না।");
        }

        return 0;
    }

    private function listUsers(): int
    {
        $query = User::query()->orderByRaw("FIELD(role,'root','reseller','super_admin','admin','staff')");

        if ($role = $this->option('role')) {
            $query->where('role', $role);
        }

        $users = $query->get(['id', 'name', 'email', 'role', 'shop_id']);

        if ($users->isEmpty()) {
            $this->warn('কোনো ব্যবহারকারী পাওয়া যায়নি।');
            return 0;
        }

        $this->info("📋  মোট ব্যবহারকারী: {$users->count()}");
        $this->newLine();

        $rows = $users->map(fn($u) => [
            $u->id,
            $u->name,
            $u->email,
            $u->role,
            $u->shop_id ?? '—',
        ])->toArray();

        $this->table(['ID', 'নাম', 'Email', 'Role', 'Shop ID'], $rows);

        return 0;
    }
}
