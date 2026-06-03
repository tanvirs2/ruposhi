<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Shop extends Model
{
    protected $fillable = ['name', 'address', 'phone', 'logo', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /* ── Relations ─────────────────────────────────────────── */

    public function users()       { return $this->hasMany(User::class); }
    public function items()       { return $this->hasMany(Item::class); }
    public function sales()       { return $this->hasMany(Sale::class); }
    public function purchases()   { return $this->hasMany(Purchase::class); }
    public function customers()   { return $this->hasMany(Customer::class); }
    public function suppliers()   { return $this->hasMany(Supplier::class); }
    public function categories()  { return $this->hasMany(Category::class); }
    public function expenses()    { return $this->hasMany(ExtraExpense::class); }
    public function employees()   { return $this->hasMany(Employee::class); }
    public function stock()       { return $this->hasMany(Stock::class); }
}
