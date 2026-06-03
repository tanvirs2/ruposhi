<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GroupChatMessage extends Model
{
    protected $fillable = ['sender_id', 'message'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
