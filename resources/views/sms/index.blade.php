@extends('layouts.app')
@section('title', 'SMS পাঠান')
@section('page-title', 'SMS পাঠান')

@section('content')

{{-- ── Stats bar ──────────────────────────────────────────── --}}
@php
    $totalSent    = \App\Models\SmsLog::where('status','sent')->count();
    $totalFailed  = \App\Models\SmsLog::where('status','failed')->count();
    $totalToday   = \App\Models\SmsLog::whereDate('created_at', today())->count();
@endphp
<div class="stats-grid" style="margin-bottom:20px;grid-template-columns:repeat(4,1fr)">
    <div class="stat-card stat-blue">
        <div class="stat-icon"><i class="fas fa-comment-sms"></i></div>
        <div class="stat-body">
            <span class="stat-label">মোট পাঠানো</span>
            <span class="stat-value">{{ number_format($totalSent) }}</span>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #22c55e">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a"><i class="fas fa-circle-check"></i></div>
        <div class="stat-body">
            <span class="stat-label">আজকের SMS</span>
            <span class="stat-value">{{ number_format($totalToday) }}</span>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid #ef4444">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626"><i class="fas fa-circle-xmark"></i></div>
        <div class="stat-body">
            <span class="stat-label">ব্যর্থ</span>
            <span class="stat-value">{{ number_format($totalFailed) }}</span>
        </div>
    </div>
    <div class="stat-card" style="border-left:4px solid {{ config('sms.api_key') ? '#0d9488' : '#f59e0b' }}">
        <div class="stat-icon" style="background:{{ config('sms.api_key') ? '#ccfbf1' : '#fef3c7' }};color:{{ config('sms.api_key') ? '#0d9488' : '#d97706' }}">
            <i class="fas fa-{{ config('sms.api_key') ? 'plug-circle-check' : 'plug-circle-exclamation' }}"></i>
        </div>
        <div class="stat-body">
            <span class="stat-label">API অবস্থা</span>
            <span class="stat-value" style="font-size:.92rem">
                @if(config('sms.api_key') && config('sms.sender_id'))
                    <span style="color:#0d9488">সংযুক্ত</span>
                @else
                    <a href="{{ route('store-config.index') }}" style="color:#d97706;font-size:.82rem">সেটআপ করুন</a>
                @endif
            </span>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:480px 1fr;gap:20px;align-items:start">

