/* ── Sidebar toggle ─────────────────────────────────────────── */
const sidebar     = document.getElementById('sidebar');
const mainWrapper = document.getElementById('mainWrapper');
const menuBtn     = document.getElementById('menuBtn');
const toggleBtn   = document.getElementById('sidebarToggle');

/* Create mobile backdrop */
const backdrop = document.createElement('div');
backdrop.className = 'sidebar-backdrop';
document.body.appendChild(backdrop);

function isMobile() { return window.innerWidth <= 768; }

function toggleSidebar() {
    if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('visible');
    } else {
        sidebar.classList.toggle('collapsed');
        mainWrapper.classList.toggle('expanded');
    }
}

backdrop.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('visible');
});

[menuBtn, toggleBtn].forEach(btn => btn?.addEventListener('click', toggleSidebar));

/* ── Nav-group accordion ─────────────────────────────────────── */
function toggleNavGroup(id) {
    const group = document.getElementById(id);
    if (!group) return;
    group.classList.toggle('open');
}

/* ── Alert auto-dismiss ──────────────────────────────────────── */
document.querySelectorAll('.alert.alert-success').forEach(alert => {
    setTimeout(() => {
        alert.style.transition = 'opacity .5s, transform .5s';
        alert.style.opacity = '0';
        alert.style.transform = 'translateY(-8px)';
        setTimeout(() => alert.remove(), 500);
    }, 4000);
});

/* ── Form submit loading state ───────────────────────────────── */
document.querySelectorAll('form:not([data-no-loader])').forEach(form => {
    form.addEventListener('submit', function () {
        const btn = this.querySelector('button[type=submit]');
        if (btn && !btn.classList.contains('btn-icon-sm')) {
            btn.classList.add('loading');
            // Re-enable after 8s as failsafe
            setTimeout(() => btn.classList.remove('loading'), 8000);
        }
    });
});

/* ── Live clock ─────────────────────────────────────────────── */
const clockEl = document.getElementById('clock');

const banglaDigits    = n => String(n).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
const toEnglishDigits = s => String(s).replace(/[০-৯]/g, d => '০১২৩৪৫৬৭৮৯'.indexOf(d));
window.toEnglishDigits = toEnglishDigits; // expose globally for blade inline scripts

/* ── Bengali → English digit converter on all numeric inputs ── */
function attachBengaliConverter(root) {
    // NOTE: type="number" inputs are intentionally excluded — browsers block
    // non-ASCII digits (like Bengali) before JS sees them. Use type="text"
    // inputmode="decimal" on all numeric inputs that need Bengali conversion.
    const selector = [
        'input[inputmode="decimal"]',
        'input[inputmode="numeric"]',
        'input[type="text"][name*="amount"]',
        'input[type="text"][name*="price"]',
        'input[type="text"][name*="quantity"]',
        'input[type="text"][name*="qty"]',
        'input[type="text"][name*="paid"]',
        'input[type="text"][name*="due"]',
        'input[type="text"][name*="rate"]',
        'input[type="text"][name*="discount"]',
        'input[type="text"][name*="min_quantity"]',
        'input[type="text"][name*="sale_price"]',
        'input[type="text"][name*="purchase_price"]',
    ].join(',');

    (root || document).querySelectorAll(selector).forEach(input => {
        if (input.dataset.bengaliWired) return;   // avoid double-binding
        input.dataset.bengaliWired = '1';

        input.addEventListener('input', function () {
            const hasBengali = /[০-৯]/.test(this.value);
            if (!hasBengali) return;

            const start = this.selectionStart;
            const end   = this.selectionEnd;
            // Count how many Bengali chars were before the cursor
            const beforeCursor = this.value.slice(0, start);
            const replaced     = toEnglishDigits(this.value);
            this.value         = replaced;

            // Restore cursor — length doesn't change, so position stays same
            try { this.setSelectionRange(start, end); } catch(_) {}

            // Fire change event so Vue/Alpine/custom listeners react
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}

// Run once on DOM ready
document.addEventListener('DOMContentLoaded', () => attachBengaliConverter(document));

// Re-run when dynamic rows are added (POS cart, purchase rows etc.)
const _origAttach = window.attachBengaliConverter;
window.attachBengaliConverter = attachBengaliConverter;   // expose globally
const banglaDay    = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহ', 'শুক্র', 'শনি'];
const banglaMonth  = ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
                      'জুলাই', 'আগস্ট', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'];

function updateClock() {
    if (!clockEl) return;
    const now  = new Date();
    let h      = now.getHours();
    const m    = String(now.getMinutes()).padStart(2, '0');
    const ampm = h >= 12 ? 'PM' : 'AM';
    h = h % 12 || 12;

    const timeStr = `${banglaDigits(String(h).padStart(2,'0'))}:${banglaDigits(m)} ${ampm}`;
    const dateStr = `${banglaDay[now.getDay()]} ${banglaDigits(now.getDate())} ${banglaMonth[now.getMonth()]}`;

    clockEl.querySelector('.time').textContent = timeStr;
    clockEl.querySelector('.date').textContent = dateStr;
}

updateClock();
setInterval(updateClock, 1000);

/* ── Delete Confirmation Modal ──────────────────────────────── */
(function () {
    let _pendingForm = null;

    // Intercept all DELETE method form submissions
    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Check for Laravel _method=DELETE
        const methodInput = form.querySelector('input[name="_method"]');
        if (!methodInput || methodInput.value.toUpperCase() !== 'DELETE') return;
        // Skip if already confirmed
        if (form.dataset.confirmed) { delete form.dataset.confirmed; return; }

        e.preventDefault();
        _pendingForm = form;

        const modal = document.getElementById('deleteConfirmModal');
        const msg   = form.dataset.confirmMsg || 'এই রেকর্ড মুছে ফেলা হবে। এই কাজটি পূর্বাবস্থায় ফেরানো যাবে না।';
        if (modal) {
            modal.querySelector('.confirm-msg').textContent = msg;
            modal.classList.add('open');
        }
    }, true); // capture phase so it runs before onsubmit

    window._confirmDelete = function () {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) modal.classList.remove('open');
        if (_pendingForm) {
            _pendingForm.dataset.confirmed = '1';
            _pendingForm.submit();
            _pendingForm = null;
        }
    };

    window._cancelDelete = function () {
        const modal = document.getElementById('deleteConfirmModal');
        if (modal) modal.classList.remove('open');
        _pendingForm = null;
    };

    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('deleteConfirmModal');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) window._cancelDelete();
            });
        }
        // Remove old inline onsubmit confirm() calls — handled by modal now
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(f => f.removeAttribute('onsubmit'));
    });
})();

