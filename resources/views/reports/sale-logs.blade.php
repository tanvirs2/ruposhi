@extends('layouts.app')
@section('title', 'বিক্রয় লগ')
@section('page-title', 'বিক্রয় সংশোধন ও মুছে ফেলার ইতিহাস')

@section('content')

{{-- Filter --}}
<div class="card no-print" style="margin-bottom:20px">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="form-group-field">
                <label>শুরুর তারিখ</label>
                <input type="date" name="from" value="{{ $from }}">
            </div>
            <div class="form-group-field">
                <label>শেষ তারিখ</label>
                <input type="date" name="to" value="{{ $to }}">
            </div>
            <div class="form-group-field">
                <label>ধরন</label>
                <select name="action" class="form-select">
                    <option value="">সব</option>
                    <option value="edited"  @selected($action==='edited')>সংশোধিত</option>
                    <option value="deleted" @selected($action==='deleted')>মুছে ফেলা</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary" style="align-self:flex-end">
                <i class="fas fa-search"></i> খুঁজুন
            </button>
        </form>
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
                    <th>মালের বিবরণ</th>
                    <th>কারণ / মন্তব্য</th>
                    <th class="tc">ইউজার</th>
                    <th class="tc">বিস্তারিত</th>
                </tr>
            </thead>
            <tbody>
                @forelse($logs as $log)
                @php $snap = $log->snapshot; $items = $snap['items'] ?? []; @endphp
                <tr>
                    <td class="tc" style="font-size:.78rem;white-space:nowrap;color:#64748b">
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
    <div style="background:#fff;border-radius:14px;padding:24px;max-width:560px;width:94%;max-height:85vh;overflow-y:auto;box-shadow:0 20px 60px rgba(0,0,0,.25)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px">
            <h3 style="font-size:1rem;font-weight:700;color:#0f172a" id="modalTitle">বিস্তারিত</h3>
            <button onclick="closeModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#64748b">✕</button>
        </div>
        <div id="modalBody"></div>
    </div>
</div>

{{-- Store all log data as JSON for modal --}}
<script>
const logData = @json($logs->keyBy('id')->map(fn($l) => ['action'=>$l->action,'note'=>$l->note,'user'=>$l->user?->name,'created_at'=>$l->created_at->format('d/m/Y h:i:s a'),'snapshot'=>$l->snapshot]));

function showDetail(id) {
    const d = logData[id]; if (!d) return;
    const s = d.snapshot;
    const actionBadge = d.action === 'deleted'
        ? '<span style="background:#fee2e2;color:#dc2626;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:700">🗑 মুছে ফেলা</span>'
        : '<span style="background:#fef9c3;color:#92400e;padding:2px 10px;border-radius:20px;font-size:.8rem;font-weight:700">✎ সংশোধিত</span>';

    let items = '';
    (s.items||[]).forEach(i => {
        items += `<tr><td style="padding:5px 8px;border:1px solid #e2e8f0">${i.item_name||'?'}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:center">${i.quantity}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:right">৳ ${Number(i.price).toLocaleString()}</td><td style="padding:5px 8px;border:1px solid #e2e8f0;text-align:right">৳ ${Number(i.subtotal).toLocaleString()}</td></tr>`;
    });

    document.getElementById('modalTitle').innerHTML = `চালান #${String(s.id||'').padStart(6,'0')} ${actionBadge}`;
    document.getElementById('modalBody').innerHTML = `
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:.85rem;margin-bottom:14px">
            <div><strong>তারিখ:</strong> ${s.sale_date||'—'}</div>
            <div><strong>কাস্টমার:</strong> ${s.customer_name||'ওয়াক-ইন'}</div>
            <div><strong>মোট:</strong> ৳ ${Number(s.total_amount||0).toLocaleString()}</div>
            <div><strong>পরিশোধ:</strong> ৳ ${Number(s.paid_amount||0).toLocaleString()}</div>
            <div><strong>বাকী:</strong> ৳ ${Number(s.due_amount||0).toLocaleString()}</div>
            <div><strong>পদ্ধতি:</strong> ${s.payment_method||'—'}</div>
        </div>
        ${items ? `<table style="width:100%;border-collapse:collapse;font-size:.82rem;margin-bottom:12px"><thead><tr style="background:#f1f5f9"><th style="padding:5px 8px;border:1px solid #e2e8f0;text-align:left">পণ্য</th><th style="padding:5px 8px;border:1px solid #e2e8f0">পরিমাণ</th><th style="padding:5px 8px;border:1px solid #e2e8f0">দর</th><th style="padding:5px 8px;border:1px solid #e2e8f0">মোট</th></tr></thead><tbody>${items}</tbody></table>` : ''}
        ${d.note ? `<div style="padding:8px 12px;background:#fef9c3;border:1px dashed #fde68a;border-radius:6px;font-size:.82rem;color:#92400e"><strong>কারণ:</strong> ${d.note}</div>` : ''}
        <div style="margin-top:10px;font-size:.76rem;color:#94a3b8">লগ সময়: ${d.created_at} &nbsp;|&nbsp; ইউজার: ${d.user||'—'}</div>
    `;
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
