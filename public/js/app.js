/* ── Sidebar toggle ─────────────────────────────────────────── */
// sidebar is data-turbo-permanent — reference stays valid across navigations
const sidebar  = document.getElementById('sidebar');
const toggleBtn = document.getElementById('sidebarToggle');

/* Backdrop: give it an id + permanent so Turbo doesn't remove it */
const backdrop = document.createElement('div');
backdrop.className = 'sidebar-backdrop';
backdrop.id = 'sidebarBackdrop';
backdrop.setAttribute('data-turbo-permanent', '');
document.body.appendChild(backdrop);

function isMobile() { return window.innerWidth <= 768; }

function _syncSidebarVar() {
    var w = (!isMobile() && sidebar?.classList.contains('collapsed'))
        ? 'var(--sidebar-collapsed)' : 'var(--sidebar-w)';
    document.documentElement.style.setProperty('--active-sidebar-w', w);
}

// Restore sidebar collapsed state on fresh page load
if (!isMobile() && localStorage.getItem('sidebarCollapsed') === '1') {
    sidebar?.classList.add('collapsed');
}
_syncSidebarVar();

function toggleSidebar() {
    if (isMobile()) {
        sidebar.classList.toggle('mobile-open');
        backdrop.classList.toggle('visible');
    } else {
        sidebar.classList.toggle('collapsed');
        localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed') ? '1' : '0');
        _syncSidebarVar();
    }
}

backdrop.addEventListener('click', () => {
    sidebar.classList.remove('mobile-open');
    backdrop.classList.remove('visible');
});

const menuBtn = document.getElementById('menuBtn');
[menuBtn, toggleBtn].forEach(btn => btn?.addEventListener('click', toggleSidebar));

/* ── Nav-group accordion ─────────────────────────────────────── */
function toggleNavGroup(id) {
    const group = document.getElementById(id);
    if (!group) return;
    // Collapsed sidebar: expand it first, then open this group
    if (!isMobile() && sidebar?.classList.contains('collapsed')) {
        toggleSidebar();
        setTimeout(function() { group.classList.add('open'); }, 50);
        return;
    }
    group.classList.toggle('open');
}

/* ── Form submit loading state — event delegation (works on all pages) ── */
document.addEventListener('submit', function(e) {
    const form = e.target;
    if (form.matches('[data-no-loader]')) return;
    const btn = form.querySelector('button[type=submit]');
    if (btn && !btn.classList.contains('btn-icon-sm')) {
        btn.classList.add('loading');
        setTimeout(() => btn.classList.remove('loading'), 8000);
    }
}, true);

/* ── Utility functions ───────────────────────────────────────── */
const banglaDigits    = n => String(n).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
const toEnglishDigits = s => String(s).replace(/[০-৯]/g, d => '০১২৩৪৫৬৭৮৯'.indexOf(d));
window.toEnglishDigits = toEnglishDigits;

/* ── Bengali → English digit converter on all numeric inputs ── */
function attachBengaliConverter(root) {
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
        if (input.dataset.bengaliWired) return;
        input.dataset.bengaliWired = '1';

        input.addEventListener('input', function () {
            const hasBengali = /[০-৯]/.test(this.value);
            if (!hasBengali) return;

            const start = this.selectionStart;
            const end   = this.selectionEnd;
            const beforeCursor = this.value.slice(0, start);
            const replaced     = toEnglishDigits(this.value);
            this.value         = replaced;

            try { this.setSelectionRange(start, end); } catch(_) {}
            this.dispatchEvent(new Event('change', { bubbles: true }));
        });
    });
}
window.attachBengaliConverter = attachBengaliConverter;

/* ── Live clock ─────────────────────────────────────────────── */
const banglaDay    = ['রবি', 'সোম', 'মঙ্গল', 'বুধ', 'বৃহ', 'শুক্র', 'শনি'];
const banglaMonth  = ['জানু', 'ফেব্রু', 'মার্চ', 'এপ্রিল', 'মে', 'জুন',
                      'জুলাই', 'আগস্ট', 'সেপ্টে', 'অক্টো', 'নভে', 'ডিসে'];

