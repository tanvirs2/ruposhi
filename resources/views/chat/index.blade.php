@extends('layouts.app')
@section('title', 'চ্যাট')
@section('page-title', 'চ্যাট')

@section('content')
<div class="chat-wrap">

    {{-- ══ LEFT: User list ══════════════════════════════════════ --}}
    <div class="chat-sidebar">
        <div class="chat-sidebar-head">
            <span style="font-weight:700;font-size:.9rem;display:flex;align-items:center;gap:8px">
                <i class="fas fa-comments" style="color:var(--accent)"></i> কথোপকথন
            </span>
            <span style="font-size:.74rem;color:#94a3b8">{{ $userList->count() }} জন</span>
        </div>

        <div class="chat-user-list">
            {{-- Group chat entry --}}
            <a href="{{ route('chat.group') }}" class="chat-user-row">
                <div class="chat-avatar" style="background:linear-gradient(135deg,#f59e0b,#ef4444)">
                    <i class="fas fa-users" style="font-size:.8rem"></i>
                </div>
                <div class="chat-user-info">
                    <div class="chat-user-name">📢 সবাই (গ্রুপ)</div>
                    <div class="chat-user-last">সকল ব্যবহারকারী</div>
                </div>
            </a>
            @forelse($userList as $item)
            @php $u = $item['user']; @endphp
            <a href="{{ route('chat.index', ['with' => $u->id]) }}"
               class="chat-user-row {{ $activeUser && $activeUser->id === $u->id ? 'active' : '' }}">
                <div class="chat-avatar" style="background:{{ $u->id % 2 === 0 ? '#3b82f6' : '#7c3aed' }}">
                    {{ strtoupper(mb_substr($u->name, 0, 1)) }}
                </div>
                <div class="chat-user-info">
                    <div class="chat-user-name">{{ $u->name }}</div>
                    <div class="chat-user-last">
                        @if($item['last'])
                            @if($item['last']->sender_id === auth()->id())
                                <span style="color:#94a3b8">আপনি: </span>
                            @endif
                            {{ mb_substr($item['last']->message, 0, 32) }}{{ mb_strlen($item['last']->message) > 32 ? '...' : '' }}
                        @else
                            <span style="color:#cbd5e1;font-style:italic">কোনো বার্তা নেই</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:4px;flex-shrink:0">
                    @if($item['last'])
                    <span class="chat-time">{{ $item['last']->created_at->format('h:ia') }}</span>
                    @endif
                    @if($item['unread'] > 0)
                    <span class="chat-unread-badge">{{ $item['unread'] }}</span>
                    @endif
                </div>
            </a>
            @empty
            <div style="padding:30px;text-align:center;color:#94a3b8;font-size:.83rem">
                কোনো অন্য ব্যবহারকারী নেই
            </div>
            @endforelse
        </div>
    </div>

    {{-- ══ RIGHT: Chat window ════════════════════════════════════ --}}
    <div class="chat-main">
        @if($activeUser)

        {{-- Chat header --}}
        <div class="chat-main-head">
            <div class="chat-avatar sm" style="background:{{ $activeUser->id % 2 === 0 ? '#3b82f6' : '#7c3aed' }}">
                {{ strtoupper(mb_substr($activeUser->name, 0, 1)) }}
            </div>
            <div>
                <div style="font-weight:700;font-size:.9rem">{{ $activeUser->name }}</div>
                <div style="font-size:.73rem;color:#94a3b8">
                    {{ $activeUser->role === 'admin' ? 'অ্যাডমিন' : 'স্টাফ' }}
                </div>
            </div>
        </div>

        {{-- Messages area --}}
        <div class="chat-messages" id="chatMessages">
            @php $lastDate = null; @endphp
            @forelse($messages as $msg)
            @php
                $msgDate = $msg->created_at->toDateString();
                $isMine  = $msg->sender_id === $me->id;
            @endphp

            @if($msgDate !== $lastDate)
            <div class="chat-date-divider">
                <span>{{ $msg->created_at->isToday() ? 'আজ' : ($msg->created_at->isYesterday() ? 'গতকাল' : $msg->created_at->format('d M Y')) }}</span>
            </div>
            @php $lastDate = $msgDate; @endphp
            @endif

            <div class="chat-bubble-row {{ $isMine ? 'mine' : 'theirs' }}" data-id="{{ $msg->id }}">
                @if(!$isMine)
                <div class="chat-avatar xs" style="background:{{ $activeUser->id % 2 === 0 ? '#3b82f6' : '#7c3aed' }}">
                    {{ strtoupper(mb_substr($activeUser->name, 0, 1)) }}
                </div>
                @endif
                <div class="chat-bubble">
                    <div class="chat-bubble-text">{{ $msg->message }}</div>
                    <div class="chat-bubble-time">{{ $msg->created_at->format('h:i a') }}</div>
                </div>
            </div>
            @empty
            <div class="chat-empty-conv">
                <i class="fas fa-comments"></i>
                <div>{{ $activeUser->name }}-এর সাথে কথোপকথন শুরু করুন</div>
            </div>
            @endforelse
        </div>

        {{-- Input --}}
        <div class="chat-input-bar">
            <div class="chat-input-wrap">
                <div class="chat-input-inner">
                    <textarea id="chatInput" class="chat-input" rows="1"
                        placeholder="{{ $activeUser->name }}-কে বার্তা লিখুন..."
                        maxlength="2000"
                        onkeydown="chatKeyDown(event)"></textarea>
                    <span class="chat-inline-loader" id="chatSendLoader">
                        <span></span><span></span><span></span>
                    </span>
                </div>
                <button type="button" class="chat-send-btn" id="chatSendBtn" onclick="sendMessage()" title="পাঠান (Enter)">
                    <i class="fas fa-paper-plane"></i>
                </button>
            </div>
            <div style="font-size:.72rem;color:#cbd5e1;margin-top:4px;padding-left:4px">
                Enter → পাঠান &nbsp;·&nbsp; Shift+Enter → নতুন লাইন
            </div>
        </div>

        @else
        {{-- No conversation selected --}}
        <div class="chat-no-conv">
            <i class="fas fa-comments"></i>
            <div class="chat-no-conv-title">কথোপকথন নির্বাচন করুন</div>
            <div class="chat-no-conv-sub">বাম দিক থেকে একজন ব্যবহারকারী বেছে নিন</div>
        </div>
        @endif
    </div>

