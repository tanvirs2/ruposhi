<?php

namespace App\Models;

use App\Traits\HasShopScope;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    use HasShopScope;

    protected $fillable = ['sender_id', 'receiver_id', 'message', 'is_read'];

    protected $casts = ['is_read' => 'boolean'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * Get all messages between two users.
     */
    public static function conversation(int $userA, int $userB)
    {
        return static::where(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userA)->where('receiver_id', $userB);
        })->orWhere(function ($q) use ($userA, $userB) {
            $q->where('sender_id', $userB)->where('receiver_id', $userA);
        })->orderBy('created_at');
    }

    /**
     * Count unread messages for a user.
     */
    public static function unreadCount(int $userId): int
    {
        return static::where('receiver_id', $userId)->where('is_read', false)->count();
    }
}
