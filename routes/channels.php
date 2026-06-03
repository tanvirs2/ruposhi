<?php

use Illuminate\Support\Facades\Broadcast;

/*
 * Private channel per user — each user listens on their own channel.
 * Only the authenticated user whose ID matches can subscribe.
 */
Broadcast::channel('chat.user.{userId}', function ($user, $userId) {
    return (int) $user->id === (int) $userId;
});
