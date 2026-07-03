<?php

namespace App\Http\Controllers;

use App\Models\ReceiptProfile;
use App\Models\User;
use Illuminate\Http\Request;

class ReceiptProfileController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'store_name'    => 'required|string|max:255',
            'store_owner'   => 'nullable|string|max:255',
            'store_tagline' => 'nullable|string|max:255',
            'store_phone'   => 'nullable|string|max:20',
            'store_phone2'  => 'nullable|string|max:20',
            'store_address' => 'nullable|string',
            'currency'      => 'nullable|string|max:10',
            'user_ids'      => 'nullable|array',
            'user_ids.*'    => 'integer',
        ]);

        $profile = ReceiptProfile::create(collect($data)->except('user_ids')->toArray());
        $this->syncUsers($profile, $data['user_ids'] ?? []);

        return redirect()->route('store-config.index')->with('success', 'রিসিট প্রোফাইল তৈরি হয়েছে।');
    }

    public function update(Request $request, ReceiptProfile $receiptProfile)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'store_name'    => 'required|string|max:255',
            'store_owner'   => 'nullable|string|max:255',
            'store_tagline' => 'nullable|string|max:255',
            'store_phone'   => 'nullable|string|max:20',
            'store_phone2'  => 'nullable|string|max:20',
            'store_address' => 'nullable|string',
            'currency'      => 'nullable|string|max:10',
            'user_ids'      => 'nullable|array',
            'user_ids.*'    => 'integer',
        ]);

        $receiptProfile->update(collect($data)->except('user_ids')->toArray());
        $this->syncUsers($receiptProfile, $data['user_ids'] ?? []);

        return redirect()->route('store-config.index')->with('success', 'রিসিট প্রোফাইল আপডেট হয়েছে।');
    }

    public function destroy(ReceiptProfile $receiptProfile)
    {
        $receiptProfile->delete(); // users.receipt_profile_id nulled via FK nullOnDelete
        return redirect()->route('store-config.index')->with('success', 'রিসিট প্রোফাইল মুছে ফেলা হয়েছে।');
    }

    /** Assign this profile to the given user ids (scoped to current shop); unassign everyone else. */
    private function syncUsers(ReceiptProfile $profile, array $userIds): void
    {
        $shopId = auth()->user()->shop_id;
        User::where('shop_id', $shopId)->where('receipt_profile_id', $profile->id)
            ->whereNotIn('id', $userIds)->update(['receipt_profile_id' => null]);
        if (!empty($userIds)) {
            User::where('shop_id', $shopId)->whereIn('id', $userIds)
                ->update(['receipt_profile_id' => $profile->id]);
        }
    }
}
