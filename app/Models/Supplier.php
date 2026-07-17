<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'proprietor', 'phone', 'email', 'address', 'due_amount', 'opening_balance', 'created_by'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }

    /**
     * Canonical due formula — the ONE place it lives.
     *
     *   due = opening_balance + all purchases − all paid − all deposits − all payments
     *
     * opening_balance is the paper-ledger dena carried in from before go-live;
     * it predates every transaction, so it's always in the sum. No max(0,...) —
     * a negative result is a real credit/advance balance and must survive.
     *
     * due_amount is a *derived* cache. The ledger recomputes it on view, but
     * that leaves it stale between a form/CSV edit and the next ledger open.
     * Call this right after changing opening_balance to keep the cache honest.
     */
    public function recalcDue(): float
    {
        $purchaseIds = Purchase::where('supplier_id', $this->id)->pluck('id');
        $purchases   = Purchase::where('supplier_id', $this->id)->sum('total_amount');
        $paid        = Purchase::where('supplier_id', $this->id)->sum('paid_amount');
        $deposits    = PurchaseDeposit::whereIn('purchase_id', $purchaseIds)->sum('amount');
        $payments    = SupplierPayment::where('supplier_id', $this->id)->sum('amount');

        $due = (float) $this->opening_balance + $purchases - $paid - $deposits - $payments;

        if (abs($this->due_amount - $due) > 0.01) {
            $this->update(['due_amount' => $due]);
        }

        return $due;
    }
}
