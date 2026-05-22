<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ExtraExpense extends Model
{
    protected $fillable = ['type', 'title', 'amount', 'category', 'expense_date', 'notes', 'user_id'];

    protected $casts = ['expense_date' => 'date'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
