<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class ExtraExpense extends Model
{
    use HasShopScope;

    protected $fillable = ['type', 'title', 'amount', 'category', 'expense_date', 'notes', 'user_id'];

    protected $casts = ['expense_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
