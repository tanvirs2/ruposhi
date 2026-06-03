<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'recipient', 'recipient_name', 'message',
        'status', 'gateway_response', 'sent_by',
    ];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }
}
