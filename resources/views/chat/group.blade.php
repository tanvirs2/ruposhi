@extends('layouts.app')
@section('title', 'গ্রুপ চ্যাট')
@section('page-title', 'গ্রুপ চ্যাট — সবাই')

@section('content')
<div class="chat-wrap">

    {{-- ══ LEFT sidebar (links back to private chat) ══════════ --}}
    <div class="chat-sidebar">
        <div class="chat-sidebar-head">
            <span style="font-weight:700;font-size:.9rem;display:flex;align-items:center;gap:8px">
                <i class="fas fa-comments" style="color:var(--accent)"></i> চ্যাট
            </span>
        </div>
        <div class="chat-user-list">
            {{-- Group entry --}}
            <a href="{{ route('chat.group') }}" class="chat-user-row active">
                <div class="chat-avatar" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
                    <i class="fas fa-users" style="font-size:.8rem"></i>
                </div>
                <div class="chat-user-info">
                    <div class="chat-user-name">📢 সবাই (গ্রুপ)</div>
                    <div class="chat-user-last">সকল ব্যবহারকারী</div>
                </div>
            </a>
            {{-- Private chats link --}}
            <a href="{{ route('chat.index') }}" class="chat-user-row">
                <div class="chat-avatar" style="background:#64748b">
                    <i class="fas fa-user" style="font-size:.8rem"></i>
                </div>
                <div class="chat-user-info">
                    <div class="chat-user-name">ব্যক্তিগত চ্যাট</div>
                    <div class="chat-user-last">একজনকে বার্তা পাঠান</div>
                </div>
            </a>
        </div>
    </div>

    {{-- ══ RIGHT: Group chat window ════════════════════════════ --}}
    <div class="chat-main">
        {{-- Header --}}
        <div class="chat-main-head">
            <div class="chat-avatar sm" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
                <i class="fas fa-users" style="font-size:.72rem"></i>
            </div>
            <div>
                <div style="font-weight:700;font-size:.9rem">📢 সবাই — গ্রুপ চ্যাট</div>
                <div style="font-size:.73rem;color:#94a3b8">সকল ব্যবহারকারী এই চ্যাট দেখতে পাবেন</div>
            </div>
        </div>

        {{-- Messages --}}
        <div class="chat-messages" id="chatMessages">
            @php $lastDate = null; @endphp
            @forelse($messages as $msg)
            @php
                $msgDate = $msg->created_at->toDateString();
                $isMine  = $msg->sender_id === auth()->id();
            @endphp

            @if($msgDate !== $lastDate)
            <div class="chat-date-divider">
                <span>{{ $msg->created_at->isToday() ? 'আজ' : ($msg->created_at->isYesterday() ? 'গতকাল' : $msg->created_at->format('d M Y')) }}</span>
            </div>
            @php $lastDate = $msgDate; @endphp
            @endif

            <div class="chat-bubble-row {{ $isMine ? 'mine' : 'theirs' }}" data-id="{{ $msg->id }}">
                @if(!$isMine)
                <div class="chat-avatar xs grp-av" title="{{ $msg->sender->name }}">
                    {{ strtoupper(mb_substr($msg->sender->name, 0, 1)) }}
                </div>
                @endif
                <div class="chat-bubble">
                    @if(!$isMine)
                    <div class="grp-sender-name">{{ $msg->sender->name }}</div>
                    @endif
                    <div class="chat-bubble-text">{{ $msg->message }}</div>
                    <div class="chat-bubble-time">{{ $msg->created_at->format('h:i a') }}</div>
                </div>
            </div>
            @empty
            <div class="chat-empty-conv">
                <i class="fas fa-users"></i>
                <div>গ্রুপ চ্যাট শুরু করুন — সবাই দেখতে পাবে</div>
            </div>
            @endforelse
        </div>

        {{-- Input --}}
        <div class="chat-input-bar">
            <div class="chat-input-wrap">
                <div class="chat-input-inner">
                    <textarea id="chatInput" class="chat-input" rows="1"
                        placeholder="সবাইকে বার্তা লিখুন..."
                        maxlength="2000"
                        onkeydown="chatKeyDown(event)"></textarea>
                    <span class="chat-inline-loader" id="chatSendLoader">
                        <span></span><span></span><span></span>
                    </span>
                </div>
                <button type="button" class="chat-send-btn" id="chatSendBtn" onclick="sendMessage()">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div style="font-size:.72rem;color:#cbd5e1;margin-top:4px;padding-left:4px">
                Enter → পাঠান &nbsp;·&nbsp; Shift+Enter → নতুন লাইন
            </div>
        </div>
    </div>