/* ── Global Live Search ─────────────────────────────────────── */
(function () {
    let _timer = null;
    let _drop  = null;

    document.addEventListener('DOMContentLoaded', function () {
        const input = document.getElementById('globalSearch');
        if (!input) return;

        _drop = document.createElement('div');
        _drop.className = 'gsearch-drop';
        document.body.appendChild(_drop);

        function position() {
            const r = input.getBoundingClientRect();
            _drop.style.top   = (r.bottom + 4) + 'px';
            _drop.style.right = (window.innerWidth - r.right) + 'px';
            _drop.style.left  = 'auto';
        }

        input.addEventListener('input', function () {
            clearTimeout(_timer);
            const q = this.value.trim();
            if (q.length < 2) { _drop.style.display = 'none'; return; }
            _timer = setTimeout(() => doSearch(q), 250);
        });

        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') { _drop.style.display = 'none'; this.blur(); }
        });

        document.addEventListener('click', function (e) {
            if (!input.contains(e.target) && !_drop.contains(e.target))
                _drop.style.display = 'none';
        });

        window.addEventListener('scroll', position, true);
        window.addEventListener('resize', position);

        function doSearch(q) {
            fetch('/search?q=' + encodeURIComponent(q))
                .then(r => r.json())
                .then(data => renderResults(data, q))
                .catch(() => {});
        }

        function renderResults(data, q) {
            const sections = [];

            if (data.items?.length) {
                sections.push(`<div class="gsearch-label">পণ্য</div>`);
                data.items.forEach(i => {
                    sections.push(`<a href="/items/${i.id}/edit" class="gsearch-item">
                        <div class="gsearch-item-icon"><i class="fas fa-box-open"></i></div>
                        <div><div>${highlight(i.name, q)}</div></div>
                    </a>`);
                });
            }
            if (data.customers?.length) {
                if (sections.length) sections.push('<div class="gsearch-divider"></div>');
                sections.push(`<div class="gsearch-label">কাস্টমার</div>`);
                data.customers.forEach(c => {
                    sections.push(`<a href="/customers/${c.id}" class="gsearch-item">
                        <div class="gsearch-item-icon"><i class="fas fa-user"></i></div>
                        <div><div>${highlight(c.name, q)}</div><div class="gsearch-item-sub">${c.phone || ''}</div></div>
                    </a>`);
                });
            }
            if (data.suppliers?.length) {
                if (sections.length) sections.push('<div class="gsearch-divider"></div>');
                sections.push(`<div class="gsearch-label">সরবরাহকারী</div>`);
                data.suppliers.forEach(s => {
                    sections.push(`<a href="/suppliers/${s.id}" class="gsearch-item">
                        <div class="gsearch-item-icon"><i class="fas fa-truck"></i></div>
                        <div><div>${highlight(s.name, q)}</div><div class="gsearch-item-sub">${s.phone || ''}</div></div>
                    </a>`);
                });
            }

            if (!sections.length) {
                sections.push(`<div class="gsearch-empty"><i class="fas fa-magnifying-glass"></i> "${q}" পাওয়া যায়নি</div>`);
            }

            _drop.innerHTML = `<div class="gsearch-section">${sections.join('')}</div>`;
            position();
            _drop.style.display = 'block';
        }

        function highlight(text, q) {
            const re = new RegExp(`(${q.replace(/[.*+?^${}()|[\]\\]/g,'\\$&')})`, 'gi');
            return text.replace(re, '<mark style="background:#fef3c7;color:#92400e;border-radius:2px;padding:0 1px">$1</mark>');
        }
    });
})();

