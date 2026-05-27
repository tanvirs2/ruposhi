@extends('layouts.app')
@section('title', 'স্টোর কনফিগ')
@section('page-title', 'স্টোর কনফিগারেশন')

@section('content')

{{-- ── Tabs ─────────────────────────────────────────────────── --}}
<div class="config-tabs">
    <button class="config-tab active" onclick="switchTab('store', this)">
        <i class="fas fa-store"></i> স্টোর তথ্য
    </button>
    <button class="config-tab" onclick="switchTab('payment', this)">
        <i class="fas fa-credit-card"></i> পরিশোধ মোড
        <span class="tab-count">{{ count($methods) }}</span>
    </button>
    <button class="config-tab" onclick="switchTab('multimedia', this)">
        <i class="fas fa-photo-film"></i> মাল্টিমিডিয়া
        @if($multimediaEnabled)<span class="tab-count" style="background:#16a34a">চালু</span>@endif
    </button>
</div>

{{-- ══ TAB 1: Store info ══════════════════════════════════════ --}}
<div id="tab-store" class="tab-panel">
    <div class="form-card">
        <form method="POST" action="{{ route('store-config.update') }}">
            @csrf @method('PUT')
            <div class="form-grid">
                <div class="form-group-field">
                    <label>স্টোরের নাম <span class="req">*</span></label>
                    <input type="text" name="store_name" value="{{ $config['store_name'] ?? '' }}" required placeholder="মেসার্স রূপসী বাংলা ট্রেডার্স">
                </div>
                <div class="form-group-field">
                    <label>মালিকের নাম (প্রোপ্রাইটর)</label>
                    <input type="text" name="store_owner" value="{{ $config['store_owner'] ?? '' }}" placeholder="মোঃ ফারুক হোসাইন">
                </div>
                <div class="form-group-field form-full">
                    <label>ব্যবসার বিবরণ (ট্যাগলাইন)</label>
                    <input type="text" name="store_tagline" value="{{ $config['store_tagline'] ?? '' }}" placeholder="পাইকারী চাউল বিক্রেতা ও কমিশন এজেন্ট">
                </div>
                <div class="form-group-field">
                    <label>ফোন নম্বর ১</label>
                    <input type="text" name="store_phone" value="{{ $config['store_phone'] ?? '' }}" placeholder="01942-796401">
                </div>
                <div class="form-group-field">
                    <label>ফোন নম্বর ২</label>
                    <input type="text" name="store_phone2" value="{{ $config['store_phone2'] ?? '' }}" placeholder="01925-507321">
                </div>
                <div class="form-group-field">
                    <label>মুদ্রা চিহ্ন</label>
                    <input type="text" name="currency" value="{{ $config['currency'] ?? '৳' }}">
                </div>
                <div class="form-group-field form-full">
                    <label>ঠিকানা</label>
                    <textarea name="store_address" rows="2" placeholder="কাচারী রোড, টঙ্গী বাজার, গাজীপুর">{{ $config['store_address'] ?? '' }}</textarea>
                </div>
            </div>

            {{-- Preview --}}
            <div style="margin:20px 0;padding:16px;background:#f8fafc;border:1px dashed #cbd5e1;border-radius:8px;text-align:center;font-family:'Hind Siliguri',sans-serif">
                <div style="font-size:.75rem;color:#94a3b8;margin-bottom:2px">ক্যাশ মেমো</div>
                @include('partials.store-name-arc', ['name' => $config['store_name'] ?? 'আমার দোকান', 'size' => 30])
                @if(!empty($config['store_owner']))<div style="font-size:.85rem">প্রোঃ {{ $config['store_owner'] }}</div>@endif
                @if(!empty($config['store_tagline']))<div style="font-size:.8rem;color:#555">{{ $config['store_tagline'] }}</div>@endif
                <div style="font-size:.8rem;color:#777">{{ $config['store_address'] ?? '' }}</div>
                <div style="font-size:.85rem;font-weight:600;margin-top:4px">
                    {{ $config['store_phone'] ?? '' }}
                    @if(!empty($config['store_phone2'])) &nbsp;|&nbsp; {{ $config['store_phone2'] }} @endif
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> সংরক্ষণ করুন</button>
            </div>
        </form>
    </div>
