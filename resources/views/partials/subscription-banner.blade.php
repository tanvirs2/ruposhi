{{--
    Subscription warning/grace banner.
    Shown only to super_admin users when their license is expiring soon or in grace period.
    Variables shared by CheckSubscription middleware:
      $subscriptionWarning → ['days' => int, 'expires_at' => 'dd M YYYY']
      $subscriptionGrace   → ['days_left' => int, 'grace_ends_at' => 'dd M YYYY']
--}}

@if(isset($subscriptionGrace))
{{-- ── RED: Grace period — expired but still accessible ── --}}
<div style="background:linear-gradient(90deg,#7f1d1d,#dc2626);color:#fff;padding:10px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:.88rem;font-weight:600">
    <span>
        <i class="fas fa-triangle-exclamation"></i>
        &nbsp;আপনার সাবস্ক্রিপশনের মেয়াদ শেষ হয়েছে!
        গ্রেস পিরিয়ড: আর মাত্র <strong>{{ $subscriptionGrace['days_left'] }} দিন</strong> বাকি।
        {{ $subscriptionGrace['grace_ends_at'] }}-এর পর লগইন করতে পারবেন না।
    </span>
    <span style="opacity:.85;font-size:.82rem;white-space:nowrap">
        সাবস্ক্রিপশন নবায়ন করুন
    </span>
</div>

@elseif(isset($subscriptionWarning))
{{-- ── AMBER: Warning — approaching expiry ── --}}
<div style="background:linear-gradient(90deg,#78350f,#d97706);color:#fff;padding:9px 20px;display:flex;align-items:center;justify-content:space-between;gap:12px;font-size:.88rem;font-weight:600">
    <span>
        <i class="fas fa-clock"></i>
        &nbsp;আপনার সাবস্ক্রিপশন <strong>{{ $subscriptionWarning['days'] }} দিনে</strong> শেষ হবে।
        ({{ $subscriptionWarning['expires_at'] }})
    </span>
    <span style="opacity:.85;font-size:.82rem;white-space:nowrap">
        সময়মতো নবায়ন করুন
    </span>
</div>
@endif
