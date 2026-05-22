/* ── Sidebar toggle ─────────────────────────────────────────── */
const sidebar     = document.getElementById('sidebar');
const mainWrapper = document.getElementById('mainWrapper');
const menuBtn     = document.getElementById('menuBtn');
const toggleBtn   = document.getElementById('sidebarToggle');

function toggleSidebar() {
    sidebar.classList.toggle('collapsed');
    mainWrapper.classList.toggle('expanded');
}

[menuBtn, toggleBtn].forEach(btn => btn?.addEventListener('click', toggleSidebar));

/* ── Live clock ─────────────────────────────────────────────── */
const clockEl = document.getElementById('clock');

const banglaDigits = n => String(n).replace(/\d/g, d => '০১২৩৪৫৬৭৮৯'[d]);
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
function animateValue(el, end, duration = 900) {
    const raw    = el.textContent.replace(/[^\d]/g, '');
    const start  = 0;
    const endNum = parseInt(raw, 10);
    if (isNaN(endNum)) return;

    const prefix = el.textContent.replace(/[\d,]+/, '').split(String(endNum))[0];
    const step   = (endNum - start) / (duration / 16);
    let current  = start;

    const timer = setInterval(() => {
        current += step;
        if (current >= endNum) { current = endNum; clearInterval(timer); }
        el.textContent = prefix + banglaDigits(Math.floor(current).toLocaleString('en'));
    }, 16);
}

document.querySelectorAll('.stat-value').forEach(el => animateValue(el, 0, 900));

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