</div>

{{-- ══ TAB 2: Payment methods ══════════════════════════════════ --}}
<div id="tab-payment" class="tab-panel" style="display:none">
    <div class="form-card">

        {{-- Add new method form --}}
        <div style="padding:20px 20px 16px;border-bottom:1px solid var(--border)">
            <div style="font-size:.82rem;font-weight:700;color:var(--text-secondary);
                text-transform:uppercase;letter-spacing:.06em;margin-bottom:12px">
                <i class="fas fa-plus-circle" style="color:var(--accent)"></i> নতুন পদ্ধতি যোগ করুন
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <input type="text" id="newPayName"
                    placeholder="পদ্ধতির নাম  যেমন: IDLC কিস্তি"
                    style="flex:2;min-width:180px"
                    onkeydown="if(event.key==='Enter'){event.preventDefault();addPayMethod();}">
                <div style="flex:1;min-width:160px;position:relative">
                    <input type="text" id="newPayGroup" list="groupOptions"
                        placeholder="গ্রুপ  যেমন: ব্যাংক ট্রান্সফার"
                        style="width:100%">
                    <datalist id="groupOptions">
                        @foreach($groups as $g)
                        <option value="{{ $g }}">
                        @endforeach
                    </datalist>
                </div>
                <button type="button" onclick="addPayMethod()" class="btn btn-primary" style="white-space:nowrap">
                    <i class="fas fa-plus"></i> যোগ করুন
                </button>
            </div>
            <p style="font-size:.77rem;color:#94a3b8;margin-top:8px">
                গ্রুপ ক্ষেত্রে বিদ্যমান গ্রুপ নির্বাচন করুন অথবা নতুন গ্রুপের নাম লিখুন।
            </p>
        </div>

        {{-- Methods list grouped --}}
        <div style="padding:20px" id="payMethodsContainer">
            @php $grouped = collect($methods)->groupBy('group'); @endphp
            @foreach($grouped as $group => $items)
            <div class="pay-group-block" data-group="{{ $group }}">
                <div class="pay-group-label">{{ $group }}</div>
                <div class="pay-group-rows">
                    @foreach($items as $m)
                    <div class="pay-method-row" data-name="{{ $m['name'] }}">
                        <span class="pay-method-name">
                            <i class="fas fa-credit-card" style="color:#cbd5e1;margin-right:8px;font-size:.8rem"></i>
                            {{ $m['name'] }}
                        </span>
                        <button type="button"
                            onclick="deletePayMethod('{{ addslashes($m['name']) }}')"
                            class="btn" style="padding:4px 12px;background:#fee2e2;color:#dc2626;
                            border:1px solid #fecaca;font-size:.78rem">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endforeach

            @if(count($methods) === 0)
            <div id="payMethodEmpty" style="text-align:center;padding:32px;
                color:var(--text-secondary);font-size:.88rem;
                background:var(--bg);border:1px dashed var(--border);border-radius:10px">
                <i class="fas fa-inbox" style="font-size:1.8rem;display:block;margin-bottom:10px;opacity:.3"></i>
                কোনো পদ্ধতি নেই
            </div>
            @endif
        </div>

    </div>
</div>

