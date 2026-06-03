<?php

namespace App\Http\Controllers;

use App\Models\ChatMessage;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    /**
     * Main chat page. Optionally open a specific user's conversation.
     */
    public function index(Request $request)
    {
        $me    = Auth::user();
        $users = User::where('id', '!=', $me->id)->orderBy('name')->get();

        // Build user list with last message + unread count
        $userList = $users->map(function ($u) use ($me) {
            $last = ChatMessage::conversation($me->id, $u->id)
                ->latest('created_at')
                ->first();

            $unread = ChatMessage::where('sender_id', $u->id)
                ->where('receiver_id', $me->id)
                ->where('is_read', false)
                ->count();

            return [
                'user'    => $u,
                'last'    => $last,
                'unread'  => $unread,
            ];
        })->sortByDesc(fn($item) => optional($item['last'])->created_at)->values();

        // Which user is currently active (from ?with=userId)
        $activeUserId = (int) $request->get('with', 0);
        $activeUser   = $activeUserId ? $users->firstWhere('id', $activeUserId) : null;

        $messages = collect();
        if ($activeUser) {
            $messages = ChatMessage::conversation($me->id, $activeUser->id)->get();
            // Mark received messages as read
            ChatMessage::where('sender_id', $activeUser->id)
                ->where('receiver_id', $me->id)
                ->where('is_read', false)
                ->update(['is_read' => true]);
        }

        return view('chat.index', compact('userList', 'activeUser', 'messages', 'me'));
    }

    /**
     * AJAX: send a message.
     */
    public function send(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'message'     => 'required|string|max:2000',
        ]);

        $msg = ChatMessage::create([
            'sender_id'   => Auth::id(),
            'receiver_id' => $request->receiver_id,
            'message'     => trim($request->message),
            'is_read'     => false,
        ]);

        return response()->json([
            'id'         => $msg->id,
            'message'    => $msg->message,
            'sender_id'  => $msg->sender_id,
            'created_at' => $msg->created_at->format('h:i a'),
            'date'       => $msg->created_at->format('d M'),
        ]);
    }

    /**
     * AJAX: poll for new messages after a given message id.
     */
    public function poll(Request $request)
    {
        $request->validate([
            'with'    => 'required|exists:users,id',
            'last_id' => 'nullable|integer',
        ]);

        $me       = Auth::id();
        $otherId  = (int) $request->with;
        $lastId   = (int) ($request->last_id ?? 0);

        $messages = ChatMessage::conversation($me, $otherId)
            ->where('id', '>', $lastId)
            ->get()
            ->map(fn($m) => [
                'id'         => $m->id,
                'message'    => $m->message,
                'sender_id'  => $m->sender_id,
                'created_at' => $m->created_at->format('h:i a'),
                'date'       => $m->created_at->format('d M'),
            ]);

        // Mark new incoming as read
        ChatMessage::where('sender_id', $otherId)
            ->where('receiver_id', $me)
            ->where('id', '>', $lastId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        // Unread count from others (for topbar badge refresh)
        $totalUnread = ChatMessage::unreadCount($me);

        return response()->json([
            'messages'     => $messages,
            'total_unread' => $totalUnread,
        ]);
    }

    /**
     * AJAX: get total unread count (for topbar badge polling).
     */
    public function unread()
    {
        return response()->json(['count' => ChatMessage::unreadCount(Auth::id())]);
    }
}
