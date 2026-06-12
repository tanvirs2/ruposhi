<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasShopScope;

    protected $fillable = ['name', 'proprietor', 'phone', 'email', 'address', 'due_amount', 'created_by'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }

    public function payments()
    {
        return $this->hasMany(SupplierPayment::class);
    }
}
