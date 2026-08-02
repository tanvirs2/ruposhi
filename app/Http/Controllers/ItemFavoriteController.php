<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\ItemFavorite;

class ItemFavoriteController extends Controller
{
    // ── Toggle favorite status for the logged-in user ─────────
    public function toggle(Item $item)
    {
        $existing = ItemFavorite::where('user_id', auth()->id())->where('item_id', $item->id)->first();

        if ($existing) {
            $existing->delete();
            return response()->json(['favorited' => false]);
        }

        ItemFavorite::create(['user_id' => auth()->id(), 'item_id' => $item->id]);
        return response()->json(['favorited' => true]);
    }
}