function updateClock() {
    const clockEl = document.getElementById('clock');
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

    document.addEventListener('submit', function (e) {
        const form = e.target;
        // Trigger for DELETE method forms OR any form with data-confirm-msg
        const methodInput = form.querySelector('input[name="_method"]');
        const isDeleteForm = methodInput && methodInput.value.toUpperCase() === 'DELETE';
        const hasConfirmMsg = !!form.dataset.confirmMsg;
        if (!isDeleteForm && !hasConfirmMsg) return;
        if (form.dataset.confirmed) { delete form.dataset.confirmed; return; }

        e.preventDefault();
        _pendingForm = form;

        const modal = document.getElementById('deleteConfirmModal');
        const msg   = form.dataset.confirmMsg || 'এই রেকর্ড মুছে ফেলা হবে। এই কাজটি পূর্বাবস্থায় ফেরানো যাবে না।';
        if (modal) {
            modal.querySelector('.confirm-msg').textContent = msg;
            modal.classList.add('open');
        }
    }, true);

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

    document.addEventListener('turbo:load', function () {
        const overlay = document.getElementById('deleteConfirmModal');
        if (overlay && !overlay._wired) {
            overlay._wired = true;
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) window._cancelDelete();
            });
        }
        document.querySelectorAll('form[onsubmit*="confirm"]').forEach(f => f.removeAttribute('onsubmit'));
    });
})();