{{-- ══ TAB 3: Multimedia ══════════════════════════════════════ --}}
<div id="tab-multimedia" class="tab-panel" style="display:none">
    <div class="form-card">

        {{-- Enable toggle + interval ─────────────────── --}}
        <div class="mm-header-row">
            <div>
                <div class="mm-section-title"><i class="fas fa-photo-film"></i> মাল্টিমিডিয়া স্লাইডশো</div>
                <div class="mm-section-sub">ছবি ও ভিডিও আপলোড করুন — পপআপ স্ক্রিনে স্বয়ংক্রিয়ভাবে প্রদর্শিত হবে</div>
            </div>
            <label class="mm-toggle-wrap">
                <span class="mm-toggle-label">{{ $multimediaEnabled ? 'চালু' : 'বন্ধ' }}</span>
                <div class="mm-toggle {{ $multimediaEnabled ? 'on' : '' }}" id="mmToggle" onclick="toggleMM()">
                    <div class="mm-toggle-knob"></div>
                </div>
            </label>
        </div>

        {{-- Interval setting ─────────────────────────── --}}
        <div class="mm-interval-row">
            <label class="mm-interval-label">
                <i class="fas fa-clock"></i> স্লাইড পরিবর্তন সময় (সেকেন্ড)
            </label>
            <div style="display:flex;align-items:center;gap:10px">
                <input type="number" id="mmInterval" value="{{ $multimediaInterval }}"
                    min="1" max="120" class="mm-interval-input"
                    onchange="updateInterval(this.value)">
                <span style="font-size:.82rem;color:#64748b">সেকেন্ড</span>
            </div>
        </div>

        {{-- Upload area ───────────────────────────────── --}}
        <div class="mm-upload-area" id="mmUploadArea"
            onclick="document.getElementById('mmFileInput').click()"
            ondragover="event.preventDefault();this.classList.add('drag-over')"
            ondragleave="this.classList.remove('drag-over')"
            ondrop="handleDrop(event)">
            <input type="file" id="mmFileInput" multiple
                accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/webm,audio/mpeg,audio/wav,audio/aac,audio/ogg,audio/mp4"
                style="display:none" onchange="uploadFiles(this.files)">
            <i class="fas fa-cloud-arrow-up mm-upload-icon"></i>
            <div class="mm-upload-text">ছবি বা ভিডিও এখানে টেনে আনুন</div>
            <div class="mm-upload-sub">অথবা ক্লিক করুন — JPG, PNG, GIF, WebP, MP4, WebM, MP3, WAV, AAC (সর্বোচ্চ 50MB)</div>
            <div id="mmUploadProgress" style="display:none;margin-top:12px">
                <div class="mm-progress-bar"><div class="mm-progress-fill" id="mmProgressFill"></div></div>
                <div id="mmProgressText" style="font-size:.78rem;color:#64748b;margin-top:4px;text-align:center">আপলোড হচ্ছে...</div>
            </div>
        </div>

        {{-- Files grid ────────────────────────────────── --}}
        <div class="mm-files-grid" id="mmFilesGrid">
            @forelse($multimediaFiles as $f)
            <div class="mm-file-card" id="mmCard_{{ $f['filename'] }}" data-type="{{ $f['type'] }}">
                @if($f['type'] === 'video')
                    <video class="mm-file-thumb" muted>
                        <source src="{{ $f['url'] }}" type="video/mp4">
                    </video>
                    <div class="mm-file-type-badge video"><i class="fas fa-play"></i> ভিডিও</div>
                @elseif($f['type'] === 'audio')
                    <div class="mm-audio-thumb">
                        <i class="fas fa-music mm-audio-icon"></i>
                        <div class="mm-audio-bars">
                            <span></span><span></span><span></span><span></span><span></span>
                        </div>
                    </div>
                    <div class="mm-file-type-badge audio"><i class="fas fa-music"></i> অডিও</div>
                @else
                    <img class="mm-file-thumb" src="{{ $f['url'] }}" alt="{{ $f['filename'] }}" loading="lazy">
                    <div class="mm-file-type-badge image"><i class="fas fa-image"></i> ছবি</div>
                @endif
                <div class="mm-file-name">{{ Str::limit($f['filename'], 20) }}</div>
                <button type="button" class="mm-file-delete" onclick="deleteMMFile('{{ $f['filename'] }}')" title="মুছুন">
                    <i class="fas fa-trash"></i>
                </button>
                <button type="button" class="mm-file-preview" onclick="previewFile('{{ $f['url'] }}','{{ $f['type'] }}')" title="প্রিভিউ">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            @empty
            <div id="mmEmpty" class="mm-empty">
                <i class="fas fa-photo-film" style="font-size:2rem;opacity:.2;display:block;margin-bottom:10px"></i>
                কোনো ফাইল আপলোড হয়নি
            </div>
            @endforelse
        </div>

    </div>
