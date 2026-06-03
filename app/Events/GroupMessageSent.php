<?php

namespace App\Events;

use App\Models\GroupChatMessage;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GroupMessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public GroupChatMessage $message) {}

    /**
     * Broadcast on every user's private channel so all receive it.
     */
    public function broadcastOn(): array
    {
        return User::pluck('id')
            ->map(fn($id) => new PrivateChannel('chat.user.' . $id))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'message.sent';   // same event name — frontend handles is_group flag
    }

    public function broadcastWith(): array
    {
        return [
            'id'          => $this->message->id,
            'sender_id'   => $this->message->sender_id,
            'sender_name' => $this->message->sender->name,
            'receiver_id' => null,
            'message'     => $this->message->message,
            'created_at'  => $this->message->created_at->format('h:i a'),
            'date'        => $this->message->created_at->format('d M'),
            'is_group'    => true,
        ];
    }
}