/* ── Global Live Search ─────────────────────────────────────── */
(function () {
    let _timer = null;
    let _drop  = null;

    // Create permanent dropdown (survives Turbo body replacement)
    _drop = document.createElement('div');
    _drop.className = 'gsearch-drop';
    _drop.id = 'gSearchDrop';
    _drop.setAttribute('data-turbo-permanent', '');
    document.body.appendChild(_drop);

    function position() {
        const input = document.getElementById('globalSearch');
        if (!input) return;
        const r = input.getBoundingClientRect();
        _drop.style.top   = (r.bottom + 4) + 'px';
        _drop.style.right = (window.innerWidth - r.right) + 'px';
        _drop.style.left  = 'auto';
    }

    // Event delegation — works across Turbo navigations
    document.addEventListener('input', function(e) {
        if (!e.target.matches('#globalSearch')) return;
        clearTimeout(_timer);
        const q = e.target.value.trim();
        if (q.length < 2) { _drop.style.display = 'none'; return; }
        _timer = setTimeout(() => doSearch(q), 250);
    });

    document.addEventListener('keydown', function(e) {
        if (!e.target.matches('#globalSearch')) return;
        if (e.key === 'Escape') { _drop.style.display = 'none'; e.target.blur(); }
    });

    document.addEventListener('click', function (e) {
        const input = document.getElementById('globalSearch');
        if (input && !input.contains(e.target) && !_drop.contains(e.target))
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
})();

/* ── Animate stat counters ──────────────────────────────────── */
function animateValue(el, duration = 900) {
    const text = el.textContent.trim();
    const isCurrency = text.includes('৳');

    let endNum, prefix, suffix;
    if (isCurrency) {
        const match = text.match(/^(৳\s*)([\d,]+)(\.\d+)?$/);
        if (!match) return;
        prefix  = match[1];
        endNum  = parseInt(match[2].replace(/,/g, ''), 10);
        suffix  = match[3] ?? '';
    } else {
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

/* ── Dark Mode ──────────────────────────────────────────────── */
(function () {
    // Apply theme immediately from localStorage to prevent flash
    const saved = localStorage.getItem('theme') || 'light';
    document.documentElement.setAttribute('data-theme', saved);

    window.toggleDarkMode = function () {
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        const next   = isDark ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('theme', next);
        _syncDarkBtn();
    };

    window._syncDarkBtn = function() {
        const btn  = document.getElementById('darkModeBtn');
        const icon = document.getElementById('darkModeIcon');
        if (!btn) return;
        const isDark = document.documentElement.getAttribute('data-theme') === 'dark';
        icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
        btn.title = isDark ? 'লাইট মোড' : 'ডার্ক মোড';
    };

    document.addEventListener('turbo:load', window._syncDarkBtn);
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

    window._syncFontBtns = function() {
        const cur = document.documentElement.getAttribute('data-fontsize') || 'md';
        document.querySelectorAll('.ctrl-btn[data-size]').forEach(btn => {
            btn.classList.toggle('active', btn.dataset.size === cur);
        });
    };

    document.addEventListener('turbo:load', window._syncFontBtns);
})();

/* ── Keyboard shortcuts ─────────────────────────────────────── */
(function () {
    // Navigate to the matching sidebar/topbar link if it exists (keeps
    // role-correct URLs) — otherwise fall back to the canonical path so the
    // shortcut still works on pages/screens where the link isn't rendered.
    const go = (selector, fallback) => {
        const a = document.querySelector(selector);
        window.location = a ? a.href : fallback;
    };
    // Keyed by e.code (physical key) — reliable across keyboard layouts and
    // unaffected by the Alt modifier, unlike e.key.
    const map = {
        'KeyS':  () => go('a[href*="sales/create"]',     '/sales/create'),
        'KeyP':  () => go('a[href*="purchases/create"]', '/purchases/create'),
        'KeyD':  () => go('a[href$="/dashboard"]',       '/dashboard'),
        'KeyK':  () => go('a[href$="/stock"]',           '/stock'),
        'KeyT':  () => go('a[href*="reports/sales"]',    '/reports/sales'),
        'KeyL':  () => go('a[href$="/purchases"]',            '/purchases'),
        'KeyM':  () => go('a[href$="/supplier-payments"]',    '/supplier-payments'),
        'Slash': () => toggleShortcutsHelp(),
    };

    document.addEventListener('keydown', function (e) {
        if (!e.altKey || e.ctrlKey || e.metaKey) return;

        // Alt+B toggles Bengali phonetic typing. Handled here (app.js runs once)
        // instead of the layout body script — that re-ran on every Turbo
        // navigation and stacked duplicate listeners, so the toggle fired
        // multiple times and appeared to "sometimes" do nothing. Works inside
        // inputs too, since that's exactly where you switch typing on.
        if (e.code === 'KeyB') {
            if (typeof window.togglePhonetic === 'function') { e.preventDefault(); window.togglePhonetic(); }
            return;
        }

        const fn = map[e.code];
        if (!fn) return;
        // Don't hijack typing inside a field (Alt+letter could discard input)
        const t = e.target;
        if (t && t.matches && t.matches('input,textarea,select,[contenteditable="true"]')) return;
        e.preventDefault();
        fn();
    });

    window.toggleShortcutsHelp = function () {
        const el = document.getElementById('shortcutsOverlay');
        if (el) el.classList.toggle('open');
    };

    document.addEventListener('turbo:load', function () {
        const overlay = document.getElementById('shortcutsOverlay');
        if (overlay && !overlay._wired) {
            overlay._wired = true;
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) overlay.classList.remove('open');
            });
        }
    });
})();

/* ── Searchable area combobox ────────────────────────────────────────
   Drop-in replacement for long <select> area filters. Rendered by the
   partials/area-combobox.blade.php partial. Bound once here (app.js loads
   with data-turbo-eval="false"); initAreaComboboxes() runs on every
   turbo:load and wires any not-yet-initialised .area-combobox on the page. */
(function () {
    let openCtl = null;   // controller of the currently-open dropdown

    // One set of global listeners (added once) — avoids the per-init
    // stacking that body scripts suffer on every Turbo navigation.
    window.addEventListener('scroll', function () { if (openCtl) openCtl.position(); }, true);
    window.addEventListener('resize', function () { if (openCtl) openCtl.position(); });
    document.addEventListener('click', function (e) {
        if (openCtl && !openCtl.input.contains(e.target) && !openCtl.drop.contains(e.target)) {
            openCtl.drop.style.display = 'none';
            openCtl = null;
        }
    });

    const esc = s => String(s).replace(/[&<>"']/g, c =>
        ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));

    window.initAreaComboboxes = function () {
        document.querySelectorAll('.area-combobox:not([data-ac-ready])').forEach(function (box) {
            box.setAttribute('data-ac-ready', '1');
            const input  = box.querySelector('.ac-search');
            const hidden = box.querySelector('.ac-id');
            const dataEl = box.querySelector('.ac-data');
            let areas;
            try { areas = JSON.parse(dataEl ? dataEl.textContent : '[]'); } catch (_) { areas = []; }
            const allLabel = box.getAttribute('data-all-label') || '— সব এলাকা —';

            const drop = document.createElement('div');
            drop.className = 'ac-drop';
            drop.style.cssText = 'position:fixed;display:none;z-index:9999;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);overflow-y:auto;max-height:260px';
            document.body.appendChild(drop);

            function position() {
                const r = input.getBoundingClientRect();
                drop.style.top   = (r.bottom + 4) + 'px';
                drop.style.left  = r.left + 'px';
                drop.style.width = r.width + 'px';
            }
            function render(q) {
                q = (q || '').trim().toLowerCase();
                const list = q ? areas.filter(a => a.name.toLowerCase().indexOf(q) !== -1) : areas;
                let html = '<div class="ac-item" data-id="">' + esc(allLabel) + '</div>';
                html += list.map(a => '<div class="ac-item" data-id="' + a.id + '">' + esc(a.name) + '</div>').join('');
                if (q && !list.length) html += '<div class="ac-item ac-empty" style="color:#94a3b8">কোনো এলাকা পাওয়া যায়নি</div>';
                drop.innerHTML = html;
                position();
                drop.style.display = 'block';
                openCtl = { input, drop, position };
            }
            function setVal(id, name) {
                hidden.value = id || '';
                input.value  = name || '';
                drop.style.display = 'none';
                openCtl = null;
                hidden.dispatchEvent(new Event('change', { bubbles: true }));
            }

            input.addEventListener('input', function () { render(this.value); });
            input.addEventListener('focus', function () { render(this.value); });
            // mousedown (not click) so selection fires before the input blurs
            drop.addEventListener('mousedown', function (e) {
                const item = e.target.closest('.ac-item');
                if (!item || item.classList.contains('ac-empty')) return;
                e.preventDefault();
                const id = item.getAttribute('data-id');
                if (!id) { setVal('', ''); return; }
                const a = areas.find(x => String(x.id) === String(id));
                setVal(id, a ? a.name : '');
            });
        });
    };

    document.addEventListener('turbo:load', window.initAreaComboboxes);
})();

/* ── Info tooltip (ⓘ button) — permanent so Turbo doesn't remove it ── */
(function () {
    let activeBtn = null;

    const popover = document.createElement('div');
    popover.id = 'info-popover';
    popover.setAttribute('data-turbo-permanent', '');
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

        let left = bRect.left + bRect.width / 2 - 20;
        if (left + pW > vpW - 8) left = vpW - pW - 8;
        if (left < 8) left = 8;

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
            e.preventDefault();
            if (activeBtn === btn) { hidePopover(); return; }
            showPopover(btn);
            return;
        }
        hidePopover();
    });

    window.addEventListener('scroll', hidePopover, true);
    window.addEventListener('resize', hidePopover);
})();