</div>

@push('styles')
<style>
/* ── Tabs ─────────────────────────────────────────── */
.config-tabs {
    display: flex;
    gap: 4px;
    margin-bottom: 16px;
    border-bottom: 2px solid var(--border);
}
.config-tab {
    padding: 10px 22px;
    border: none;
    background: none;
    font-family: inherit;
    font-size: .9rem;
    font-weight: 600;
    color: var(--text-secondary);
    cursor: pointer;
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    border-radius: 6px 6px 0 0;
    display: flex;
    align-items: center;
    gap: 7px;
    transition: color .15s, border-color .15s;
}
.config-tab:hover { color: var(--text); }
.config-tab.active {
    color: var(--accent);
    border-bottom-color: var(--accent);
    background: var(--accent-light);
}
.tab-count {
    background: var(--accent);
    color: #fff;
    font-size: .7rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 20px;
    min-width: 20px;
    text-align: center;
}
.config-tab.active .tab-count { background: var(--accent); }
.config-tab:not(.active) .tab-count { background: #94a3b8; }

/* ── Payment method list ──────────────────────────── */
.pay-group-block { margin-bottom: 20px; }
.pay-group-label {
    font-size: .75rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: var(--text-secondary);
    padding: 4px 0 8px;
    border-bottom: 1px solid var(--border);
    margin-bottom: 6px;
    display: flex;
    align-items: center;
    gap: 6px;
}
.pay-group-label::before {
    content: '';
    display: inline-block;
    width: 3px;
    height: 12px;
    background: var(--accent);
    border-radius: 2px;
}
.pay-method-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 12px;
    border-radius: 7px;
    margin-bottom: 4px;
    border: 1px solid var(--border);
    background: var(--surface);
    transition: background .1s;
}
.pay-method-row:hover { background: var(--bg); }
.pay-method-name {
    font-size: .88rem;
    font-weight: 500;
    color: var(--text);
}

