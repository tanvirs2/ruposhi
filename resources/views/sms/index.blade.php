@extends('layouts.app')
@section('title', 'SMS পাঠান')
@section('page-title', 'SMS পাঠান')

@section('content')
@php
    $apiReady     = !empty($smsApiKey) && !empty($smsSenderId);
    $totalSent    = \App\Models\SmsLog::where('status','sent')->count();
    $totalFailed  = \App\Models\SmsLog::where('status','failed')->count();
    $totalToday   = \App\Models\SmsLog::whereDate('created_at', today())->where('status','sent')->count();
@endphp

{{-- ── Stats row ──────────────────────────────────────────────── --}}
<div class="sms-stats-row">
    <div class="sms-stat-card" style="--c:#3b82f6;--cl:#eff6ff">
        <div class="sms-stat-ico"><i class="fas fa-paper-plane"></i></div>
        <div>
            <div class="sms-stat-val">{{ number_format($totalSent) }}</div>
            <div class="sms-stat-lbl">মোট পাঠানো</div>
        </div>
    </div>
    <div class="sms-stat-card" style="--c:#22c55e;--cl:#f0fdf4">
        <div class="sms-stat-ico"><i class="fas fa-check-circle"></i></div>
        <div>
            <div class="sms-stat-val">{{ number_format($totalToday) }}</div>
            <div class="sms-stat-lbl">আজ সফল</div>
        </div>
    </div>
    <div class="sms-stat-card" style="--c:#ef4444;--cl:#fff1f2">
        <div class="sms-stat-ico"><i class="fas fa-times-circle"></i></div>
        <div>
            <div class="sms-stat-val">{{ number_format($totalFailed) }}</div>
            <div class="sms-stat-lbl">ব্যর্থ</div>
        </div>
    </div>
    <div class="sms-stat-card" style="--c:{{ $apiReady ? '#0d9488' : '#f59e0b' }};--cl:{{ $apiReady ? '#f0fdfa' : '#fffbeb' }};cursor:pointer"
         onclick="toggleApiPanel()">
        <div class="sms-stat-ico"><i class="fas fa-{{ $apiReady ? 'plug-circle-check' : 'plug-circle-exclamation' }}"></i></div>
        <div>
            <div class="sms-stat-val" style="font-size:.95rem">{{ $apiReady ? 'সংযুক্ত' : 'সেটআপ করুন' }}</div>
            <div class="sms-stat-lbl">API অবস্থা</div>
        </div>
        <i class="fas fa-chevron-right" style="margin-left:auto;color:var(--c);font-size:.72rem;opacity:.6"></i>
    </div>
</div>

{{-- ── API Setup panel — admin only ───────────────────────────── --}}
<div id="apiWrap" class="admin-only" style="display:none;margin-bottom:20px">
    <div class="card api-setup-card">
        <div class="api-setup-head">
            <span style="display:flex;align-items:center;gap:10px">
                <span class="api-ico-wrap {{ $apiReady ? 'ready' : 'warn' }}">
                    <i class="fas fa-{{ $apiReady ? 'plug-circle-check' : 'plug-circle-exclamation' }}"></i>
                </span>
                <span>
                    <div style="font-weight:700;font-size:.9rem">BulkSMSBD API সেটআপ</div>
                    <div style="font-size:.76rem;color:#94a3b8;margin-top:1px">SMS পাঠাতে API Key ও Sender ID দিন</div>
                </span>
            </span>
            <button type="button" onclick="toggleApiPanel()" class="api-close-btn" title="বন্ধ করুন">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('sms.settings') }}" class="api-setup-body">
            @csrf
            <div class="api-fields">
                <div class="api-field">
                    <label class="api-label">
                        <i class="fas fa-key" style="color:#f59e0b"></i> API Key
                        <a href="https://bulksmsbd.net" target="_blank" class="api-ext-link">
                            <i class="fas fa-external-link-alt"></i> bulksmsbd.net
                        </a>
                    </label>
                    <input type="text" name="sms_api_key" class="form-input"
                        value="{{ $smsApiKey }}"
                        placeholder="API Key পেস্ট করুন..."
                        style="font-family:monospace;letter-spacing:.03em">
                </div>
                <div class="api-field">
                    <label class="api-label">
                        <i class="fas fa-id-badge" style="color:#3b82f6"></i> Sender ID
                    </label>
                    <input type="text" name="sms_sender_id" class="form-input"
                        value="{{ $smsSenderId }}"
                        placeholder="যেমন: 8809611..."
                        style="font-family:monospace">
                </div>
            </div>
            <div class="api-footer">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> সংরক্ষণ করুন
                </button>
                <span class="api-enc-note">
                    <i class="fas fa-shield-halved"></i> Database-এ নিরাপদে সংরক্ষিত হবে
                </span>
            </div>
        </form>
    </div>