{{-- ══ LEFT: Compose Panel ═══════════════════════════════════ --}}
<div style="display:flex;flex-direction:column;gap:16px">

    {{-- Compose card --}}
    <div class="card">
        {{-- Tab header --}}
        <div style="display:flex;border-bottom:1.5px solid var(--border)">
            <button type="button" id="tabBtnList" onclick="switchMode('list')"
                style="flex:1;padding:14px;font-size:.85rem;font-weight:700;border:none;cursor:pointer;border-bottom:3px solid var(--accent);color:var(--accent);background:transparent;transition:.15s">
                <i class="fas fa-users" style="margin-right:6px"></i>তালিকা থেকে
            </button>
            <button type="button" id="tabBtnCustom" onclick="switchMode('custom')"
                style="flex:1;padding:14px;font-size:.85rem;font-weight:700;border:none;cursor:pointer;border-bottom:3px solid transparent;color:var(--text-secondary);background:transparent;transition:.15s">
                <i class="fas fa-keyboard" style="margin-right:6px"></i>কাস্টম নম্বর
            </button>
        </div>

        {{-- ── MODE: List ── --}}
        <div id="modeList" style="padding:20px">
            <form method="POST" action="{{ route('sms.send') }}" id="smsListForm">
                @csrf

                {{-- Message composition --}}
                <div style="background:var(--bg);border-radius:var(--radius-sm);padding:16px;margin-bottom:16px;border:1.5px solid var(--border)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                        <label style="font-size:.82rem;font-weight:700;color:var(--text-secondary)">
                            <i class="fas fa-pencil" style="margin-right:4px"></i> বার্তা লিখুন
                        </label>
                        <span id="charPill" style="font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:999px;background:#f1f5f9;color:#64748b">0 / 160</span>
                    </div>
                    <textarea name="message" id="smsMessage" rows="5"
                        style="width:100%;border:none;background:transparent;resize:none;font-family:inherit;font-size:.88rem;color:var(--text-primary);outline:none;line-height:1.6"
                        placeholder="এখানে বার্তা লিখুন..." required maxlength="640"
                        oninput="updateChar(this)"></textarea>
                </div>

                {{-- Templates --}}
                <div style="margin-bottom:16px">
                    <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">
                        টেমপ্লেট
                    </div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($templates as $t)
                        @if($t['text'])
                        <button type="button" class="tmpl-chip" onclick="useTemplate({{ json_encode($t['text']) }})">
                            {{ $t['label'] }}
                        </button>
                        @endif
                        @endforeach
                    </div>
                </div>

                {{-- Recipient picker --}}
                <div style="border:1.5px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;margin-bottom:14px">
                    {{-- Picker header --}}
                    <div style="background:var(--bg);padding:10px 14px;display:flex;align-items:center;gap:8px;border-bottom:1.5px solid var(--border)">
                        <button type="button" class="recip-tab-btn active" id="rcTabCustomers" onclick="switchRecipTab('customers')">
                            <i class="fas fa-users"></i> কাস্টমার
                            <span class="rcnt" id="rcntCustomers">{{ $customers->count() }}</span>
                        </button>
                        <button type="button" class="recip-tab-btn" id="rcTabSuppliers" onclick="switchRecipTab('suppliers')">
                            <i class="fas fa-truck"></i> সরবরাহকারী
                            <span class="rcnt" id="rcntSuppliers">{{ $suppliers->count() }}</span>
                        </button>
                        <div style="flex:1"></div>
                        <div class="search-box" style="max-width:160px;height:30px">
                            <i class="fas fa-search" style="font-size:.72rem"></i>
                            <input type="text" id="recipSearch" placeholder="খুঁজুন..."
                                oninput="filterRecip(this.value)"
                                style="font-size:.78rem">
                        </div>
                    </div>

                    {{-- Quick actions --}}
                    <div style="padding:7px 14px;display:flex;gap:6px;border-bottom:1px solid var(--border);background:var(--surface)">
                        <button type="button" class="qbtn" onclick="selectAllVisible()"><i class="fas fa-check-double"></i> সব</button>
                        <button type="button" class="qbtn" onclick="deselectAll()"><i class="fas fa-xmark"></i> বাতিল</button>
                        <button type="button" class="qbtn qbtn-red" onclick="selectDueOnly()">
                            <i class="fas fa-exclamation-circle"></i> বাকী আছে
                        </button>
                        <div style="flex:1"></div>
                        <span style="font-size:.75rem;color:var(--text-secondary);align-self:center">
                            <span id="selCount" style="font-weight:700;color:var(--accent)">০</span> নির্বাচিত
                        </span>
                    </div>

                    {{-- Customer list --}}
                    <div id="listCustomers" style="max-height:240px;overflow-y:auto">
                        @forelse($customers as $c)
                        <label class="recip-row {{ $c->due_amount > 0 ? 'has-due' : '' }} {{ empty($c->phone) ? 'no-phone' : '' }}"
                            data-name="{{ strtolower($c->name) }}" data-phone="{{ $c->phone }}"
                            data-due="{{ $c->due_amount }}" data-tab="customers">
                            <input type="checkbox" name="recipients[]"
                                value="{{ $c->name }}||{{ $c->phone }}"
                                class="recip-cb" {{ empty($c->phone) ? 'disabled' : '' }}>
                            <div class="ri">
                                <span class="ri-name">{{ $c->name }}</span>
                                <span class="ri-phone">{{ $c->phone ?: '— নম্বর নেই' }}</span>
                            </div>
                            @if($c->due_amount > 0)
                            <span class="due-pill">৳{{ number_format($c->due_amount, 0) }}</span>
                            @endif
                        </label>
                        @empty
                        <div style="padding:20px;text-align:center;color:#94a3b8;font-size:.82rem">কোনো কাস্টমার নেই</div>
                        @endforelse
                    </div>

                    {{-- Supplier list --}}
                    <div id="listSuppliers" style="max-height:240px;overflow-y:auto;display:none">
                        @forelse($suppliers as $s)
                        <label class="recip-row {{ $s->due_amount > 0 ? 'has-due' : '' }} {{ empty($s->phone) ? 'no-phone' : '' }}"
                            data-name="{{ strtolower($s->name) }}" data-phone="{{ $s->phone }}"
                            data-due="{{ $s->due_amount }}" data-tab="suppliers">
                            <input type="checkbox" name="recipients[]"
                                value="{{ $s->name }}||{{ $s->phone }}"
                                class="recip-cb" {{ empty($s->phone) ? 'disabled' : '' }}>
                            <div class="ri">
                                <span class="ri-name">{{ $s->name }}</span>
                                <span class="ri-phone">{{ $s->phone ?: '— নম্বর নেই' }}</span>
                            </div>
                            @if($s->due_amount > 0)
                            <span class="due-pill">৳{{ number_format($s->due_amount, 0) }}</span>
                            @endif
                        </label>
                        @empty
                        <div style="padding:20px;text-align:center;color:#94a3b8;font-size:.82rem">কোনো সরবরাহকারী নেই</div>
                        @endforelse
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" style="width:100%;height:44px;font-size:.92rem;font-weight:700">
                    <i class="fas fa-paper-plane"></i>&nbsp; SMS পাঠান
                </button>
            </form>
        </div>

        {{-- ── MODE: Custom ── --}}
        <div id="modeCustom" style="padding:20px;display:none">
            <form method="POST" action="{{ route('sms.send-custom') }}">
                @csrf
                <div style="margin-bottom:14px">
                    <label style="font-size:.82rem;font-weight:700;color:var(--text-secondary);display:block;margin-bottom:6px">
                        <i class="fas fa-mobile-screen-button" style="margin-right:4px"></i>
                        ফোন নম্বর
                        <span style="font-weight:400;color:#94a3b8">(কমা বা নতুন লাইনে আলাদা করুন)</span>
                    </label>
                    <textarea name="numbers" class="form-input" rows="3"
                        placeholder="01711000000&#10;01811000000, 01911000000"
                        required style="font-size:.88rem;resize:none;font-family:monospace"></textarea>
                </div>
                <div style="background:var(--bg);border-radius:var(--radius-sm);padding:16px;margin-bottom:14px;border:1.5px solid var(--border)">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                        <label style="font-size:.82rem;font-weight:700;color:var(--text-secondary)">
                            <i class="fas fa-pencil" style="margin-right:4px"></i> বার্তা লিখুন
                        </label>
                        <span id="charPill2" style="font-size:.72rem;font-weight:700;padding:2px 9px;border-radius:999px;background:#f1f5f9;color:#64748b">0 / 160</span>
                    </div>
                    <textarea name="message" rows="5"
                        style="width:100%;border:none;background:transparent;resize:none;font-family:inherit;font-size:.88rem;color:var(--text-primary);outline:none;line-height:1.6"
                        placeholder="এখানে বার্তা লিখুন..." required maxlength="640"
                        oninput="updateChar2(this)"></textarea>
                </div>
                {{-- Templates for custom mode --}}
                <div style="margin-bottom:14px">
                    <div style="font-size:.78rem;font-weight:700;color:var(--text-secondary);margin-bottom:8px;text-transform:uppercase;letter-spacing:.05em">টেমপ্লেট</div>
                    <div style="display:flex;flex-wrap:wrap;gap:6px">
                        @foreach($templates as $t)
                        @if($t['text'])
                        <button type="button" class="tmpl-chip" onclick="useTemplate2({{ json_encode($t['text']) }})">{{ $t['label'] }}</button>
                        @endif
                        @endforeach
                    </div>
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;height:44px;font-size:.92rem;font-weight:700;background:#7c3aed;border-color:#7c3aed">
                    <i class="fas fa-paper-plane"></i>&nbsp; পাঠান
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ══ RIGHT: SMS Log ══════════════════════════════════════════ --}}
<div class="card">
    <div style="padding:16px 20px;border-bottom:1.5px solid var(--border);display:flex;align-items:center;justify-content:space-between">
        <span style="font-weight:700;font-size:.92rem">
            <i class="fas fa-clock-rotate-left" style="color:#64748b;margin-right:7px"></i>SMS ইতিহাস
        </span>
        <span style="font-size:.78rem;color:#94a3b8">মোট {{ $logs->total() }} টি</span>
    </div>

    @if($logs->isEmpty())
    <div style="padding:48px 20px;text-align:center">
        <i class="fas fa-comment-slash" style="font-size:2.2rem;color:#cbd5e1;display:block;margin-bottom:12px"></i>
        <div style="color:#94a3b8;font-size:.88rem">এখনো কোনো SMS পাঠানো হয়নি</div>
    </div>
    @else
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>প্রাপক</th>
                    <th>বার্তা</th>
                    <th class="tc">অবস্থা</th>
                    <th class="tc">সময়</th>
                    <th class="tc" style="width:44px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td style="white-space:nowrap">
                        <div style="font-weight:600;font-size:.83rem;color:var(--text-primary)">
                            {{ $log->recipient_name ?? '—' }}
                        </div>
                        <div class="mono" style="font-size:.76rem;color:#64748b">{{ $log->recipient }}</div>
                    </td>
                    <td>
                        <div style="font-size:.8rem;color:var(--text-secondary);max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"
                             title="{{ $log->message }}">{{ $log->message }}</div>
                    </td>
                    <td class="tc">
                        @if($log->status === 'sent')
                            <span class="badge badge-green" style="white-space:nowrap">
                                <i class="fas fa-check"></i> সফল
                            </span>
                        @elseif($log->status === 'failed')
                            <span class="badge badge-red" style="white-space:nowrap" title="{{ $log->gateway_response }}">
                                <i class="fas fa-xmark"></i> ব্যর্থ
                            </span>
                        @else
                            <span class="badge" style="background:#f1f5f9;color:#64748b;white-space:nowrap">অপেক্ষমান</span>
                        @endif
                    </td>
                    <td class="tc" style="font-size:.75rem;color:#94a3b8;white-space:nowrap">
                        {{ $log->created_at->format('d M') }}<br>
                        <span style="color:#cbd5e1">{{ $log->created_at->format('h:ia') }}</span>
                    </td>
                    <td class="tc">
                        <form method="POST" action="{{ route('sms.log.destroy', $log) }}"
                              onsubmit="return confirm('লগ মুছে ফেলবেন?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @if($logs->hasPages())
    <div class="pagination-wrap">{{ $logs->withQueryString()->links() }}</div>
    @endif
    @endif
