@extends('layouts.app')
@section('title', 'SMS পাঠান')
@section('page-title', 'SMS পাঠান')

@section('content')

{{-- Config warning --}}
@if(!config('sms.api_key') || !config('sms.sender_id'))
<div class="alert alert-error" role="alert" style="margin-bottom:16px">
    <i class="fas fa-circle-exclamation" style="flex-shrink:0"></i>
    <span>SMS API Key বা Sender ID সেট করা নেই। <a href="{{ route('store-config.index') }}" style="color:inherit;font-weight:700;text-decoration:underline">স্টোর কনফিগ</a> থেকে সেট করুন।</span>
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- ── Left: Compose ──────────────────────────── --}}
    <div style="display:flex;flex-direction:column;gap:16px">

        {{-- Customer/Supplier SMS --}}
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem;display:flex;align-items:center;gap:8px">
                <i class="fas fa-paper-plane" style="color:var(--accent)"></i> তালিকা থেকে SMS পাঠান
            </div>
            <div style="padding:20px">
                <form method="POST" action="{{ route('sms.send') }}" id="smsListForm">
                    @csrf

                    {{-- Template selector --}}
                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">টেমপ্লেট নির্বাচন করুন</label>
                        <select class="form-select" id="templateSelect" onchange="applyTemplate(this)">
                            <option value="">— টেমপ্লেট বেছে নিন —</option>
                            @foreach($templates as $i => $t)
                                <option value="{{ $t['text'] }}">{{ $t['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Message --}}
                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">বার্তা <span style="color:#94a3b8;font-weight:400;font-size:.78rem">(সর্বোচ্চ ১৬০ অক্ষর = ১ SMS)</span></label>
                        <textarea name="message" id="smsMessage" class="form-input" rows="4"
                            style="resize:vertical;font-size:.88rem"
                            placeholder="এখানে বার্তা লিখুন..." required maxlength="640"
                            oninput="updateCharCount(this, 'charCount1')"></textarea>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:4px;text-align:right">
                            <span id="charCount1">0</span>/160 অক্ষর
                            <span id="smsCount1" style="margin-left:8px;color:#0d9488;font-weight:600"></span>
                        </div>
                    </div>

                    {{-- Recipient type tabs --}}
                    <div style="margin-bottom:12px">
                        <label class="form-label">প্রাপক নির্বাচন করুন</label>
                        <div style="display:flex;gap:8px;margin-bottom:10px">
                            <button type="button" class="btn btn-secondary btn-sm recip-tab active-tab" onclick="switchTab('customers')" id="tabCustomers">
                                <i class="fas fa-users"></i> কাস্টমার
                            </button>
                            <button type="button" class="btn btn-ghost btn-sm recip-tab" onclick="switchTab('suppliers')" id="tabSuppliers">
                                <i class="fas fa-truck"></i> সরবরাহকারী
                            </button>
                        </div>

                        {{-- Search --}}
                        <div class="search-box" style="margin-bottom:8px">
                            <i class="fas fa-search"></i>
                            <input type="text" id="recipientSearch" placeholder="নাম বা ফোন খুঁজুন..."
                                oninput="filterRecipients(this.value)" style="font-size:.82rem">
                        </div>

                        {{-- Quick select --}}
                        <div style="display:flex;gap:6px;margin-bottom:8px;flex-wrap:wrap">
                            <button type="button" class="btn btn-ghost btn-sm" onclick="selectAllVisible()">সব নির্বাচন</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="deselectAll()">বাতিল</button>
                            <button type="button" class="btn btn-ghost btn-sm" onclick="selectDueOnly()" id="btnDueOnly">
                                <i class="fas fa-exclamation-circle" style="color:#dc2626"></i> শুধু বাকী আছে
                            </button>
                        </div>

                        {{-- Customer list --}}
                        <div id="listCustomers" style="max-height:260px;overflow-y:auto;border:1.5px solid var(--border);border-radius:var(--radius-sm)">
                            @foreach($customers as $c)
                            <label class="recip-row {{ $c->due_amount > 0 ? 'has-due' : '' }}"
                                data-name="{{ strtolower($c->name) }}"
                                data-phone="{{ $c->phone }}"
                                data-due="{{ $c->due_amount }}"
                                data-type="customer">
                                <input type="checkbox" name="recipients[]"
                                    value="{{ $c->name }}||{{ $c->phone }}"
                                    {{ empty($c->phone) ? 'disabled' : '' }}
                                    class="recip-check">
                                <div class="recip-info">
                                    <span class="recip-name">{{ $c->name }}</span>
                                    @if($c->phone)
                                        <span class="recip-phone">{{ $c->phone }}</span>
                                    @else
                                        <span class="recip-phone" style="color:#dc2626">নম্বর নেই</span>
                                    @endif
                                </div>
                                @if($c->due_amount > 0)
                                    <span class="recip-due">৳{{ number_format($c->due_amount, 0) }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>

                        {{-- Supplier list (hidden by default) --}}
                        <div id="listSuppliers" style="max-height:260px;overflow-y:auto;border:1.5px solid var(--border);border-radius:var(--radius-sm);display:none">
                            @foreach($suppliers as $s)
                            <label class="recip-row {{ $s->due_amount > 0 ? 'has-due' : '' }}"
                                data-name="{{ strtolower($s->name) }}"
                                data-phone="{{ $s->phone }}"
                                data-due="{{ $s->due_amount }}"
                                data-type="supplier">
                                <input type="checkbox" name="recipients[]"
                                    value="{{ $s->name }}||{{ $s->phone }}"
                                    {{ empty($s->phone) ? 'disabled' : '' }}
                                    class="recip-check">
                                <div class="recip-info">
                                    <span class="recip-name">{{ $s->name }}</span>
                                    @if($s->phone)
                                        <span class="recip-phone">{{ $s->phone }}</span>
                                    @else
                                        <span class="recip-phone" style="color:#dc2626">নম্বর নেই</span>
                                    @endif
                                </div>
                                @if($s->due_amount > 0)
                                    <span class="recip-due">৳{{ number_format($s->due_amount, 0) }}</span>
                                @endif
                            </label>
                            @endforeach
                        </div>

                        <div style="margin-top:8px;font-size:.78rem;color:#64748b">
                            <span id="selectedCount">০</span> জন নির্বাচিত
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary" style="width:100%" id="sendListBtn">
                        <i class="fas fa-paper-plane"></i> SMS পাঠান
                    </button>
                </form>
            </div>
        </div>

        {{-- Custom number --}}
        <div class="card">
            <div style="padding:16px 20px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem;display:flex;align-items:center;gap:8px">
                <i class="fas fa-keyboard" style="color:#7c3aed"></i> কাস্টম নম্বরে পাঠান
            </div>
            <div style="padding:20px">
                <form method="POST" action="{{ route('sms.send-custom') }}">
                    @csrf
                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">ফোন নম্বর <span style="color:#94a3b8;font-size:.78rem">(কমা বা নতুন লাইনে আলাদা করুন)</span></label>
                        <textarea name="numbers" class="form-input" rows="3"
                            placeholder="01XXXXXXXXX, 01YYYYYYYYY..." required
                            style="font-size:.88rem;resize:vertical"></textarea>
                    </div>
                    <div class="form-group" style="margin-bottom:14px">
                        <label class="form-label">বার্তা</label>
                        <textarea name="message" class="form-input" rows="4"
                            placeholder="এখানে বার্তা লিখুন..." required maxlength="640"
                            style="resize:vertical;font-size:.88rem"
                            oninput="updateCharCount(this, 'charCount2')"></textarea>
                        <div style="font-size:.75rem;color:#94a3b8;margin-top:4px;text-align:right">
                            <span id="charCount2">0</span>/160 অক্ষর
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary" style="width:100%;background:#7c3aed;border-color:#7c3aed">
                        <i class="fas fa-paper-plane"></i> পাঠান
                    </button>
                </form>
            </div>
        </div>
    </div>

    {{-- ── Right: SMS Log ──────────────────────────── --}}
    <div class="card">
        <div style="padding:16px 20px;border-bottom:1.5px solid var(--border);font-weight:700;font-size:.92rem;display:flex;align-items:center;justify-content:space-between">
            <span><i class="fas fa-clock-rotate-left" style="color:#64748b;margin-right:6px"></i>SMS ইতিহাস</span>
            <span style="font-size:.78rem;color:#94a3b8;font-weight:400">সর্বশেষ {{ $logs->total() }} টি</span>
        </div>
        <div class="table-wrap" style="max-height:720px;overflow-y:auto">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>প্রাপক</th>
                        <th>বার্তা</th>
                        <th class="tc">অবস্থা</th>
                        <th class="tc">সময়</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($logs as $log)
                    <tr>
                        <td>
                            <div style="font-weight:600;font-size:.85rem">{{ $log->recipient_name ?? '—' }}</div>
                            <div class="mono" style="font-size:.78rem;color:#64748b">{{ $log->recipient }}</div>
                        </td>
                        <td style="max-width:180px">
                            <div style="font-size:.8rem;color:var(--text-secondary);white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px"
                                title="{{ $log->message }}">{{ $log->message }}</div>
                        </td>
                        <td class="tc">
                            @if($log->status === 'sent')
                                <span class="badge badge-green"><i class="fas fa-circle-check"></i> সফল</span>
                            @elseif($log->status === 'failed')
                                <span class="badge badge-red" title="{{ $log->gateway_response }}"><i class="fas fa-circle-xmark"></i> ব্যর্থ</span>
                            @else
                                <span class="badge" style="background:#f1f5f9;color:#64748b">অপেক্ষমান</span>
                            @endif
                        </td>
                        <td class="tc" style="font-size:.75rem;color:#94a3b8;white-space:nowrap">
                            {{ $log->created_at->format('d M, h:ia') }}
                        </td>
                        <td>
                            <form method="POST" action="{{ route('sms.log.destroy', $log) }}"
                                onsubmit="return confirm('লগ মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="empty-row">এখনো কোনো SMS পাঠানো হয়নি</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
        <div class="pagination-wrap">{{ $logs->links() }}</div>
        @endif
    </div>
</div>

@endsection

@push('styles')
<style>
.recip-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 8px 12px;
    cursor: pointer;
    border-bottom: 1px solid var(--border);
    transition: background .12s;
}
.recip-row:last-child { border-bottom: none; }
.recip-row:hover { background: var(--bg); }
.recip-row input[type=checkbox] { flex-shrink: 0; width: 15px; height: 15px; accent-color: var(--accent); }
.recip-row.disabled-row { opacity: .45; cursor: not-allowed; }
.recip-info { flex: 1; min-width: 0; }
.recip-name { display: block; font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.recip-phone { display: block; font-size: .74rem; color: #64748b; }
.recip-due {
    font-size: .74rem; font-weight: 700; color: #dc2626;
    background: #fee2e2; padding: 2px 7px; border-radius: 999px; flex-shrink: 0;
}
.recip-row.has-due { background: #fff8f8; }
.recip-row.has-due:hover { background: #fff0f0; }
.recip-row.hidden-row { display: none !important; }
.active-tab { background: var(--accent) !important; color: #fff !important; border-color: var(--accent) !important; }
</style>
@endpush

@push('scripts')
<script>
let currentTab = 'customers';

function applyTemplate(sel) {
    if (sel.value) {
        document.getElementById('smsMessage').value = sel.value;
        updateCharCount(document.getElementById('smsMessage'), 'charCount1');
    }
}

function updateCharCount(el, countId) {
    const len   = el.value.length;
    const count = document.getElementById(countId);
    const smsEl = document.getElementById('smsCount1');
    if (count) {
        count.textContent = len;
        count.style.color = len > 160 ? '#dc2626' : '#94a3b8';
    }
    if (smsEl) {
        const msgs = Math.ceil(len / 160) || 0;
        smsEl.textContent = msgs > 1 ? `(${msgs} SMS)` : '';
    }
}

function switchTab(tab) {
    currentTab = tab;
    document.getElementById('listCustomers').style.display = tab === 'customers' ? 'block' : 'none';
    document.getElementById('listSuppliers').style.display = tab === 'suppliers' ? 'block' : 'none';
    document.getElementById('tabCustomers').classList.toggle('active-tab', tab === 'customers');
    document.getElementById('tabCustomers').classList.toggle('btn-secondary', tab === 'customers');
    document.getElementById('tabCustomers').classList.toggle('btn-ghost', tab !== 'customers');
    document.getElementById('tabSuppliers').classList.toggle('active-tab', tab === 'suppliers');
    document.getElementById('tabSuppliers').classList.toggle('btn-secondary', tab === 'suppliers');
    document.getElementById('tabSuppliers').classList.toggle('btn-ghost', tab !== 'suppliers');
    document.getElementById('btnDueOnly').style.display = tab === 'customers' ? 'inline-flex' : 'inline-flex';
    updateSelectedCount();
    filterRecipients(document.getElementById('recipientSearch').value);
}

function filterRecipients(q) {
    q = q.toLowerCase().trim();
    const listId = currentTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row').forEach(row => {
        const name  = row.dataset.name  || '';
        const phone = row.dataset.phone || '';
        const match = !q || name.includes(q) || phone.includes(q);
        row.classList.toggle('hidden-row', !match);
    });
}

function selectAllVisible() {
    const listId = currentTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row:not(.hidden-row) .recip-check:not(:disabled)').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function deselectAll() {
    document.querySelectorAll('.recip-check').forEach(cb => cb.checked = false);
    updateSelectedCount();
}

function selectDueOnly() {
    deselectAll();
    const listId = currentTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row.has-due .recip-check:not(:disabled)').forEach(cb => cb.checked = true);
    updateSelectedCount();
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.recip-check:checked').length;
    document.getElementById('selectedCount').textContent = count;
}

document.addEventListener('change', e => {
    if (e.target.classList.contains('recip-check')) updateSelectedCount();
});

// Confirm before send
document.getElementById('smsListForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.recip-check:checked').length;
    if (count === 0) { e.preventDefault(); alert('কমপক্ষে একজন প্রাপক নির্বাচন করুন।'); return; }
    if (!confirm(`${count} জনকে SMS পাঠাবেন?`)) e.preventDefault();
});
</script>
@endpush
