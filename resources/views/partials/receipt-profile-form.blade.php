{{--
    Reusable receipt-profile form (create or edit).
    Props: $action, $method ('POST'|'PUT'), $rp (ReceiptProfile|null), $shopUsers, $submitLabel
--}}
<form method="POST" action="{{ $action }}">
    @csrf
    @if($method === 'PUT') @method('PUT') @endif
    <div class="form-grid">
        <div class="form-group-field">
            <label>প্রোফাইলের নাম (শুধু নিজের রেফারেন্সের জন্য) <span class="req">*</span></label>
            <input type="text" name="name" value="{{ old('name', $rp->name ?? '') }}" required placeholder="যেমনঃ শাখা ২ প্রোফাইল">
        </div>
        <div class="form-group-field">
            <label>স্টোরের নাম <span class="req">*</span></label>
            <input type="text" name="store_name" value="{{ old('store_name', $rp->store_name ?? '') }}" required>
        </div>
        <div class="form-group-field">
            <label>মালিকের নাম (প্রোপ্রাইটর)</label>
            <input type="text" name="store_owner" value="{{ old('store_owner', $rp->store_owner ?? '') }}">
        </div>
        <div class="form-group-field form-full">
            <label>ব্যবসার বিবরণ (ট্যাগলাইন)</label>
            <input type="text" name="store_tagline" value="{{ old('store_tagline', $rp->store_tagline ?? '') }}">
        </div>
        <div class="form-group-field">
            <label>ফোন নম্বর ১</label>
            <input type="text" name="store_phone" value="{{ old('store_phone', $rp->store_phone ?? '') }}">
        </div>
        <div class="form-group-field">
            <label>ফোন নম্বর ২</label>
            <input type="text" name="store_phone2" value="{{ old('store_phone2', $rp->store_phone2 ?? '') }}">
        </div>
        <div class="form-group-field">
            <label>মুদ্রা চিহ্ন</label>
            <input type="text" name="currency" value="{{ old('currency', $rp->currency ?? '৳') }}">
        </div>
        <div class="form-group-field form-full">
            <label>ঠিকানা</label>
            <textarea name="store_address" rows="2">{{ old('store_address', $rp->store_address ?? '') }}</textarea>
        </div>
    </div>

    <div style="margin-top:14px">
        <label style="display:block;margin-bottom:8px;font-size:.85rem;font-weight:600">এই প্রোফাইল যেসব ইউজারকে assign করবেন</label>
        @if($shopUsers->isEmpty())
        <div style="font-size:.82rem;color:var(--text-secondary)">কোনো স্টাফ/অ্যাডমিন ইউজার পাওয়া যায়নি।</div>
        @else
        <div style="display:flex;flex-wrap:wrap;gap:10px">
            @foreach($shopUsers as $u)
            @php $checked = $rp && (int) $u->receipt_profile_id === (int) $rp->id; @endphp
            <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;background:var(--bg);border:1px solid var(--border);border-radius:6px;padding:6px 10px;cursor:pointer">
                <input type="checkbox" name="user_ids[]" value="{{ $u->id }}" @checked($checked)>
                {{ $u->name }}
                <span style="color:var(--text-secondary);font-size:.75rem">({{ $u->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ' }})</span>
            </label>
            @endforeach
        </div>
        @endif
    </div>

    <div style="margin-top:16px;display:flex;gap:10px">
        <button type="submit" class="btn btn-primary btn-sm">{{ $submitLabel }}</button>
    </div>
</form>