</div>

</div>{{-- end grid --}}
@endsection

@push('styles')
<style>
/* ── Template chips ──────────────────────────────────────── */
.tmpl-chip {
    display: inline-flex; align-items: center;
    padding: 4px 12px;
    font-size: .76rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    transition: border-color .15s, color .15s, background .15s;
    font-family: inherit;
}
.tmpl-chip:hover { border-color: var(--accent); color: var(--accent); background: #f0fdfa; }

/* ── Recipient tabs ──────────────────────────────────────── */
.recip-tab-btn {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 5px 12px;
    font-size: .78rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    transition: .15s;
    font-family: inherit;
}
.recip-tab-btn.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.rcnt {
    display: inline-flex; align-items: center; justify-content: center;
    min-width: 18px; height: 18px;
    font-size: .68rem; font-weight: 700;
    background: rgba(255,255,255,.25);
    border-radius: 999px;
    padding: 0 4px;
}
.recip-tab-btn:not(.active) .rcnt { background: var(--bg); color: var(--text-secondary); }

/* ── Quick action buttons ────────────────────────────────── */
.qbtn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 10px;
    font-size: .74rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    font-family: inherit;
    transition: .12s;
}
.qbtn:hover { border-color: var(--accent); color: var(--accent); }
.qbtn-red:hover { border-color: #dc2626; color: #dc2626; }

/* ── Recipient rows ──────────────────────────────────────── */
.recip-row {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 14px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background .12s;
    user-select: none;
}
.recip-row:last-child { border-bottom: none; }
.recip-row:hover:not(.no-phone) { background: var(--bg); }
.recip-row.no-phone { opacity: .45; cursor: not-allowed; }
.recip-row.has-due { background: #fff9f9; }
.recip-row.has-due:hover:not(.no-phone) { background: #fff0f0; }
.recip-row.hidden-row { display: none !important; }
.recip-row input[type=checkbox] {
    width: 15px; height: 15px;
    accent-color: var(--accent);
    flex-shrink: 0; cursor: pointer;
}
.ri { flex: 1; min-width: 0; }
.ri-name { display: block; font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.ri-phone { display: block; font-size: .74rem; color: #64748b; font-family: monospace; }
.due-pill {
    font-size: .72rem; font-weight: 700;
    color: #dc2626; background: #fee2e2;
    padding: 2px 8px; border-radius: 999px; flex-shrink: 0;
}

/* ── Mode tab header ─────────────────────────────────────── */
#tabBtnList, #tabBtnCustom { outline: none; }
#tabBtnList:hover, #tabBtnCustom:hover { background: var(--bg) !important; }
</style>
@endpush

@push('scripts')
<script>
// ── Mode tabs (list / custom) ───────────────────────────────
function switchMode(mode) {
    const isList = mode === 'list';
    document.getElementById('modeList').style.display   = isList ? 'block' : 'none';
    document.getElementById('modeCustom').style.display = isList ? 'none'  : 'block';
    const bl = document.getElementById('tabBtnList');
    const bc = document.getElementById('tabBtnCustom');
    bl.style.borderBottomColor = isList ? 'var(--accent)' : 'transparent';
    bl.style.color             = isList ? 'var(--accent)' : 'var(--text-secondary)';
    bl.style.fontWeight        = isList ? '700' : '600';
    bc.style.borderBottomColor = isList ? 'transparent' : '#7c3aed';
    bc.style.color             = isList ? 'var(--text-secondary)' : '#7c3aed';
    bc.style.fontWeight        = isList ? '600' : '700';
}

// ── Character counter ───────────────────────────────────────
function updateChar(el) {
    const len = el.value.length;
    const pill = document.getElementById('charPill');
    const sms  = Math.ceil(len / 160) || 0;
    pill.textContent = len + ' / 160' + (sms > 1 ? ' · ' + sms + ' SMS' : '');
    pill.style.background = len > 160 ? '#fee2e2' : '#f1f5f9';
    pill.style.color      = len > 160 ? '#dc2626' : '#64748b';
}
function updateChar2(el) {
    const len = el.value.length;
    const pill = document.getElementById('charPill2');
    const sms  = Math.ceil(len / 160) || 0;
    pill.textContent = len + ' / 160' + (sms > 1 ? ' · ' + sms + ' SMS' : '');
    pill.style.background = len > 160 ? '#fee2e2' : '#f1f5f9';
    pill.style.color      = len > 160 ? '#dc2626' : '#64748b';
}

// ── Templates ───────────────────────────────────────────────
function useTemplate(text) {
    document.getElementById('smsMessage').value = text;
    updateChar(document.getElementById('smsMessage'));
    document.getElementById('smsMessage').focus();
}
function useTemplate2(text) {
    const ta = document.querySelector('#modeCustom textarea[name=message]');
    if (ta) { ta.value = text; updateChar2(ta); ta.focus(); }
}

// ── Recipient tab switch ────────────────────────────────────
let currentRecipTab = 'customers';
function switchRecipTab(tab) {
    currentRecipTab = tab;
    document.getElementById('listCustomers').style.display = tab === 'customers' ? 'block' : 'none';
    document.getElementById('listSuppliers').style.display = tab === 'suppliers' ? 'block' : 'none';
    document.getElementById('rcTabCustomers').classList.toggle('active', tab === 'customers');
    document.getElementById('rcTabSuppliers').classList.toggle('active', tab === 'suppliers');
    filterRecip(document.getElementById('recipSearch').value);
    updateSelCount();
}

// ── Filter ──────────────────────────────────────────────────
function filterRecip(q) {
    q = q.toLowerCase().trim();
    const listId = currentRecipTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row').forEach(row => {
        const match = !q || row.dataset.name.includes(q) || (row.dataset.phone || '').includes(q);
        row.classList.toggle('hidden-row', !match);
    });
}

// ── Select helpers ──────────────────────────────────────────
function selectAllVisible() {
    const listId = currentRecipTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row:not(.hidden-row):not(.no-phone) .recip-cb').forEach(cb => cb.checked = true);
    updateSelCount();
}
function deselectAll() {
    document.querySelectorAll('.recip-cb').forEach(cb => cb.checked = false);
    updateSelCount();
}
function selectDueOnly() {
    deselectAll();
    const listId = currentRecipTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row.has-due:not(.no-phone) .recip-cb').forEach(cb => cb.checked = true);
    updateSelCount();
}
function updateSelCount() {
    document.getElementById('selCount').textContent = document.querySelectorAll('.recip-cb:checked').length;
}
document.addEventListener('change', e => { if (e.target.classList.contains('recip-cb')) updateSelCount(); });

// ── Confirm before send ─────────────────────────────────────
document.getElementById('smsListForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.recip-cb:checked').length;
    if (!count) { e.preventDefault(); alert('কমপক্ষে একজন প্রাপক নির্বাচন করুন।'); return; }
    if (!confirm(count + ' জনকে SMS পাঠাবেন?')) e.preventDefault();
});
</script>
@endpush
