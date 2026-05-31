<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── sales ─────────────────────────────────────────────────────
        // Most queried: reports filter by date, ledger filters by customer+date
        Schema::table('sales', function (Blueprint $table) {
            $table->index('sale_date');                       // daily/monthly reports
            $table->index(['customer_id', 'sale_date']);     // customer ledger date range
            $table->index('due_amount');                      // sum() filters in reports
        });

        // ── purchases ─────────────────────────────────────────────────
        Schema::table('purchases', function (Blueprint $table) {
            $table->index('purchase_date');                   // report date filters
            $table->index(['supplier_id', 'purchase_date']); // supplier ledger date range
        });

        // ── customers ─────────────────────────────────────────────────
        // Searched by name in dropdowns, filtered by due_amount
        Schema::table('customers', function (Blueprint $table) {
            $table->index('name');                            // name search in POS dropdowns
            $table->index('due_amount');                      // filter positive/negative
        });

        // ── suppliers ─────────────────────────────────────────────────
        Schema::table('suppliers', function (Blueprint $table) {
            $table->index('name');                            // name search in purchase form
            $table->index('due_amount');                      // filter positive/negative
        });

        // ── items ─────────────────────────────────────────────────────
        Schema::table('items', function (Blueprint $table) {
            $table->index('name');                            // item search in POS cart
        });

        // ── sale_logs ─────────────────────────────────────────────────
        Schema::table('sale_logs', function (Blueprint $table) {
            $table->index('created_at');                      // audit log date filter
            $table->index('action');                          // filter edit/delete
        });

        // ── customer_payments ─────────────────────────────────────────
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->index('payment_date');
            $table->index(['customer_id', 'payment_date']);   // ledger date range
        });

        // ── supplier_payments ─────────────────────────────────────────
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->index('payment_date');
            $table->index(['supplier_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['sale_date']);
            $table->dropIndex(['customer_id', 'sale_date']);
            $table->dropIndex(['due_amount']);
        });
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex(['purchase_date']);
            $table->dropIndex(['supplier_id', 'purchase_date']);
        });
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['due_amount']);
        });
        Schema::table('suppliers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['due_amount']);
        });
        Schema::table('items', function (Blueprint $table) {
            $table->dropIndex(['name']);
        });
        Schema::table('sale_logs', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['action']);
        });
        Schema::table('customer_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['customer_id', 'payment_date']);
        });
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropIndex(['payment_date']);
            $table->dropIndex(['supplier_id', 'payment_date']);
        });
    }
};
