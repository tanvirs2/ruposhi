<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Due carried in from the client's paper ledger, from before they
        // started using the software. due_amount can't hold it on its own —
        // the ledger auto-fix recomputes due_amount from transactions on every
        // view, so a directly-set figure gets wiped. This column feeds INTO
        // that formula instead, so it survives.
        // Negative = advance/credit, same as due_amount (never max(0,...)).
        Schema::table('customers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('due_amount');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->decimal('opening_balance', 15, 2)->default(0)->after('due_amount');
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });

        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropColumn('opening_balance');
        });
    }
};