</div>
@endsection

{{-- Chat CSS lives in public/css/app.css (shared with group.blade.php) --}}

@push('scripts')
<script>
const RECEIVER_ID = {{ $activeUser ? $activeUser->id : 'null' }};
let lastMsgId     = {{ $messages->isNotEmpty() ? $messages->last()->id : 0 }};
let pollTimer     = null;

// ── Auto-scroll to bottom ────────────────────────────────────
function scrollBottom(smooth = false) {
    const el = document.getElementById('chatMessages');
    if (!el) return;
    el.scrollTo({ top: el.scrollHeight, behavior: smooth ? 'smooth' : 'instant' });
}
scrollBottom();

// ── Auto-grow textarea ───────────────────────────────────────
const chatInput = document.getElementById('chatInput');
if (chatInput) {
    chatInput.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });
}

// ── Enter to send ────────────────────────────────────────────
function chatKeyDown(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        sendMessage();
    }
}

// ── Send message ─────────────────────────────────────────────
function sendMessage() {
    const input = document.getElementById('chatInput');
    const text  = input.value.trim();
    if (!text || !RECEIVER_ID) return;

    const btn    = document.getElementById('chatSendBtn');
    const loader = document.getElementById('chatSendLoader');
    btn.disabled = true;
    loader.classList.add('active');

    fetch('{{ route('chat.send') }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
            'Accept': 'application/json',
        },
        body: JSON.stringify({ receiver_id: RECEIVER_ID, message: text }),
    })
    .then(r => r.json())
    .then(msg => {
        input.value = '';
        input.style.height = 'auto';
        appendBubble(msg, true);
        scrollBottom(true);
        lastMsgId = msg.id;
    })
    .catch(() => alert('পাঠানো ব্যর্থ হয়েছে।'))
    .finally(() => {
        btn.disabled = false;
        loader.classList.remove('active');
        input.focus();
    });
}

// ── Append bubble to DOM ─────────────────────────────────────
function appendBubble(msg, isMine) {
    const container = document.getElementById('chatMessages');

    // Remove empty-conv if present
    const empty = container.querySelector('.chat-empty-conv');
    if (empty) empty.remove();

    const row = document.createElement('div');
    row.className = 'chat-bubble-row ' + (isMine ? 'mine' : 'theirs');
    row.dataset.id = msg.id;

    if (!isMine) {
        const av = document.createElement('div');
        av.className = 'chat-avatar xs';
        av.style.background = '{{ $activeUser ? ($activeUser->id % 2 === 0 ? "#3b82f6" : "#7c3aed") : "#3b82f6" }}';
        av.textContent = '{{ $activeUser ? strtoupper(mb_substr($activeUser->name, 0, 1)) : "?" }}';
        row.appendChild(av);
    }

    const bubble = document.createElement('div');
    bubble.className = 'chat-bubble';
    bubble.innerHTML = `<div class="chat-bubble-text">${escHtml(msg.message)}</div>
                        <div class="chat-bubble-time">${msg.created_at}</div>`;
    row.appendChild(bubble);
    container.appendChild(row);
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/\n/g,'<br>');
}

// ── WebSocket: instant delivery when Reverb/Pusher running ──────
window.addEventListener('ws:message', function (e) {
    const msg = e.detail;
    if (RECEIVER_ID && msg.sender_id == RECEIVER_ID) {
        if (!document.querySelector(`[data-id="${msg.id}"]`)) {
            appendBubble(msg, false);
            scrollBottom(true);
        }
        lastMsgId = Math.max(lastMsgId, msg.id);
    }
});

// ── Polling fallback (3s) — works without Reverb/Pusher ─────────
function startPolling() {
    if (!RECEIVER_ID) return;
    pollTimer = setInterval(function () {
        fetch(`{{ route('chat.poll') }}?with=${RECEIVER_ID}&last_id=${lastMsgId}`, {
            headers: { 'Accept': 'application/json',
                       'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
        })
        .then(r => r.json())
        .then(function (data) {
            data.messages.forEach(function (msg) {
                if (msg.sender_id != {{ auth()->id() }} && !document.querySelector(`[data-id="${msg.id}"]`)) {
                    appendBubble(msg, false);
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
