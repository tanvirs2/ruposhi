// ── Offline Sale Capture — IndexedDB + auto-sync ─────────────
// Loaded once via data-turbo-eval="false". Do NOT use top-level const/let.
(function () {
    'use strict';

    var DB_NAME    = 'ruposhi_offline';
    var DB_VERSION = 1;
    var STORE      = 'pending_sales';
    var _db        = null;

    /* ── IndexedDB helpers ─────────────────────────────────── */
    function openDB() {
        return new Promise(function (resolve, reject) {
            if (_db) { resolve(_db); return; }
            var req = indexedDB.open(DB_NAME, DB_VERSION);
            req.onupgradeneeded = function (e) {
                var d = e.target.result;
                if (!d.objectStoreNames.contains(STORE)) {
                    d.createObjectStore(STORE, { keyPath: 'id', autoIncrement: true });
                }
            };
            req.onsuccess = function (e) { _db = e.target.result; resolve(_db); };
            req.onerror   = function (e) { reject(e.target.error); };
        });
    }

    window.OfflineSales = {
        save: function (entries) {
            return openDB().then(function (db) {
                return new Promise(function (resolve, reject) {
                    var req = db.transaction(STORE, 'readwrite')
                                .objectStore(STORE)
                                .add({ entries: entries, savedAt: new Date().toISOString() });
                    req.onsuccess = function () { resolve(req.result); };
                    req.onerror   = function (e) { reject(e.target.error); };
                });
            });
        },

        getAll: function () {
            return openDB().then(function (db) {
                return new Promise(function (resolve, reject) {
                    var req = db.transaction(STORE, 'readonly').objectStore(STORE).getAll();
                    req.onsuccess = function () { resolve(req.result); };
                    req.onerror   = function (e) { reject(e.target.error); };
                });
            });
        },

        remove: function (id) {
            return openDB().then(function (db) {
                return new Promise(function (resolve, reject) {
                    var req = db.transaction(STORE, 'readwrite').objectStore(STORE).delete(id);
                    req.onsuccess = resolve;
                    req.onerror   = function (e) { reject(e.target.error); };
                });
            });
        },

        count: function () {
            return openDB().then(function (db) {
                return new Promise(function (resolve, reject) {
                    var req = db.transaction(STORE, 'readonly').objectStore(STORE).count();
                    req.onsuccess = function () { resolve(req.result); };
                    req.onerror   = function (e) { reject(e.target.error); };
                });
            });
        },

        /* POST each pending sale to the server with a fresh CSRF token */
        sync: function (onProgress) {
            var csrfMeta = document.querySelector('meta[name="csrf-token"]');
            if (!csrfMeta) return Promise.reject(new Error('CSRF token missing'));
            var token = csrfMeta.content;

            return OfflineSales.getAll().then(function (pending) {
                var total = pending.length;
                if (!total) return { synced: 0, failed: 0, total: 0 };

                var synced = 0, failed = 0;

                function next(i) {
                    if (i >= total) return Promise.resolve({ synced: synced, failed: failed, total: total });

                    var item = pending[i];
                    var fd   = new FormData();
                    item.entries.forEach(function (pair) {
                        if (pair[0] === '_token') return; // replaced below
                        fd.append(pair[0], pair[1]);
                    });
                    fd.append('_token', token);

                    return fetch('/sales', {
                        method:      'POST',
                        body:        fd,
                        credentials: 'same-origin',
                        headers:     { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                    .then(function (res) {
                        if (res.ok) {
                            return OfflineSales.remove(item.id).then(function () {
                                synced++;
                                if (onProgress) onProgress(synced, failed, total);
                                return next(i + 1);
                            });
                        } else {
                            failed++;
                            if (onProgress) onProgress(synced, failed, total);
                            return next(i + 1);
                        }
                    })
                    .catch(function () {
                        failed++;
                        if (onProgress) onProgress(synced, failed, total);
                        return next(i + 1);
                    });
                }

                return next(0);
            });
        }
    };

    /* ── Toast helper ──────────────────────────────────────── */
    function toast(msg, color) {
        var el = document.createElement('div');
        el.style.cssText = [
            'position:fixed;bottom:90px;left:50%;transform:translateX(-50%)',
            'background:' + (color || '#1d4ed8'),
            'color:#fff;padding:12px 24px;border-radius:10px',
            'font-size:.88rem;font-weight:600;z-index:10000',
            'box-shadow:0 4px 20px rgba(0,0,0,.25);max-width:90vw;text-align:center'
        ].join(';');
        el.textContent = msg;
        document.body.appendChild(el);
        setTimeout(function () { el.remove(); }, 4500);
    }

    /* ── UI state update ───────────────────────────────────── */
    function refreshUI() {
        var dot     = document.getElementById('offlineDot');
        var pendBtn = document.getElementById('offlinePendingBtn');
        var banner  = document.getElementById('offlineSyncBanner');
        var badge   = document.getElementById('offlinePendingBadge');

        if (!navigator.onLine) {
            if (dot)     dot.style.display = 'flex';
            if (pendBtn) pendBtn.style.display = 'none';
            if (banner) {
                banner.querySelector('#offlineBannerMsg').textContent =
                    'ইন্টারনেট নেই — বিক্রয় জমা করুন, অফলাইনে সংরক্ষিত হবে';
                banner.querySelector('#offlineSyncBtn').style.display = 'none';
                banner.style.display = 'flex';
            }
        } else {
            if (dot) dot.style.display = 'none';
            OfflineSales.count().then(function (n) {
                if (badge) {
                    badge.textContent   = n;
                    badge.style.display = n > 0 ? 'inline-flex' : 'none';
                }
                if (pendBtn) pendBtn.style.display = n > 0 ? 'flex' : 'none';
                if (banner) {
                    if (n > 0) {
                        banner.querySelector('#offlineBannerMsg').textContent =
                            'ইন্টারনেট ফিরে এসেছে — ' + n + 'টি বিক্রয় sync বাকী';
                        banner.querySelector('#offlineSyncBtn').style.display = 'inline-flex';
                        banner.style.display = 'flex';
                    } else {
                        banner.style.display = 'none';
                    }
                }
            });
        }
    }

    /* ── Sync button handler (called from onclick) ─────────── */
    window.syncOfflineSales = function () {
        var btn = document.getElementById('offlineSyncBtn');
        if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sync হচ্ছে...'; }

        OfflineSales.sync(function (s, f, t) {
            if (btn) btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> ' + s + '/' + t + ' হয়েছে...';
        }).then(function (result) {
            if (result.synced > 0) toast(result.synced + 'টি বিক্রয় সফলভাবে সংরক্ষিত!', '#16a34a');
            if (result.failed > 0) toast(result.failed + 'টি বিক্রয় sync হয়নি।', '#dc2626');
        }).catch(function () {
            toast('Sync ব্যর্থ — পেজ রিফ্রেশ করে আবার চেষ্টা করুন।', '#dc2626');
        }).finally(function () {
            if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fas fa-rotate"></i> Sync করুন'; }
            refreshUI();
        });
    };

    /* ── Offline form intercept ─────────────────────────────
       Runs AFTER saleForm's own submit handlers (bubble phase, document-level).
       By then, renderCart() has already populated the hidden item inputs.
    ─────────────────────────────────────────────────────── */
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form || form.id !== 'saleForm') return;
        if (e.defaultPrevented)  return; // validation failed — don't intercept
        if (navigator.onLine)    return; // online — normal submission

        e.preventDefault();

        var entries  = Array.from(new FormData(form).entries());
        var hasItems = entries.some(function (p) { return p[0].indexOf('items[') === 0; });

        if (!hasItems) {
            toast('কমপক্ষে একটি আইটেম যোগ করুন।', '#dc2626');
            return;
        }

        OfflineSales.save(entries).then(function () {
            toast('বিক্রয় অফলাইনে সংরক্ষিত হয়েছে! ইন্টারনেট ফিরলে স্বয়ংক্রিয়ভাবে sync হবে।', '#1d4ed8');
            refreshUI();
        }).catch(function (err) {
            toast('সংরক্ষণ ব্যর্থ: ' + (err.message || err), '#dc2626');
        });
    }, false);

    /* ── Wire up events ────────────────────────────────────── */
    window.addEventListener('online',       refreshUI);
    window.addEventListener('offline',      refreshUI);
    document.addEventListener('turbo:load', refreshUI);
})();