</div>

{{-- ── Main two-column grid ────────────────────────────────────── --}}
<div class="sms-main-grid">

{{-- ══ LEFT: Compose ══════════════════════════════════════════════ --}}
<div>
    <div class="card sms-compose-card">
        {{-- Tab switcher --}}
        <div class="sms-tabs">
            <button type="button" id="tabBtnList" class="sms-tab active" onclick="switchMode('list')">
                <i class="fas fa-users"></i> তালিকা থেকে
            </button>
            <button type="button" id="tabBtnCustom" class="sms-tab" onclick="switchMode('custom')">
                <i class="fas fa-keyboard"></i> কাস্টম নম্বর
            </button>
        </div>

        {{-- ── List mode ── --}}
        <div id="modeList" class="compose-body">
            <form method="POST" action="{{ route('sms.send') }}" id="smsListForm">
                @csrf
                {{-- Message box --}}
                <div class="msg-box">
                    <div class="msg-box-head">
                        <span><i class="fas fa-pencil"></i> বার্তা</span>
                        <span id="charPill" class="char-pill">০ / ১৬০</span>
                    </div>
                    <textarea name="message" id="smsMessage" rows="5" class="msg-textarea"
                        placeholder="এখানে বার্তা লিখুন..."
                        required maxlength="640"
                        oninput="updateChar(this)"></textarea>
                </div>

                {{-- Templates --}}
                <div class="tmpl-section">
                    <div class="tmpl-label">টেমপ্লেট</div>
                    <div class="tmpl-chips">
                        @foreach($templates as $t)
                        @if($t['text'])
                        <button type="button" class="tmpl-chip" onclick="useTemplate({{ json_encode($t['text']) }})">
                            {{ $t['label'] }}
                        </button>
                        @endif
                        @endforeach
                    </div>
                </div>

                {{-- Recipients --}}
                <div class="recip-box">
                    <div class="recip-head">
                        <div style="display:flex;gap:6px">
                            <button type="button" id="rcTabCustomers" class="recip-tab active" onclick="switchRecipTab('customers')">
                                <i class="fas fa-user"></i> কাস্টমার
                                <span class="rcnt">{{ $customers->count() }}</span>
                            </button>
                            <button type="button" id="rcTabSuppliers" class="recip-tab" onclick="switchRecipTab('suppliers')">
                                <i class="fas fa-truck"></i> সরবরাহকারী
                                <span class="rcnt">{{ $suppliers->count() }}</span>
                            </button>
                        </div>
                        <div class="search-box" style="max-width:150px;height:30px">
                            <i class="fas fa-search" style="font-size:.7rem"></i>
                            <input type="text" id="recipSearch" placeholder="খুঁজুন..."
                                oninput="filterRecip(this.value)"
                                style="font-size:.78rem">
                        </div>
                    </div>

                    <div class="recip-actions">
                        <button type="button" class="qbtn" onclick="selectAllVisible()">
                            <i class="fas fa-check-double"></i> সব
                        </button>
                        <button type="button" class="qbtn" onclick="deselectAll()">
                            <i class="fas fa-xmark"></i> বাতিল
                        </button>
                        <button type="button" class="qbtn qbtn-red" onclick="selectDueOnly()">
                            <i class="fas fa-exclamation-triangle"></i> বাকী আছে
                        </button>
                        <span class="sel-count-wrap">
                            <span id="selCount" class="sel-count-num">০</span> নির্বাচিত
                        </span>
                    </div>

                    {{-- Customer list --}}
                    <div id="listCustomers" class="recip-list">
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
                        <div class="recip-empty">কোনো কাস্টমার নেই</div>
                        @endforelse
                    </div>

                    {{-- Supplier list --}}
                    <div id="listSuppliers" class="recip-list" style="display:none">
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
                        <div class="recip-empty">কোনো সরবরাহকারী নেই</div>
                        @endforelse
                    </div>
                </div>

                <button type="submit" class="btn btn-primary sms-send-btn">
                    <i class="fas fa-paper-plane"></i>&nbsp; SMS পাঠান
                </button>
            </form>
        </div>

        {{-- ── Custom mode ── --}}
        <div id="modeCustom" class="compose-body" style="display:none">
            <form method="POST" action="{{ route('sms.send-custom') }}">
                @csrf
                <div style="margin-bottom:14px">
                    <label class="form-label">
                        <i class="fas fa-mobile-screen-button" style="color:#7c3aed"></i>
                        ফোন নম্বর
                        <span style="font-weight:400;color:#94a3b8;font-size:.76rem">(কমা বা নতুন লাইনে আলাদা করুন)</span>
                    </label>
                    <textarea name="numbers" class="form-input" rows="3"
                        placeholder="01711000000&#10;01811000000, 01911000000"
                        required style="resize:none;font-family:monospace;font-size:.86rem"></textarea>
                </div>

                <div class="msg-box">
                    <div class="msg-box-head">
                        <span><i class="fas fa-pencil"></i> বার্তা</span>
                        <span id="charPill2" class="char-pill">০ / ১৬০</span>
                    </div>
                    <textarea name="message" rows="5" class="msg-textarea"
                        placeholder="এখানে বার্তা লিখুন..."
                        required maxlength="640"
                        oninput="updateChar2(this)"></textarea>
                </div>

                <div class="tmpl-section">
                    <div class="tmpl-label">টেমপ্লেট</div>
                    <div class="tmpl-chips">
                        @foreach($templates as $t)
                        @if($t['text'])
                        <button type="button" class="tmpl-chip" onclick="useTemplate2({{ json_encode($t['text']) }})">{{ $t['label'] }}</button>
                        @endif
                        @endforeach
                    </div>
                </div>

                <button type="submit" class="btn sms-send-btn" style="background:#7c3aed;border-color:#7c3aed;color:#fff">
                    <i class="fas fa-paper-plane"></i>&nbsp; পাঠান
                </button>
            </form>
        </div>
    </div>
