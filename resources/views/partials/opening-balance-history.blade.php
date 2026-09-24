{{-- পুরনো বাকী/দেনা বদলানোর ইতিহাস — লেজারে দেখায়, যাতে হিসাব চুপচাপ বদলাতে না পারে।
     প্যারামিটার: $obLogs (OpeningBalanceLog কালেকশন), $obLabel ('পুরনো বাকী' / 'পুরনো দেনা')
     no-print: কাস্টমারকে দেওয়া লেজার প্রিন্টে ভেতরের অডিট নোট যায় না। --}}
@if(isset($obLogs) && $obLogs->isNotEmpty())
<div class="card no-print" style="margin-bottom:18px;border-left:4px solid #f59e0b">
    <div class="card-header" style="background:#fffbeb">
        <h3 style="color:#92400e;font-size:.92rem"><i class="fas fa-clock-rotate-left"></i> {{ $obLabel }} সংশোধনের ইতিহাস ({{ $obLogs->count() }} বার)</h3>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>সময়</th>
                    <th class="tr">আগের অঙ্ক</th>
                    <th class="tr">নতুন অঙ্ক</th>
                    <th class="tr">পার্থক্য</th>
                    <th>কে বদলেছেন</th>
                    <th>কারণ</th>
                </tr>
            </thead>
            <tbody>
                @foreach($obLogs as $log)
                @php $diff = $log->new_value - $log->old_value; @endphp
                <tr>
                    <td style="white-space:nowrap;font-size:.8rem">{{ $log->created_at->format('d/m/Y h:i a') }}</td>
                    <td class="tr">৳ {{ number_format($log->old_value, 0) }}</td>
                    <td class="tr" style="font-weight:700">৳ {{ number_format($log->new_value, 0) }}</td>
                    <td class="tr" style="font-weight:700;color:{{ $diff > 0 ? '#dc2626' : '#15803d' }}">
                        {{ $diff > 0 ? '+' : '− ' }}৳ {{ number_format(abs($diff), 0) }}
                    </td>
                    <td style="font-weight:600">{{ $log->user?->name ?? 'অজানা' }}</td>
                    <td style="font-size:.82rem;color:#475569">{{ $log->reason ?? '— (লেনদেন শুরুর আগে)' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
