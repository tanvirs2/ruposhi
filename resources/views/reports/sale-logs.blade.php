@extends('layouts.app')
@section('title', 'বিক্রয় লগ')
@section('page-title', 'বিক্রয় সংশোধন ও মুছে ফেলার ইতিহাস')

@section('content')

@push('styles')
<meta name="turbo-cache-control" content="no-cache">
@endpush

{{-- Filter --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form" data-date-snap>
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            @include('partials.date-range-buttons')
            {{-- preserve the active type filter across date searches --}}
            <input type="hidden" name="action" value="{{ $action }}">
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> খুঁজুন
            </button>
        </form>
    </div>

    {{-- ধরন filter chips --}}
    @php
        $logChips = [
            ''        => ['সব',        $counts[''],        '#475569'],
            'edited'  => ['সংশোধিত',   $counts['edited'],  '#d97706'],
            'deleted' => ['মুছে ফেলা', $counts['deleted'], '#dc2626'],
        ];
    @endphp
    <div class="filter-chips" style="border-top:1px solid var(--border);border-bottom:none">
        @foreach($logChips as $key => [$label, $cnt, $color])
            <a href="{{ route('reports.sale-logs', array_merge(request()->except(['action','page']), ['action' => $key])) }}"
               class="fchip {{ $action === $key ? 'fchip-active' : '' }}" style="--chip:{{ $color }}">
                {{ $label }}<span class="fchip-count">{{ $cnt }}</span>
            </a>
        @endforeach
    </div>
</div>

<div class="card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
        <h3><i class="fas fa-clock-rotate-left"></i> লগ ইতিহাস</h3>
        <span style="font-size:.8rem;color:#94a3b8">{{ $logs->total() }} টি রেকর্ড</span>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="tc">সময়</th>
                    <th class="tc">চালান নং</th>
                    <th class="tc">ধরন</th>
                    <th>কাস্টমার</th>
                    <th class="tr">মোট</th>
                    <th class="tr">পরিশোধ</th>
                    <th>পণ্যের বিবরণ</th>
                    <th>কারণ / মন্তব্য</th>
                    <th class="tc">ইউজার</th>
                    <th class="tc">বিস্তারিত</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php $snap = $log->snapshot; $items = $snap['items'] ?? []; @endphp
                <tr>
                    <td class="tc" style="font-size:.78rem;white-space:nowrap;color:#334155">
                        {{ $log->created_at->format('d/m/Y h:i a') }}
                    </td>
                    <td class="tc mono">
                        #{{ str_pad($snap['id'] ?? $log->sale_id, 6, '0', STR_PAD_LEFT) }}
                    </td>
                    <td class="tc">
                        @if($log->action === 'deleted')
                            <span class="badge badge-red"><i class="fas fa-trash"></i> মুছে ফেলা</span>
                        @else
                            <span class="badge" style="background:#fef9c3;color:#92400e"><i class="fas fa-pen"></i> সংশোধিত</span>
                        @endif
                    </td>
                    <td>{{ $snap['customer_name'] ?? '—' }}</td>
                    <td class="tr">৳ {{ number_format($snap['total_amount'] ?? 0, 0) }}</td>
                    <td class="tr" style="color:#16a34a">৳ {{ number_format($snap['paid_amount'] ?? 0, 0) }}</td>
                    <td style="font-size:.78rem;color:#475569;max-width:200px">
                        @if(count($items))
                            @php
                                $lines = collect($items)->map(fn($i) => ($i['item_name']??'?').' ×'.(int)($i['quantity']??0))->toArray();
                                $preview = implode(', ', array_slice($lines, 0, 2));
                            @endphp
                            {{ $preview }}{{ count($lines) > 2 ? ', ...' : '' }}
                        @else
                            <span style="color:#94a3b8">পণ্য নেই</span>
                        @endif
                    </td>
                    <td style="font-size:.8rem;color:#64748b;max-width:160px">
                        {{ $log->note ?: '—' }}
                    </td>
                    <td class="tc" style="font-size:.78rem;color:#64748b">{{ $log->user?->name ?? '—' }}</td>
                    <td class="tc">
                        <button type="button" onclick="showDetail({{ $log->id }})"
                            class="btn-icon-sm" title="বিস্তারিত দেখুন" style="color:#0d9488">
                            <i class="fas fa-eye"></i>
                        </button>
                    </td>
                </tr>
                @empty
                <tr><td colspan="10" class="empty-row">কোনো লগ পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $logs->withQueryString()->links() }}</div>