</div>

{{-- ══ RIGHT: Log ══════════════════════════════════════════════════ --}}
<div class="card" style="overflow:hidden">
    <div class="log-head">
        <span style="font-weight:700;font-size:.9rem;display:flex;align-items:center;gap:8px">
            <span style="width:28px;height:28px;border-radius:8px;background:#f1f5f9;display:inline-flex;align-items:center;justify-content:center;color:#64748b">
                <i class="fas fa-clock-rotate-left" style="font-size:.8rem"></i>
            </span>
            SMS ইতিহাস
        </span>
        <span class="log-count-badge">{{ $logs->total() }} টি</span>
    </div>

    @if($logs->isEmpty())
    <div class="log-empty">
        <i class="fas fa-comment-slash"></i>
        <div>এখনো কোনো SMS পাঠানো হয়নি</div>
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
                    <th class="tc" style="width:40px"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($logs as $log)
                <tr>
                    <td>
                        <div style="font-weight:600;font-size:.83rem">{{ $log->recipient_name ?? '—' }}</div>
                        <div class="mono" style="font-size:.74rem;color:#64748b">{{ $log->recipient }}</div>
                    </td>
                    <td>
                        <div class="log-msg" title="{{ $log->message }}">{{ $log->message }}</div>
                    </td>
                    <td class="tc">
                        @if($log->status === 'sent')
                            <span class="badge badge-green"><i class="fas fa-check"></i> সফল</span>
                        @elseif($log->status === 'failed')
                            <span class="badge badge-red" title="{{ $log->gateway_response }}">
                                <i class="fas fa-xmark"></i> ব্যর্থ
                            </span>
                        @else
                            <span class="badge" style="background:#f1f5f9;color:#64748b">অপেক্ষমান</span>
                        @endif
                    </td>
                    <td class="tc log-time">
                        {{ $log->created_at->format('d M') }}<br>
                        <span>{{ $log->created_at->format('h:ia') }}</span>
                    </td>
                    <td class="tc">
                        <form class="admin-only" method="POST" action="{{ route('sms.log.destroy', $log) }}"
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
/* ══ Stats row ══════════════════════════════════════════════════ */
.sms-stats-row {
    display: grid;
    grid-template-columns: repeat(4,1fr);
    gap: 14px;
    margin-bottom: 20px;
}
.sms-stat-card {
    background: var(--surface);
    border: 1.5px solid var(--border);
    border-left: 4px solid var(--c);
    border-radius: var(--radius);
    padding: 16px 18px;
    display: flex;
    align-items: center;
    gap: 14px;
    transition: box-shadow .15s, transform .15s;
}
.sms-stat-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.07); transform: translateY(-1px); }
.sms-stat-ico {
    width: 40px; height: 40px;
    border-radius: 10px;
    background: var(--cl);
    color: var(--c);
    display: flex; align-items: center; justify-content: center;
    font-size: 1rem;
    flex-shrink: 0;
}
.sms-stat-val { font-size: 1.3rem; font-weight: 800; color: var(--text-primary); line-height: 1.1; }
.sms-stat-lbl { font-size: .74rem; color: #94a3b8; margin-top: 2px; font-weight: 500; }

/* ══ API Setup card ══════════════════════════════════════════════ */
.api-setup-card { overflow: hidden; border-top: 3px solid #f59e0b; }
.api-setup-head {
    padding: 16px 20px;
    display: flex; align-items: center; justify-content: space-between;
    border-bottom: 1.5px solid var(--border);
    background: #fffbeb;
}
.api-ico-wrap {
    width: 36px; height: 36px;
    border-radius: 9px;
    display: inline-flex; align-items: center; justify-content: center;
    font-size: .9rem; flex-shrink: 0;
}
.api-ico-wrap.warn  { background: #fef3c7; color: #d97706; }
.api-ico-wrap.ready { background: #ccfbf1; color: #0d9488; }
.api-close-btn {
    width: 30px; height: 30px;
    border: none; background: transparent;
    border-radius: 6px; cursor: pointer;
    color: #94a3b8; font-size: .85rem;
    display: flex; align-items: center; justify-content: center;
    transition: background .12s, color .12s;
}
.api-close-btn:hover { background: #fee2e2; color: #dc2626; }
.api-setup-body { padding: 20px; }
.api-fields {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 16px;
}
.api-field {}
.api-label {
    display: flex; align-items: center; gap: 6px;
    font-size: .8rem; font-weight: 700;
    color: var(--text-secondary);
    margin-bottom: 6px;
}
.api-ext-link {
    font-size: .72rem; font-weight: 400;
    color: var(--accent); margin-left: auto;
    text-decoration: none;
}
.api-ext-link:hover { text-decoration: underline; }
.api-footer {
    display: flex; align-items: center; gap: 14px;
}
.api-enc-note {
    font-size: .76rem; color: #94a3b8;
    display: flex; align-items: center; gap: 5px;
}

/* ══ Main grid ════════════════════════════════════════════════════ */
.sms-main-grid {
    display: grid;
    grid-template-columns: 460px 1fr;
    gap: 20px;
    align-items: start;
}

/* ══ Compose card ═════════════════════════════════════════════════ */
.sms-compose-card { overflow: hidden; }
.sms-tabs {
    display: flex;
    border-bottom: 1.5px solid var(--border);
    background: var(--bg);
}
.sms-tab {
    flex: 1;
    padding: 13px 16px;
    font-size: .84rem; font-weight: 600;
    border: none; cursor: pointer;
    border-bottom: 3px solid transparent;
    color: var(--text-secondary);
    background: transparent;
    transition: .15s;
    font-family: inherit;
    display: flex; align-items: center; justify-content: center; gap: 7px;
}
.sms-tab:hover { background: var(--surface); color: var(--text-primary); }
.sms-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: var(--surface);
    font-weight: 700;
}
.compose-body { padding: 18px 20px; }

/* ══ Message box ══════════════════════════════════════════════════ */
.msg-box {
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
    margin-bottom: 14px;
    transition: border-color .15s;
}
.msg-box:focus-within { border-color: var(--accent); }
.msg-box-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 8px 12px;
    background: var(--bg);
    border-bottom: 1.5px solid var(--border);
    font-size: .78rem; font-weight: 700; color: var(--text-secondary);
}
.char-pill {
    font-size: .7rem; font-weight: 700;
    padding: 2px 9px;
    border-radius: 999px;
    background: #f1f5f9;
    color: #64748b;
    transition: background .15s, color .15s;
}
.msg-textarea {
    display: block; width: 100%; box-sizing: border-box;
    border: none; outline: none;
    padding: 12px;
    font-family: inherit; font-size: .87rem;
    color: var(--text-primary);
    background: var(--surface);
    resize: none;
    line-height: 1.65;
}

/* ══ Templates ════════════════════════════════════════════════════ */
.tmpl-section { margin-bottom: 14px; }
.tmpl-label {
    font-size: .72rem; font-weight: 700;
    color: #94a3b8;
    text-transform: uppercase; letter-spacing: .08em;
    margin-bottom: 7px;
}
.tmpl-chips { display: flex; flex-wrap: wrap; gap: 5px; }
.tmpl-chip {
    display: inline-flex; align-items: center;
    padding: 4px 11px;
    font-size: .74rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: 999px;
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    transition: .12s;
    font-family: inherit;
}
.tmpl-chip:hover { border-color: var(--accent); color: var(--accent); background: #f0fdfa; }

/* ══ Recipient box ════════════════════════════════════════════════ */
.recip-box {
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    overflow: hidden;
    margin-bottom: 14px;
}
.recip-head {
    display: flex; align-items: center; justify-content: space-between;
    gap: 8px;
    padding: 9px 12px;
    background: var(--bg);
    border-bottom: 1.5px solid var(--border);
}
.recip-tab {
    display: inline-flex; align-items: center; gap: 5px;
    padding: 4px 11px;
    font-size: .77rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: var(--radius-sm);
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer;
    transition: .12s;
    font-family: inherit;
}
.recip-tab.active { background: var(--accent); color: #fff; border-color: var(--accent); }
.rcnt {
    min-width: 18px; height: 18px;
    font-size: .67rem; font-weight: 700;
    background: rgba(255,255,255,.25);
    border-radius: 999px; padding: 0 4px;
    display: inline-flex; align-items: center; justify-content: center;
}
.recip-tab:not(.active) .rcnt { background: var(--bg); color: var(--text-secondary); }
.recip-actions {
    display: flex; align-items: center; gap: 6px;
    padding: 7px 12px;
    border-bottom: 1px solid var(--border);
    background: var(--surface);
}
.sel-count-wrap { margin-left: auto; font-size: .74rem; color: var(--text-secondary); }
.sel-count-num { font-weight: 800; color: var(--accent); }
.qbtn {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 3px 9px;
    font-size: .73rem; font-weight: 600;
    border: 1.5px solid var(--border);
    border-radius: 6px;
    background: var(--surface);
    color: var(--text-secondary);
    cursor: pointer; font-family: inherit;
    transition: .12s;
}
.qbtn:hover { border-color: var(--accent); color: var(--accent); }
.qbtn-red:hover { border-color: #dc2626; color: #dc2626; }
.recip-list { max-height: 220px; overflow-y: auto; }
.recip-row {
    display: flex; align-items: center; gap: 10px;
    padding: 8px 12px;
    border-bottom: 1px solid var(--border);
    cursor: pointer;
    transition: background .1s;
    user-select: none;
}
.recip-row:last-child { border-bottom: none; }
.recip-row:hover:not(.no-phone) { background: var(--bg); }
.recip-row.no-phone { opacity: .4; cursor: not-allowed; }
.recip-row.has-due { background: #fff9f9; }
.recip-row.has-due:hover:not(.no-phone) { background: #fff0f0; }
.recip-row.hidden-row { display: none !important; }
.recip-row input[type=checkbox] { width: 15px; height: 15px; accent-color: var(--accent); flex-shrink: 0; cursor: pointer; }
.ri { flex: 1; min-width: 0; }
.ri-name { display: block; font-size: .82rem; font-weight: 600; color: var(--text-primary); }
.ri-phone { display: block; font-size: .74rem; color: #64748b; font-family: monospace; }
.due-pill {
    font-size: .7rem; font-weight: 700;
    color: #dc2626; background: #fee2e2;
    padding: 2px 7px; border-radius: 999px; flex-shrink: 0;
}
.recip-empty { padding: 20px; text-align: center; color: #94a3b8; font-size: .82rem; }

/* ══ Send button ══════════════════════════════════════════════════ */
.sms-send-btn { width: 100%; height: 42px; font-size: .9rem; font-weight: 700; }

/* ══ Log panel ════════════════════════════════════════════════════ */
.log-head {
    padding: 14px 20px;
    border-bottom: 1.5px solid var(--border);
    display: flex; align-items: center; justify-content: space-between;
    background: var(--bg);
}
.log-count-badge {
    font-size: .74rem; font-weight: 700;
    padding: 3px 10px;
    background: var(--border);
    color: var(--text-secondary);
    border-radius: 999px;
}
.log-empty {
    padding: 52px 20px; text-align: center;
    color: #94a3b8; font-size: .88rem;
}
.log-empty i { font-size: 2rem; color: #cbd5e1; display: block; margin-bottom: 10px; }
.log-msg {
    font-size: .78rem; color: var(--text-secondary);
    max-width: 220px; overflow: hidden;
    text-overflow: ellipsis; white-space: nowrap;
}
.log-time { font-size: .74rem; color: #94a3b8; white-space: nowrap; }
.log-time span { color: #cbd5e1; }

@media (max-width: 900px) {
    .sms-main-grid { grid-template-columns: 1fr; }
    .sms-stats-row { grid-template-columns: repeat(2,1fr); }
    .api-fields { grid-template-columns: 1fr; }
}
</style>
@endpush

@push('scripts')
<script>
// ── API Panel ───────────────────────────────────────────────────
function toggleApiPanel() {
    const w = document.getElementById('apiWrap');
    w.style.display = w.style.display === 'none' ? 'block' : 'none';
}

// ── Mode tabs ───────────────────────────────────────────────────
function switchMode(mode) {
    const isList = mode === 'list';
    document.getElementById('modeList').style.display   = isList ? 'block' : 'none';
    document.getElementById('modeCustom').style.display = isList ? 'none'  : 'block';
    document.getElementById('tabBtnList').classList.toggle('active', isList);
    document.getElementById('tabBtnCustom').classList.toggle('active', !isList);
}

// ── Char counter ────────────────────────────────────────────────
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

// ── Templates ───────────────────────────────────────────────────
function useTemplate(text) {
    const ta = document.getElementById('smsMessage');
    ta.value = text; updateChar(ta); ta.focus();
}
function useTemplate2(text) {
    const ta = document.querySelector('#modeCustom textarea[name=message]');
    if (ta) { ta.value = text; updateChar2(ta); ta.focus(); }
}

// ── Recipient tab switch ────────────────────────────────────────
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

// ── Filter ──────────────────────────────────────────────────────
function filterRecip(q) {
    q = q.toLowerCase().trim();
    const listId = currentRecipTab === 'customers' ? 'listCustomers' : 'listSuppliers';
    document.querySelectorAll('#' + listId + ' .recip-row').forEach(row => {
        const match = !q || row.dataset.name.includes(q) || (row.dataset.phone || '').includes(q);
        row.classList.toggle('hidden-row', !match);
    });
}

// ── Selection helpers ───────────────────────────────────────────
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

// ── Confirm before send ─────────────────────────────────────────
document.getElementById('smsListForm').addEventListener('submit', function(e) {
    const count = document.querySelectorAll('.recip-cb:checked').length;
    if (!count) { e.preventDefault(); alert('কমপক্ষে একজন প্রাপক নির্বাচন করুন।'); return; }
    if (!confirm(count + ' জনকে SMS পাঠাবেন?')) e.preventDefault();
});
</script>
@endpush