</div>
@endsection

{{-- Chat CSS (incl. .grp-av, .grp-sender-name) lives in public/css/app.css --}}

@push('scripts')
<script>
const ME         = {{ auth()->id() }};
const SEND_URL   = '{{ route('chat.group.send') }}';
const POLL_URL   = '{{ route('chat.group.poll') }}';
let lastMsgId    = {{ $messages->isNotEmpty() ? $messages->last()->id : 0 }};
let pollTimer    = null;

scrollBottom();

// ── Auto-grow ────────────────────────────────────────────────
const chatInput = document.getElementById('chatInput');
chatInput.addEventListener('input', function () {
    this.style.height = 'auto';
    this.style.height = Math.min(this.scrollHeight, 120) + 'px';
});

function scrollBottom(smooth = false) {
    const el = document.getElementById('chatMessages');
    if (el) el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
}

// ── Send ─────────────────────────────────────────────────────
function chatKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); sendMessage(); }
}

function sendMessage() {
    const text = chatInput.value.trim();
    if (!text) return;
    const btn    = document.getElementById('chatSendBtn');
    const loader = document.getElementById('chatSendLoader');
    btn.disabled = true;
    loader.classList.add('active');

    fetch(SEND_URL, {
        method: 'POST',
        headers: { 'Content-Type':'application/json', 'Accept':'application/json',
                   'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
        body: JSON.stringify({ message: text }),
    })
    .then(r => r.json())
    .then(msg => {
        chatInput.value = ''; chatInput.style.height = 'auto';
        appendBubble(msg);
        scrollBottom(true);
        lastMsgId = msg.id;
    })
    .finally(() => {
        btn.disabled = false;
        loader.classList.remove('active');
        chatInput.focus();
    });
}

// ── Append bubble ────────────────────────────────────────────
function appendBubble(msg) {
    const container = document.getElementById('chatMessages');
    const empty = container.querySelector('.chat-empty-conv');
    if (empty) empty.remove();

    const isMine = msg.sender_id == ME;
    const row = document.createElement('div');
    row.className = 'chat-bubble-row ' + (isMine ? 'mine' : 'theirs');
    row.dataset.id = msg.id;

    let avHtml = '';
    if (!isMine) {
        avHtml = `<div class="chat-avatar xs grp-av" title="${escH(msg.sender_name)}">${escH(msg.sender_name.charAt(0).toUpperCase())}</div>`;
    }

    const senderHtml = !isMine ? `<div class="grp-sender-name">${escH(msg.sender_name)}</div>` : '';

    row.innerHTML = `${avHtml}
        <div class="chat-bubble">
            ${senderHtml}
            <div class="chat-bubble-text">${escH(msg.message).replace(/\n/g,'<br>')}</div>
            <div class="chat-bubble-time">${msg.created_at}</div>
        </div>`;
    container.appendChild(row);
}

function escH(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// ── WebSocket: receive via layout's ws:message event ─────────
window.addEventListener('ws:message', function (e) {
    const msg = e.detail;
    if (msg.is_group && !document.querySelector(`[data-id="${msg.id}"]`)) {
        appendBubble(msg);
        scrollBottom(true);
        lastMsgId = Math.max(lastMsgId, msg.id);
    }
});

// ── Polling fallback ─────────────────────────────────────────
function startPolling() {
    pollTimer = setInterval(function () {
        fetch(`${POLL_URL}?last_id=${lastMsgId}`, {
            headers: { 'Accept':'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        })
        .then(r => r.json())
        .then(function (data) {
            data.messages.forEach(function (msg) {
                if (msg.sender_id != ME && !document.querySelector(`[data-id="${msg.id}"]`)) {
                    appendBubble(msg);
                    scrollBottom(true);
                }
                lastMsgId = Math.max(lastMsgId, msg.id);
            });
        }).catch(function () {});
    }, 3000);
}

startPolling();
document.addEventListener('visibilitychange', function () {
    if (document.hidden) { clearInterval(pollTimer); }
    else { startPolling(); }
});
</script>
@endpush