</div>

{{-- Detail Modal --}}
<div id="logModal" style="display:none;position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:#fff;border-radius:14px;padding:24px;max-width:660px;width:94%;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:1rem;font-weight:700;color:#0f172a" id="modalTitle">বিস্তারিত</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b">✕</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

@php
$logJson = [];
foreach ($logs as $l) {
    $logJson[$l->id] = [
        'action'     => $l->action,
        'note'       => $l->note,
        'user'       => $l->user?->name,
        'created_at' => $l->created_at->format('d/m/Y h:i:s a'),
        'snapshot'   => $l->snapshot,
    ];
}
@endphp

{{-- Store all log data as JSON for modal --}}
<script>
var logData = {!! json_encode($logJson) !!};

function itemsTable(items) {
    if (!items || !items.length) return '<div style="color:#94a3b8;font-size:.82rem">পণ্য নেই</div>';
    let rows = '';
    items.forEach(i => {
        rows += `<tr><td style="padding:5px 8px;border:1px solid #e2e8f0">${i.item_name||'?'}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:center">${i.quantity}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:right">৳ ${Number(i.price).toLocaleString()}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:right">৳ ${Number(i.subtotal).toLocaleString()}</td></tr>`;
    });
    return `<table style="width:100%;border-collapse:collapse;font-size:.8rem"><thead><tr style="background:#f1f5f9"><th style="padding:5px 8px;border:1px solid #e2e8f0;text-align:left">পণ্য</th><th style="padding:5px 8px;border:1px solid #e2e8f0">পরিমাণ</th><th style="padding:5px 8px;border:1px solid #e2e8f0">দর</th><th style="padding:5px 8px;border:1px solid #e2e8f0">মোট</th></tr></thead><tbody>${rows}</tbody></table>`;
}

