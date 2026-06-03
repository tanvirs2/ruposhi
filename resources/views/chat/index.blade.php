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
                <textarea id="chatInput" class="chat-input" rows="1"
                    placeholder="{{ $activeUser->name }}-কে বার্তা লিখুন..."
                    maxlength="2000"
                    onkeydown="chatKeyDown(event)"></textarea>
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

@push('styles')
<style>
/* ══ Wrapper ══════════════════════════════════════════════════ */
.chat-wrap {
    display: grid;
    grid-template-columns: 280px 1fr;
    height: calc(100vh - 120px);
    border: 1.5px solid var(--border);
    border-radius: var(--radius);
    overflow: hidden;
    background: var(--surface);
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
}

/* ══ Left sidebar ═════════════════════════════════════════════ */
.chat-sidebar {
    border-right: 1.5px solid var(--border);
    display: flex;
    flex-direction: column;
    background: var(--bg);
}
.chat-sidebar-head {
    padding: 14px 16px;
    border-bottom: 1.5px solid var(--border);
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--surface);
}
.chat-user-list { flex: 1; overflow-y: auto; }
.chat-user-row {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 11px 14px;
    border-bottom: 1px solid var(--border);
    text-decoration: none;
    color: inherit;
    transition: background .1s;
    cursor: pointer;
}
.chat-user-row:hover { background: var(--surface); }
.chat-user-row.active {
    background: #f0fdfa;
    border-left: 3px solid var(--accent);
}
.chat-avatar {
    width: 38px; height: 38px;
    border-radius: 50%;
    color: #fff;
    font-weight: 700;
    font-size: .88rem;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}
.chat-avatar.sm { width: 32px; height: 32px; font-size: .78rem; }
.chat-avatar.xs { width: 26px; height: 26px; font-size: .68rem; flex-shrink: 0; }
.chat-user-info { flex: 1; min-width: 0; }
.chat-user-name { font-size: .85rem; font-weight: 700; color: var(--text-primary); }
.chat-user-last {
    font-size: .74rem; color: #94a3b8;
    white-space: nowrap; overflow: hidden; text-overflow: ellipsis;
    margin-top: 1px;
}
.chat-time { font-size: .68rem; color: #94a3b8; }
.chat-unread-badge {
    min-width: 18px; height: 18px;
    background: var(--accent);
    color: #fff;
    border-radius: 999px;
    font-size: .68rem; font-weight: 700;
    display: flex; align-items: center; justify-content: center;
    padding: 0 4px;
}

/* ══ Right: main chat ═════════════════════════════════════════ */
.chat-main {
    display: flex;
    flex-direction: column;
    overflow: hidden;
}
.chat-main-head {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    border-bottom: 1.5px solid var(--border);
    background: var(--surface);
    flex-shrink: 0;
}
.chat-messages {
    flex: 1;
    overflow-y: auto;
    padding: 16px 18px;
    display: flex;
    flex-direction: column;
    gap: 2px;
    background: var(--bg);
}

/* Date divider */
.chat-date-divider {
    display: flex; align-items: center;
    gap: 10px;
    margin: 12px 0 8px;
    color: #94a3b8; font-size: .72rem; text-align: center;
}
.chat-date-divider::before,
.chat-date-divider::after {
    content: ''; flex: 1;
    height: 1px; background: var(--border);
}

/* Bubbles */
.chat-bubble-row {
    display: flex;
    align-items: flex-end;
    gap: 6px;
    margin-bottom: 4px;
}
.chat-bubble-row.mine { flex-direction: row-reverse; }
.chat-bubble {
    max-width: 68%;
    display: flex;
    flex-direction: column;
}
.chat-bubble-text {
    padding: 9px 13px;
    border-radius: 16px;
    font-size: .85rem;
    line-height: 1.55;
    word-break: break-word;
    white-space: pre-wrap;
}
.mine .chat-bubble-text {
    background: var(--accent);
    color: #fff;
    border-bottom-right-radius: 4px;
}
.theirs .chat-bubble-text {
    background: var(--surface);
    color: var(--text-primary);
    border: 1.5px solid var(--border);
    border-bottom-left-radius: 4px;
}
.chat-bubble-time {
    font-size: .67rem;
    color: #94a3b8;
    margin-top: 3px;
    padding: 0 4px;
}
.mine .chat-bubble-time { text-align: right; }

/* Empty state */
.chat-empty-conv {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    gap: 8px;
    padding: 40px 0;
}
.chat-empty-conv i { font-size: 2rem; color: #cbd5e1; }

/* No conversation selected */
.chat-no-conv {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #94a3b8;
    gap: 10px;
}
.chat-no-conv i { font-size: 3rem; color: #e2e8f0; }
.chat-no-conv-title { font-size: 1rem; font-weight: 700; color: var(--text-secondary); }
.chat-no-conv-sub { font-size: .82rem; }

/* Input bar */
.chat-input-bar {
    padding: 12px 16px;
    border-top: 1.5px solid var(--border);
    background: var(--surface);
    flex-shrink: 0;
}
.chat-input-wrap {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: var(--bg);
    border: 1.5px solid var(--border);
    border-radius: 12px;
    padding: 8px 8px 8px 14px;
    transition: border-color .15s;
}
.chat-input-wrap:focus-within { border-color: var(--accent); }
.chat-input {
    flex: 1;
    border: none; outline: none; background: transparent;
    font-family: inherit; font-size: .87rem;
    color: var(--text-primary);
    resize: none;
    max-height: 120px;
    line-height: 1.55;
}
.chat-send-btn {
    width: 36px; height: 36px;
    border-radius: 9px;
    background: var(--accent);
    color: #fff;
    border: none; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: .88rem;
    flex-shrink: 0;
    transition: background .12s, transform .1s;
}
.chat-send-btn:hover { background: var(--accent-dark, #0b7a6e); transform: scale(1.05); }
.chat-send-btn:active { transform: scale(.97); }

/* Topbar chat badge */
.chat-topbar-wrap { position: relative; }
.chat-icon-btn { position: relative; }
.chat-badge {
    position: absolute;
    top: -4px; right: -4px;
    min-width: 16px; height: 16px;
    background: #ef4444;
    color: #fff;
    border-radius: 999px;
    font-size: .62rem; font-weight: 800;
    display: flex; align-items: center; justify-content: center;
    padding: 0 3px;
    border: 2px solid var(--surface);
    line-height: 1;
}

@media (max-width: 700px) {
    .chat-wrap { grid-template-columns: 1fr; height: auto; }
    .chat-sidebar { height: 240px; border-right: none; border-bottom: 1.5px solid var(--border); }
}
</style>
@endpush

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

    const btn = document.getElementById('chatSendBtn');
    btn.disabled = true;

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
    .finally(() => { btn.disabled = false; input.focus(); });
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