/* ── Active nav link ────────────────────────────────────────── */
const currentPage = window.location.pathname.split('/').pop() || 'index.html';
document.querySelectorAll('.nav-item').forEach(link => {
    const href = link.getAttribute('href') || '';
    if (href === currentPage || (currentPage === '' && href === 'index.html')) {
        link.classList.add('active');
    } else {
        link.classList.remove('active');
    }
});

/* ── Animate stat counters ──────────────────────────────────── */
function animateValue(el, duration = 900) {
    const text = el.textContent.trim();
    const isCurrency = text.includes('৳');

    // For currency: extract the numeric part before decimal, keep suffix
    let endNum, prefix, suffix;
    if (isCurrency) {
        // e.g. "৳ 1,998,000.00" → prefix="৳ ", endNum=1998000, suffix=".00"
        const match = text.match(/^(৳\s*)([\d,]+)(\.\d+)?$/);
        if (!match) return;
        prefix  = match[1];
        endNum  = parseInt(match[2].replace(/,/g, ''), 10);
        suffix  = match[3] ?? '';
    } else {
        // plain integer e.g. "5"
        endNum  = parseInt(text.replace(/[^\d]/g, ''), 10);
        prefix  = '';
        suffix  = '';
    }

    if (isNaN(endNum) || endNum === 0) return;

    const step = endNum / (duration / 16);
    let current = 0;

    const timer = setInterval(() => {
        current += step;
        if (current >= endNum) { current = endNum; clearInterval(timer); }
        const formatted = Math.floor(current).toLocaleString('en');
        el.textContent = prefix + banglaDigits(formatted) + suffix;
    }, 16);
}

document.querySelectorAll('.stat-value').forEach(el => animateValue(el));

/* ── Ripple on module cards ─────────────────────────────────── */
document.querySelectorAll('.module-card').forEach(card => {
    card.addEventListener('click', function (e) {
        const ripple = document.createElement('span');
        const rect   = card.getBoundingClientRect();
        const size   = Math.max(rect.width, rect.height);
        ripple.style.cssText = `
            position:absolute;width:${size}px;height:${size}px;
            border-radius:50%;background:rgba(79,70,229,.12);
            top:${e.clientY - rect.top - size/2}px;
            left:${e.clientX - rect.left - size/2}px;
            transform:scale(0);animation:ripple .5s linear;
            pointer-events:none;
        `;
        card.appendChild(ripple);
        setTimeout(() => ripple.remove(), 550);
    });
});

/* inject ripple keyframe */
const style = document.createElement('style');
style.textContent = `@keyframes ripple{to{transform:scale(2.5);opacity:0}}`;
document.head.appendChild(style);

/* ── Dark Mode ──────────────────────────────────────────────── */
(function () {
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);

    window.toggleDarkMode = function () {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const next   = isDark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        _syncDarkBtn();
    };

    function _syncDarkBtn() {
        const btn  = document.getElementById('darkModeBtn');
        const icon = document.getElementById('darkModeIcon');
        if (!btn) return;
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        btn.title = isDark ? 'লাইট মোড' : 'ডার্ক মোড';
    }

    // sync after DOM ready
    document.addEventListener('DOMContentLoaded', _syncDarkBtn);
})();

