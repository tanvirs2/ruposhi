<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'proprietor', 'phone', 'address', 'area_id', 'due_amount', 'opening_balance', 'credit_limit', 'created_by'];

    public function area()
    {
        return $this->belongsTo(CustomerArea::class, 'area_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function payments()
    {
        return $this->hasMany(CustomerPayment::class);
    }

    /**
     * Canonical due formula — the ONE place it lives.
     *
     *   due = opening_balance + all sales − all paid-on-sale − all payments
     *
     * opening_balance is the paper-ledger due carried in from before go-live;
     * it predates every transaction, so it's always in the sum. No max(0,...) —
     * a negative result is a real credit/advance balance and must survive.
     *
     * due_amount is a *derived* cache of this. The ledger recomputes it on view
     * (auto-fix), but that leaves it stale between a form/CSV edit and the next
     * ledger open — the list would show the old figure. Call this right after
     * changing opening_balance (or any transaction) to keep the cache honest.
     */
    public function recalcDue(): float
    {
        $sales    = Sale::where('customer_id', $this->id)->sum('total_amount');
        $paid     = Sale::where('customer_id', $this->id)->sum('paid_amount');
        $payments = CustomerPayment::where('customer_id', $this->id)->sum('amount');

        $due = (float) $this->opening_balance + $sales - $paid - $payments;

        if (abs($this->due_amount - $due) > 0.01) {
            $this->update(['due_amount' => $due]);
        }

        return $due;
    }
}