/* ── Multimedia tab ───────────────────────────────── */
.mm-header-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 20px 16px;
    border-bottom: 1px solid var(--border);
    gap: 16px;
    flex-wrap: wrap;
}
.mm-section-title { font-size: 1rem; font-weight: 700; display:flex; align-items:center; gap:8px; }
.mm-section-sub   { font-size: .8rem; color: #64748b; margin-top: 4px; }

/* Toggle switch */
.mm-toggle-wrap  { display:flex; align-items:center; gap:10px; cursor:pointer; }
.mm-toggle-label { font-size: .82rem; font-weight: 700; min-width: 32px; }
.mm-toggle {
    width: 52px; height: 28px;
    background: #cbd5e1;
    border-radius: 14px;
    position: relative;
    transition: background .2s;
    cursor: pointer;
}
.mm-toggle.on    { background: #16a34a; }
.mm-toggle-knob  {
    position: absolute;
    top: 3px; left: 3px;
    width: 22px; height: 22px;
    background: #fff;
    border-radius: 50%;
    transition: transform .2s;
    box-shadow: 0 1px 4px rgba(0,0,0,.2);
}
.mm-toggle.on .mm-toggle-knob { transform: translateX(24px); }

/* Interval */
.mm-interval-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 14px 20px;
    border-bottom: 1px solid var(--border);
    gap: 12px;
    flex-wrap: wrap;
}
.mm-interval-label { font-size: .85rem; font-weight: 600; color: #475569; display:flex; align-items:center; gap:8px; }
.mm-interval-input {
    width: 80px;
    padding: 7px 12px;
    border: 1.5px solid var(--border);
    border-radius: 8px;
    font-size: .9rem;
    font-family: inherit;
    text-align: center;
}
.mm-interval-input:focus { outline: none; border-color: var(--accent); }

/* Upload area */
.mm-upload-area {
    margin: 20px 20px 0;
    border: 2px dashed #cbd5e1;
    border-radius: 12px;
    padding: 32px 20px;
    text-align: center;
    cursor: pointer;
    transition: all .2s;
    background: #fafafa;
}
.mm-upload-area:hover, .mm-upload-area.drag-over {
    border-color: var(--accent);
    background: var(--accent-light);
}
.mm-upload-icon { font-size: 2.2rem; color: #94a3b8; margin-bottom: 10px; display: block; }
.mm-upload-area:hover .mm-upload-icon,
.mm-upload-area.drag-over .mm-upload-icon { color: var(--accent); }
.mm-upload-text { font-weight: 700; color: #374151; margin-bottom: 6px; }
.mm-upload-sub  { font-size: .78rem; color: #94a3b8; }

/* Progress bar */
.mm-progress-bar  { height: 6px; background: #e2e8f0; border-radius: 3px; overflow: hidden; }
.mm-progress-fill { height: 100%; width: 0; background: var(--accent); border-radius: 3px; transition: width .3s; }

/* Files grid */
.mm-files-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
    gap: 12px;
    padding: 20px;
}
.mm-empty {
    grid-column: 1/-1;
    text-align: center;
    padding: 40px;
    color: #94a3b8;
    font-size: .88rem;
}
.mm-file-card {
    border: 1px solid var(--border);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    background: #fff;
    transition: box-shadow .15s;
}
.mm-file-card:hover { box-shadow: 0 4px 16px rgba(0,0,0,.1); }
.mm-file-thumb {
    width: 100%; height: 100px;
    object-fit: cover;
    display: block;
    background: #f1f5f9;
}
.mm-file-type-badge {
    position: absolute; top: 6px; left: 6px;
    padding: 2px 8px; border-radius: 20px;
    font-size: .68rem; font-weight: 700;
}
.mm-file-type-badge.image { background: #eff6ff; color: #1e40af; }
.mm-file-type-badge.video { background: #faf5ff; color: #7e22ce; }
.mm-file-type-badge.audio { background: #fff7ed; color: #c2410c; }

/* Audio thumbnail */
.mm-audio-thumb {
    width: 100%; height: 100px;
    background: linear-gradient(135deg, #1e1b4b 0%, #312e81 100%);
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 8px;
}
.mm-audio-icon { font-size: 1.8rem; color: #a5b4fc; }
.mm-audio-bars { display: flex; align-items: flex-end; gap: 3px; height: 20px; }
.mm-audio-bars span {
    display: block; width: 4px; border-radius: 2px;
    background: #818cf8; animation: mmBar 1s ease-in-out infinite;
}
.mm-audio-bars span:nth-child(1) { height: 8px;  animation-delay: 0s; }
.mm-audio-bars span:nth-child(2) { height: 14px; animation-delay: .15s; }
.mm-audio-bars span:nth-child(3) { height: 20px; animation-delay: .3s; }
.mm-audio-bars span:nth-child(4) { height: 14px; animation-delay: .45s; }
.mm-audio-bars span:nth-child(5) { height: 8px;  animation-delay: .6s; }
@keyframes mmBar {
    0%,100% { transform: scaleY(.4); }
    50%      { transform: scaleY(1); }
}
.mm-file-name {
    padding: 6px 8px;
    font-size: .72rem;
    color: #64748b;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mm-file-delete, .mm-file-preview {
    position: absolute;
    width: 28px; height: 28px;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    font-size: .75rem;
    display: flex; align-items: center; justify-content: center;
    opacity: 0;
    transition: opacity .15s;
}
.mm-file-card:hover .mm-file-delete,
.mm-file-card:hover .mm-file-preview { opacity: 1; }
.mm-file-delete  { top: 6px; right: 6px; background: #fee2e2; color: #dc2626; }
.mm-file-preview { top: 36px; right: 6px; background: #eff6ff; color: #1d4ed8; }
</style>
@endpush

@push('scripts')
<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-panel').forEach(p => p.style.display = 'none');
    document.querySelectorAll('.config-tab').forEach(b => b.classList.remove('active'));
    document.getElementById('tab-' + name).style.display = 'block';
    btn.classList.add('active');
}

const payAddUrl    = '{{ route("store-config.payment-method.add") }}';
const payDeleteUrl = '{{ route("store-config.payment-method.delete") }}';
const csrfToken    = '{{ csrf_token() }}';

async function addPayMethod() {
    const nameEl  = document.getElementById('newPayName');
    const groupEl = document.getElementById('newPayGroup');
    const name    = nameEl.value.trim();
    const group   = groupEl.value.trim();

    if (!name)  { nameEl.focus();  showToast('পদ্ধতির নাম লিখুন', 'warn'); return; }
    if (!group) { groupEl.focus(); showToast('গ্রুপের নাম লিখুন', 'warn'); return; }

    const res  = await fetch(payAddUrl, {
        method:  'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body:    JSON.stringify({ name, group })
    });
    const data = await res.json();
    if (data.success) {
        nameEl.value  = '';
        groupEl.value = '';
        nameEl.focus();
        rebuildList(data.methods);
        updateTabCount(data.methods.length);
        showToast('✓ "' + name + '" যোগ হয়েছে', 'success');
    }
}

async function deletePayMethod(name) {
    if (!confirm(`"${name}" মুছে ফেলবেন?`)) return;
    const res  = await fetch(payDeleteUrl, {
        method:  'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body:    JSON.stringify({ name })
    });
    const data = await res.json();
    if (data.success) {
        rebuildList(data.methods);
        updateTabCount(data.methods.length);
        showToast('"' + name + '" মুছে ফেলা হয়েছে', 'warn');
    }
}

function rebuildList(methods) {
    const container = document.getElementById('payMethodsContainer');

    // Group by group field
    const grouped = {};
    methods.forEach(m => {
        if (!grouped[m.group]) grouped[m.group] = [];
        grouped[m.group].push(m.name);
    });

    if (!methods.length) {
        container.innerHTML = `<div style="text-align:center;padding:32px;
            color:var(--text-secondary);font-size:.88rem;
            background:var(--bg);border:1px dashed var(--border);border-radius:10px">
            <i class="fas fa-inbox" style="font-size:1.8rem;display:block;margin-bottom:10px;opacity:.3"></i>
            কোনো পদ্ধতি নেই
        </div>`;
        return;
    }

    container.innerHTML = Object.entries(grouped).map(([group, names]) => `
        <div class="pay-group-block" data-group="${group}">
            <div class="pay-group-label">${group}</div>
            <div class="pay-group-rows">
                ${names.map(n => `
                <div class="pay-method-row" data-name="${n}">
                    <span class="pay-method-name">
                        <i class="fas fa-credit-card" style="color:#cbd5e1;margin-right:8px;font-size:.8rem"></i>
                        ${n}
                    </span>
                    <button type="button"
                        onclick="deletePayMethod('${n.replace(/\\/g,'\\\\').replace(/'/g,"\\'")}')"
                        class="btn" style="padding:4px 12px;background:#fee2e2;color:#dc2626;
                        border:1px solid #fecaca;font-size:.78rem">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>`).join('')}
            </div>
        </div>
    `).join('');

    // Refresh datalist groups
    const dl = document.getElementById('groupOptions');
    const existing = new Set(methods.map(m => m.group));
    dl.innerHTML = [...existing].map(g => `<option value="${g}">`).join('');
}

function updateTabCount(n) {
    const badge = document.querySelector('.config-tab:nth-child(2) .tab-count');
    if (badge) badge.textContent = n;
}

/* ══ Multimedia JS ══════════════════════════════════════════ */
const mmToggleUrl   = '{{ route("store-config.multimedia.toggle") }}';
const mmIntervalUrl = '{{ route("store-config.multimedia.interval") }}';
const mmUploadUrl   = '{{ route("store-config.multimedia.upload") }}';
const mmDeleteUrl   = '{{ route("store-config.multimedia.delete") }}';
let   mmEnabled     = {{ $multimediaEnabled ? 'true' : 'false' }};

async function toggleMM() {
    mmEnabled = !mmEnabled;
    const toggle = document.getElementById('mmToggle');
    const label  = toggle.previousElementSibling;
    toggle.classList.toggle('on', mmEnabled);
    label.textContent = mmEnabled ? 'চালু' : 'বন্ধ';

    // Update tab badge
    const tabBadge = document.querySelector('.config-tab:nth-child(3) .tab-count');
    if (mmEnabled) {
        if (!tabBadge) {
            const btn = document.querySelector('.config-tab:nth-child(3)');
            const s = document.createElement('span');
            s.className = 'tab-count'; s.style.background = '#16a34a'; s.textContent = 'চালু';
            btn.appendChild(s);
        }
    } else if (tabBadge) { tabBadge.remove(); }

    await fetch(mmToggleUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ enabled: mmEnabled })
    });
    showToast(mmEnabled ? '✓ মাল্টিমিডিয়া চালু হয়েছে' : 'মাল্টিমিডিয়া বন্ধ করা হয়েছে', mmEnabled ? 'success' : 'warn');
}

let intervalTimer;
function updateInterval(val) {
    clearTimeout(intervalTimer);
    intervalTimer = setTimeout(async () => {
        await fetch(mmIntervalUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
            body: JSON.stringify({ interval: parseInt(val) })
        });
        showToast('✓ সময় আপডেট হয়েছে', 'success');
    }, 600);
}

function handleDrop(e) {
    e.preventDefault();
    document.getElementById('mmUploadArea').classList.remove('drag-over');
    uploadFiles(e.dataTransfer.files);
}

async function uploadFiles(files) {
    for (const file of files) {
        await uploadSingleFile(file);
    }
}

async function uploadSingleFile(file) {
    const progress  = document.getElementById('mmUploadProgress');
    const fill      = document.getElementById('mmProgressFill');
    const text      = document.getElementById('mmProgressText');
    progress.style.display = 'block';
    fill.style.width = '10%';
    text.textContent = file.name + ' আপলোড হচ্ছে...';

    const fd = new FormData();
    fd.append('file', file);
    fd.append('_token', csrfToken);

    try {
        // Simulate progress
        let pct = 10;
        const timer = setInterval(() => { pct = Math.min(pct + 15, 85); fill.style.width = pct + '%'; }, 200);

        const res  = await fetch(mmUploadUrl, { method: 'POST', body: fd });
        const data = await res.json();
        clearInterval(timer);

        if (data.success) {
            fill.style.width = '100%';
            text.textContent = '✓ আপলোড সম্পন্ন';
            addFileCard(data);
            showToast('✓ ' + file.name + ' আপলোড হয়েছে', 'success');
        } else {
            showToast('ফাইল আপলোড ব্যর্থ হয়েছে', 'warn');
        }
    } catch (e) {
        showToast('ত্রুটি: ' + e.message, 'warn');
    }

    setTimeout(() => { progress.style.display = 'none'; fill.style.width = '0'; }, 1500);
}

function addFileCard(data) {
    const empty = document.getElementById('mmEmpty');
    if (empty) empty.remove();

    const grid = document.getElementById('mmFilesGrid');
    const card = document.createElement('div');
    card.className = 'mm-file-card';
    card.id = 'mmCard_' + data.filename;

    const shortName = data.filename.length > 20 ? data.filename.substring(0, 20) + '…' : data.filename;

    const deleteBtn = `<button type="button" class="mm-file-delete" onclick="deleteMMFile('${data.filename}')" title="মুছুন"><i class="fas fa-trash"></i></button>`;
    const previewBtn = `<button type="button" class="mm-file-preview" onclick="previewFile('${data.url}','${data.type}')" title="প্রিভিউ"><i class="fas fa-eye"></i></button>`;
    if (data.type === 'video') {
        card.innerHTML = `
            <video class="mm-file-thumb" muted><source src="${data.url}" type="video/mp4"></video>
            <div class="mm-file-type-badge video"><i class="fas fa-play"></i> ভিডিও</div>
            <div class="mm-file-name">${shortName}</div>${deleteBtn}${previewBtn}`;
    } else if (data.type === 'audio') {
        card.innerHTML = `
            <div class="mm-audio-thumb">
                <i class="fas fa-music mm-audio-icon"></i>
                <div class="mm-audio-bars"><span></span><span></span><span></span><span></span><span></span></div>
            </div>
            <div class="mm-file-type-badge audio"><i class="fas fa-music"></i> অডিও</div>
            <div class="mm-file-name">${shortName}</div>${deleteBtn}${previewBtn}`;
    } else {
        card.innerHTML = `
            <img class="mm-file-thumb" src="${data.url}" alt="${shortName}" loading="lazy">
            <div class="mm-file-type-badge image"><i class="fas fa-image"></i> ছবি</div>
            <div class="mm-file-name">${shortName}</div>${deleteBtn}${previewBtn}`;
    }
    grid.insertBefore(card, grid.firstChild);
}

async function deleteMMFile(filename) {
    if (!confirm('এই ফাইলটি মুছে ফেলবেন?')) return;
    const card = document.getElementById('mmCard_' + filename);
    if (card) { card.style.opacity = '.4'; card.style.pointerEvents = 'none'; }

    const res  = await fetch(mmDeleteUrl, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' },
        body: JSON.stringify({ filename })
    });
    const data = await res.json();
    if (data.success) {
        if (card) card.remove();
        if (data.files.length === 0) {
            document.getElementById('mmFilesGrid').innerHTML = `<div id="mmEmpty" class="mm-empty">
                <i class="fas fa-photo-film" style="font-size:2rem;opacity:.2;display:block;margin-bottom:10px"></i>
                কোনো ফাইল আপলোড হয়নি</div>`;
        }
        showToast('ফাইল মুছে ফেলা হয়েছে', 'warn');
    }
}

function previewFile(url, type) {
    const modal = document.createElement('div');
    modal.style.cssText = `position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:9999;
        display:flex;align-items:center;justify-content:center;padding:20px`;
    modal.onclick = () => modal.remove();

    if (type === 'video') {
        modal.innerHTML = `<video src="${url}" controls autoplay style="max-width:90vw;max-height:85vh;border-radius:8px"></video>`;
    } else if (type === 'audio') {
        modal.innerHTML = `
            <div style="background:#1e1b4b;border-radius:16px;padding:40px 48px;text-align:center;min-width:280px">
                <i class="fas fa-music" style="font-size:3rem;color:#a5b4fc;display:block;margin-bottom:20px"></i>
                <audio src="${url}" controls autoplay style="width:100%;margin-top:8px"></audio>
            </div>`;
    } else {
        modal.innerHTML = `<img src="${url}" style="max-width:90vw;max-height:85vh;border-radius:8px;object-fit:contain">`;
    }
    document.body.appendChild(modal);
}

function showToast(msg, type) {
    let t = document.getElementById('configToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'configToast';
        t.style.cssText = `position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
            z-index:9999;padding:11px 22px;border-radius:10px;font-size:.88rem;font-weight:600;
            box-shadow:0 8px 24px rgba(0,0,0,.15);opacity:0;transition:opacity .25s,transform .25s;
            pointer-events:none;white-space:nowrap`;
        document.body.appendChild(t);
    }
    clearTimeout(t._timer);
    t.textContent = msg;
    if (type === 'success') {
        t.style.background='#dcfce7'; t.style.color='#15803d'; t.style.border='1px solid #bbf7d0';
    } else {
        t.style.background='#fef9c3'; t.style.color='#92400e'; t.style.border='1px solid #fde68a';
    }
    t.style.opacity='1'; t.style.transform='translateX(-50%) translateY(0)';
    t._timer = setTimeout(() => {
        t.style.opacity='0'; t.style.transform='translateX(-50%) translateY(20px)';
    }, 3000);
}
</script>
@endpush
@endsection