/* ── Font Size ──────────────────────────────────────────────── */
(function () {
    const saved = localStorage.getItem('fontsize') || 'md';
    document.documentElement.setAttribute('data-fontsize', saved);

    window.setFontSize = function (size) {
        document.documentElement.setAttribute('data-fontsize', size);
        localStorage.setItem('fontsize', size);
        _syncFontBtns();
    };

    function _syncFontBtns() {
        const cur = document.documentElement.getAttribute('data-fontsize') || 'md';
        document.querySelectorAll('.ctrl-btn[data-size]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.size === cur);
        });
    }

    document.addEventListener('DOMContentLoaded', _syncFontBtns);
})();

/* ── Keyboard shortcuts ─────────────────────────────────────── */
(function () {
    const map = {
        's': () => { const a = document.querySelector('a[href*="sales/create"]'); if (a) window.location = a.href; },
        'p': () => { const a = document.querySelector('a[href*="purchases/create"]'); if (a) window.location = a.href; },
        'd': () => { const a = document.querySelector('a[href*="dashboard"]'); if (a) window.location = a.href; },
        '/': () => { toggleShortcutsHelp(); },
    };

    document.addEventListener('keydown', function (e) {
        // Skip if user is typing in an input/textarea
        if (e.target.matches('input,textarea,select')) return;
        if (e.altKey && !e.ctrlKey && !e.metaKey) {
            const fn = map[e.key.toLowerCase()];
            if (fn) { e.preventDefault(); fn(); }
        }
    });

    window.toggleShortcutsHelp = function () {
        const el = document.getElementById('shortcutsOverlay');
        if (el) el.classList.toggle('open');
    };

    // close on backdrop click
    document.addEventListener('DOMContentLoaded', function () {
        const overlay = document.getElementById('shortcutsOverlay');
        if (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.classList.remove('open');
            });
        }
    });
})();

/* ── Info tooltip (ⓘ button) ────────────────────────────────── */
(function () {
    let activeBtn = null;

    // Build popover: textEl on top, arrow on bottom
    const popover = document.createElement('div');
    popover.id = 'info-popover';
    popover.style.cssText = [
        'display:none',
        'position:fixed',
        'z-index:999999',
        'max-width:270px',
        'padding:11px 14px',
        'background:#1e293b',
        'color:#f1f5f9',
        'font-family:\'Hind Siliguri\',\'Inter\',sans-serif',
        'font-size:.83rem',
        'line-height:1.65',
        'border-radius:10px',
        'box-shadow:0 8px 28px rgba(0,0,0,.28)',
        'pointer-events:none',
        'word-break:break-word',
    ].join(';');

    const textEl = document.createElement('span');
    textEl.style.display = 'block';
    popover.appendChild(textEl);

    const arrow = document.createElement('div');
    arrow.style.cssText = [
        'position:absolute',
        'width:10px',
        'height:10px',
        'background:#1e293b',
        'transform:rotate(45deg)',
        'border-radius:2px',
        'left:18px',
    ].join(';');
    popover.appendChild(arrow);
    document.body.appendChild(popover);

    function showPopover(btn) {
        const text = btn.dataset.info;
        if (!text) return;

        textEl.textContent = text;
        popover.style.display = 'block';

        const bRect = btn.getBoundingClientRect();
        const pW    = popover.offsetWidth;
        const pH    = popover.offsetHeight;
        const vpW   = window.innerWidth;
        const vpH   = window.innerHeight;

        // Prefer above; fall back to below
        const spaceAbove = bRect.top - 12;
        const spaceBelow = vpH - bRect.bottom - 12;
        const goBelow    = spaceAbove < pH && spaceBelow > spaceAbove;

        let top, arrowTop, arrowBottom;
        if (goBelow) {
            top         = bRect.bottom + 8;
            arrowTop    = '-5px';
            arrowBottom = 'auto';
        } else {
            top         = bRect.top - pH - 8;
            arrowTop    = 'auto';
            arrowBottom = '-5px';
        }

        // Horizontal: align with button, clamp to viewport
        let left = bRect.left + bRect.width / 2 - 20;
        if (left + pW > vpW - 8) left = vpW - pW - 8;
        if (left < 8) left = 8;

        // Arrow horizontal stays near button
        const arrowLeft = Math.min(Math.max(bRect.left - left + bRect.width / 2 - 5, 10), pW - 20);

        popover.style.top  = top  + 'px';
        popover.style.left = left + 'px';
        arrow.style.top    = arrowTop;
        arrow.style.bottom = arrowBottom;
        arrow.style.left   = arrowLeft + 'px';

        activeBtn = btn;
    }

    function hidePopover() {
        popover.style.display = 'none';
        activeBtn = null;
    }

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.info-btn');
        if (btn) {
            e.stopPropagation();
            e.preventDefault(); // prevent anchor navigation when inside <a>
            if (activeBtn === btn) { hidePopover(); return; }
            showPopover(btn);
            return;
        }
        hidePopover();
    });

    window.addEventListener('scroll', hidePopover, true);
    window.addEventListener('resize', hidePopover);
})();