/* ── Per-page init (runs on every Turbo navigation) ─────────── */
document.addEventListener('turbo:load', function() {
    // Remove txn-summary body class on navigation (only relevant on sale/purchase create pages)
    document.body.classList.remove('txn-summary-active');

    // Alert auto-dismiss
    document.querySelectorAll('.alert.alert-success').forEach(function(alert) {
        if (alert._autoHide) return;
        alert._autoHide = true;
        setTimeout(() => {
            alert.style.transition = 'opacity .5s, transform .5s';
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-8px)';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Bengali converter on all new inputs
    attachBengaliConverter(document);

    // Stat counter animation
    document.querySelectorAll('.stat-value').forEach(function(el) {
        if (!el._animated) { el._animated = true; animateValue(el); }
    });

    // Auto-snap date filter forms — opt-in via data-date-snap on <form>
    // From-date change: snaps to-date to same day then submits.
    // To-date change: submits (extends range). Guard prevents double-bind on Turbo.
    document.querySelectorAll('form[data-date-snap]').forEach(function(form) {
        if (form._dateSnap) return;
        form._dateSnap = true;
        var fromEl = form.querySelector('input[type="date"][name="from"]');
        var toEl   = form.querySelector('input[type="date"][name="to"]');
        if (!fromEl || !toEl) return;
        function dsSubmit() { form.requestSubmit ? form.requestSubmit() : form.submit(); }
        if (fromEl.value) toEl.min = fromEl.value;
        if (fromEl.value && toEl.value && toEl.value < fromEl.value) { toEl.value = fromEl.value; dsSubmit(); }
        fromEl.addEventListener('change', function() { if (fromEl.value) { toEl.min = fromEl.value; toEl.value = fromEl.value; } dsSubmit(); });
        fromEl.addEventListener('input',  function() { if (fromEl.value) { toEl.min = fromEl.value; toEl.value = fromEl.value; } dsSubmit(); });
        toEl.addEventListener('change', dsSubmit);
        toEl.addEventListener('input',  dsSubmit);
    });

    // Module card ripples
    document.querySelectorAll('.module-card').forEach(function(card) {
        if (card._rippleWired) return;
        card._rippleWired = true;
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
});

/* inject ripple keyframe once */
const _rippleStyle = document.createElement('style');
_rippleStyle.textContent = `@keyframes ripple{to{transform:scale(2.5);opacity:0}}`;
document.head.appendChild(_rippleStyle);

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

window.bnNumWords       = bnNumWords;
window.bnTakaWords      = bnTakaWords;
window.bnWatchTakaWords = bnWatchTakaWords;