function showDetail(id) {
    const d = logData[id]; if (!d) return;
    const s = d.snapshot;
    const before = s.before || null; // present only for edits — the pre-edit state
    const actionBadge = d.action === 'deleted'
        ? '<span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:700">🗑 মুছে ফেলা</span>'
        : '<span style="background:#fef9c3;color:#92400e;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:700">✎ সংশোধিত</span>';

    document.getElementById('modalTitle').innerHTML = `চালান #${String(s.id||'').padStart(6,'0')} ${actionBadge}`;

    let body = '';

    if (before) {
        // ── Edit with a captured before-state → show a field-by-field diff ──
        const fields = [
            ['sale_date',      'তারিখ',    v => v || '—'],
            ['customer_name',  'কাস্টমার', v => v || 'ওয়াক-ইন'],
            ['total_amount',   'মোট',      v => '৳ ' + Number(v||0).toLocaleString()],
            ['discount',       'ছাড়',      v => '৳ ' + Number(v||0).toLocaleString()],
            ['extra_cost',     'অতিরিক্ত খরচ', v => '৳ ' + Number(v||0).toLocaleString()],
            ['paid_amount',    'পরিশোধ',   v => '৳ ' + Number(v||0).toLocaleString()],
            ['due_amount',     'বাকী',     v => '৳ ' + Number(v||0).toLocaleString()],
            ['payment_method', 'পদ্ধতি',   v => v || '—'],
            ['notes',          'নোট',      v => v || '—'],
        ];
        let rows = '';
        fields.forEach(([key, label, fmt]) => {
            const oldV = before[key], newV = s[key];
            const changed = JSON.stringify(oldV) !== JSON.stringify(newV);
            if (!changed) return;
            rows += `<tr>
                <td style="padding:6px 8px;border:1px solid #e2e8f0;font-weight:600;color:#334155">${label}</td>
                <td style="padding:6px 8px;border:1px solid #e2e8f0;color:#b91c1c;text-decoration:line-through;background:#fef2f2">${fmt(oldV)}</td>
                <td style="padding:6px 8px;border:1px solid #e2e8f0;color:#15803d;font-weight:600;background:#f0fdf4">${fmt(newV)}</td>
            </tr>`;
        });

        const oldItemsKey = JSON.stringify((before.items||[]).map(i => [i.item_name, i.quantity, i.price]));
        const newItemsKey = JSON.stringify((s.items||[]).map(i => [i.item_name, i.quantity, i.price]));
        const itemsChanged = oldItemsKey !== newItemsKey;

        body += `<div style="font-size:.8rem;color:#64748b;margin-bottom:8px">সংশোধনের আগে যা ছিল, তার সাথে সংশোধনের পর যা হয়েছে তার তুলনা — নিচে শুধু পরিবর্তিত ক্ষেত্রগুলো দেখানো হয়েছে।</div>`;
        if (rows) {
            body += `<table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:14px">
                <thead><tr style="background:#f1f5f9"><th style="padding:6px 8px;border:1px solid #e2e8f0;text-align:left">ক্ষেত্র</th><th style="padding:6px 8px;border:1px solid #e2e8f0;text-align:left">আগে</th><th style="padding:6px 8px;border:1px solid #e2e8f0;text-align:left">পরে</th></tr></thead>
                <tbody>${rows}</tbody>
            </table>`;
        } else if (!itemsChanged) {
            body += `<div style="padding:8px 12px;background:#f1f5f9;border-radius:6px;font-size:.82rem;color:#64748b;margin-bottom:14px">কোনো মান পরিবর্তন হয়নি।</div>`;
        }

        if (itemsChanged) {
            body += `<div style="font-weight:700;font-size:.82rem;color:#334155;margin-bottom:4px">পণ্য — আগে</div>
                <div style="margin-bottom:10px">${itemsTable(before.items)}</div>
                <div style="font-weight:700;font-size:.82rem;color:#334155;margin-bottom:4px">পণ্য — পরে</div>
                <div style="margin-bottom:14px">${itemsTable(s.items)}</div>`;
        } else if (s.items && s.items.length) {
            body += `<div style="font-weight:700;font-size:.82rem;color:#334155;margin-bottom:4px">পণ্য (অপরিবর্তিত)</div>
                <div style="margin-bottom:14px">${itemsTable(s.items)}</div>`;
        }
    } else {
        // ── No before-state (deletion, or an older log row) → single snapshot ──
        body += `
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.85rem;margin-bottom:14px">
                <div><strong>তারিখ:</strong> ${s.sale_date||'—'}</div>
                <div><strong>কাস্টমার:</strong> ${s.customer_name||'ওয়াক-ইন'}</div>
                <div><strong>মোট:</strong> ৳ ${Number(s.total_amount||0).toLocaleString()}</div>
                <div><strong>পরিশোধ:</strong> ৳ ${Number(s.paid_amount||0).toLocaleString()}</div>
                <div><strong>বাকী:</strong> ৳ ${Number(s.due_amount||0).toLocaleString()}</div>
                <div><strong>পদ্ধতি:</strong> ${s.payment_method||'—'}</div>
            </div>
            <div style="margin-bottom:12px">${itemsTable(s.items)}</div>
        `;
    }

    body += `${d.note ? `<div style="padding:8px 12px;background:#fef9c3;border:1px dashed #fde68a;border-radius:6px;font-size:.82rem;color:#92400e"><strong>কারণ:</strong> ${d.note}</div>` : ''}
        <div style="margin-top:10px;font-size:.76rem;color:#94a3b8">লগ সময়: ${d.created_at} &nbsp;|&nbsp; ইউজার: ${d.user||'—'}</div>`;

    document.getElementById('modalBody').innerHTML = body;
    document.getElementById('logModal').style.display = 'flex';
}
function closeModal() { document.getElementById('logModal').style.display = 'none'; }
document.getElementById('logModal').addEventListener('click', function(e) { if(e.target===this) closeModal(); });
</script>

@push('styles')
<style>
.tc { text-align:center; }
.tr { text-align:right; }
.mono { font-family:'Inter',monospace;font-size:.8rem; }
</style>
@endpush
@endsection