/* ── Amount in Bengali words (টাকার অংক কথায়) ─────────────────── */
const bnOnesWords = [
    'শূন্য','এক','দুই','তিন','চার','পাঁচ','ছয়','সাত','আট','নয়',
    'দশ','এগারো','বারো','তেরো','চৌদ্দ','পনেরো','ষোল','সতেরো','আঠারো','উনিশ',
    'বিশ','একুশ','বাইশ','তেইশ','চব্বিশ','পঁচিশ','ছাব্বিশ','সাতাশ','আটাশ','ঊনত্রিশ',
    'ত্রিশ','একত্রিশ','বত্রিশ','তেত্রিশ','চৌত্রিশ','পঁয়ত্রিশ','ছত্রিশ','সাঁইত্রিশ','আটত্রিশ','ঊনচল্লিশ',
    'চল্লিশ','একচল্লিশ','বিয়াল্লিশ','তেতাল্লিশ','চুয়াল্লিশ','পঁয়তাল্লিশ','ছেচল্লিশ','সাতচল্লিশ','আটচল্লিশ','ঊনপঞ্চাশ',
    'পঞ্চাশ','একান্ন','বাহান্ন','তিপ্পান্ন','চুয়ান্ন','পঞ্চান্ন','ছাপ্পান্ন','সাতান্ন','আটান্ন','ঊনষাট',
    'ষাট','একষট্টি','বাষট্টি','তেষট্টি','চৌষট্টি','পঁয়ষট্টি','ছেষট্টি','সাতষট্টি','আটষট্টি','ঊনসত্তর',
    'সত্তর','একাত্তর','বাহাত্তর','তিয়াত্তর','চুয়াত্তর','পঁচাত্তর','ছিয়াত্তর','সাতাত্তর','আটাত্তর','ঊনআশি',
    'আশি','একাশি','বিরাশি','তিরাশি','চুরাশি','পঁচাশি','ছিয়াশি','সাতাশি','আটাশি','ঊননব্বই',
    'নব্বই','একানব্বই','বিরানব্বই','তিরানব্বই','চুরানব্বই','পঁচানব্বই','ছিয়ানব্বই','সাতানব্বই','আটানব্বই','নিরানব্বই',
];

function bnNumWords(n) {
    n = Math.floor(n);
    if (n === 0) return 'শূন্য';
    const parts = [];
    if (n >= 10000000) { parts.push(bnNumWords(Math.floor(n / 10000000)) + ' কোটি'); n %= 10000000; }
    if (n >= 100000)   { parts.push(bnOnesWords[Math.floor(n / 100000)] + ' লক্ষ');   n %= 100000; }
    if (n >= 1000)     { parts.push(bnOnesWords[Math.floor(n / 1000)] + ' হাজার');    n %= 1000; }
    if (n >= 100)      { parts.push(bnOnesWords[Math.floor(n / 100)] + ' শত');        n %= 100; }
    if (n > 0)         parts.push(bnOnesWords[n]);
    return parts.join(' ');
}

function bnTakaWords(v) {
    v = parseFloat(toEnglishDigits(String(v == null ? '' : v))) || 0;
    if (v <= 0) return '';
    const tk = Math.floor(v);
    const ps = Math.round((v - tk) * 100);
    let s = tk > 0 ? bnNumWords(tk) + ' টাকা' : '';
    if (ps > 0) s += (s ? ' ' : '') + bnNumWords(ps) + ' পয়সা';
    return s;
}

// Polling watcher — also catches programmatic value changes (e.g. "সম্পূর্ণ" button)
function bnWatchTakaWords(inputId, targetId) {
    const el = document.getElementById(inputId);
    const t  = document.getElementById(targetId);
    if (!el || !t) return;
    let last = null;
    const update = () => {
        if (el.value === last) return;
        last = el.value;
        const w = bnTakaWords(el.value);
        t.textContent = w;
        t.style.display = w ? '' : 'none';
    };
    update();
    setInterval(update, 300);
}

window.bnNumWords      = bnNumWords;
window.bnTakaWords     = bnTakaWords;
window.bnWatchTakaWords = bnWatchTakaWords;
