<?php

namespace App\Http\Controllers;

use App\Events\GroupMessageSent;
use App\Events\MessageSent;
use App\Models\ChatMessage;
use App\Models\GroupChatMessage;
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
        // Only colleagues in the same shop appear in the chat list
        $users = User::where('id', '!=', $me->id)
            ->where('shop_id', $me->shop_id)
            ->orderBy('name')->get();

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

        // Mini-chat AJAX user list request
        if ($request->has('mc')) {
            return response()->json(
                $userList->map(fn($item) => [
                    'id'       => $item['user']->id,
                    'name'     => $item['user']->name,
                    'last_msg' => $item['last'] ? mb_substr($item['last']->message, 0, 40) : null,
                    'unread'   => $item['unread'],
                ])
            );
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

        // Broadcast to receiver via WebSocket (fails silently if Reverb not running)
        try { broadcast(new MessageSent($msg)); } catch (\Throwable) {}

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

    // ──────────────────────────────────────────────────────────
    // Group Chat
    // ──────────────────────────────────────────────────────────

    public function groupIndex()
    {
        $messages = GroupChatMessage::with('sender')
            ->latest()->take(100)->get()->reverse()->values();

        return view('chat.group', compact('messages'));
    }

    public function groupSend(Request $request)
    {
        $request->validate(['message' => 'required|string|max:2000']);

        $msg = GroupChatMessage::create([
            'sender_id' => Auth::id(),
            'message'   => trim($request->message),
        ]);
        $msg->load('sender');

        try { broadcast(new GroupMessageSent($msg)); } catch (\Throwable) {}

        return response()->json([
            'id'          => $msg->id,
            'sender_id'   => $msg->sender_id,
            'sender_name' => $msg->sender->name,
            'message'     => $msg->message,
            'created_at'  => $msg->created_at->format('h:i a'),
            'date'        => $msg->created_at->format('d M'),
            'is_group'    => true,
        ]);
    }

    public function groupPoll(Request $request)
    {
        $lastId   = (int) ($request->last_id ?? 0);
        $messages = GroupChatMessage::with('sender')
            ->where('id', '>', $lastId)
            ->orderBy('id')
            ->get()
            ->map(fn($m) => [
                'id'          => $m->id,
                'sender_id'   => $m->sender_id,
                'sender_name' => $m->sender->name,
                'message'     => $m->message,
                'created_at'  => $m->created_at->format('h:i a'),
                'date'        => $m->created_at->format('d M'),
                'is_group'    => true,
            ]);

        return response()->json(['messages' => $messages]);
    }
}
