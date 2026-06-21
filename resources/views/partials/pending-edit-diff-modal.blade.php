{{-- Pending edit diff modal --}}
{{-- Usage: @include('partials.pending-edit-diff-modal') --}}
<div id="pendingEditModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.45);align-items:center;justify-content:center">
    <div style="background:var(--surface);border-radius:14px;width:min(680px,96vw);max-height:88vh;
                overflow:hidden;display:flex;flex-direction:column;box-shadow:0 20px 60px rgba(0,0,0,.3)">
        <div style="padding:18px 24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
            <h3 style="font-size:.95rem;font-weight:700;color:var(--text-primary)">
                <i class="fas fa-code-compare" style="color:var(--accent)"></i> সংশোধনের বিবরণ
            </h3>
            <button onclick="closePendingEditModal()" style="background:none;border:none;font-size:1.2rem;color:var(--text-secondary);cursor:pointer">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div id="pendingEditModalBody" style="overflow-y:auto;padding:20px 24px;flex:1"></div>
        <div id="pendingEditModalFooter" style="padding:14px 24px;border-top:1px solid var(--border);display:flex;gap:10px;justify-content:flex-end"></div>
    </div>
</div>

<style>
.diff-table { width:100%; border-collapse:collapse; font-size:.85rem; }
.diff-table th { background:var(--surface-2); padding:8px 12px; text-align:left; font-weight:600; color:var(--text-secondary); border-bottom:1px solid var(--border); }
.diff-table td { padding:8px 12px; border-bottom:1px solid var(--border); vertical-align:top; }
.diff-table tr:last-child td { border-bottom:none; }
.diff-old { color:#ef4444; background:#fff1f2; border-radius:4px; padding:2px 6px; }
.diff-new { color:#16a34a; background:#f0fdf4; border-radius:4px; padding:2px 6px; }
</style>

<script>
function openPendingEditModal(btn, approveUrl, rejectUrl) {
    var data;
    try { data = JSON.parse(btn.dataset.edit); } catch(e) { return; }
    if (!data) return;

    var orig = data.original;
    var prop = data.proposed;

    function fmt(val) {
        if (val === null || val === undefined || val === '') return '—';
        return String(val);
    }

    function diffRow(label, oldVal, newVal) {
        if (JSON.stringify(oldVal) === JSON.stringify(newVal)) return '';
        return '<tr>' +
            '<td style="font-weight:600;color:var(--text-primary);white-space:nowrap">' + label + '</td>' +
            '<td><span class="diff-old">' + fmt(oldVal) + '</span></td>' +
            '<td><span class="diff-new">' + fmt(newVal) + '</span></td>' +
            '</tr>';
    }

    function fmtItems(items) {
        if (!items || !items.length) return '—';
        return items.map(function(i) {
            return (i.name || 'Item #' + i.id) + ' ×' + i.qty + ' @৳' + i.price;
        }).join(', ');
    }

    var rows = '';
    rows += diffRow('তারিখ', orig.sale_date || orig.purchase_date, prop.sale_date || prop.purchase_date);
    rows += diffRow('কাস্টমার/সরবরাহকারী', orig.customer_name || orig.supplier_name, prop.customer_name || prop.supplier_name);
    rows += diffRow('স্ট্যাটাস', orig.status, prop.status);
    rows += diffRow('পরিশোধ মোড', orig.payment_method, prop.payment_method);
    rows += diffRow('পরিশোধ ৳', orig.paid_amount, prop.paid_amount);
    rows += diffRow('ছাড় ৳', orig.discount, prop.discount);
    rows += diffRow('আইটেম', fmtItems(orig.items), fmtItems(prop.items));
    rows += diffRow('নোট', orig.notes, prop.notes);

    if (!rows) {
        rows = '<tr><td colspan="3" style="text-align:center;color:var(--text-secondary);padding:20px">কোনো পরিবর্তন শনাক্ত হয়নি</td></tr>';
    }

    var infoHtml = '<div style="background:var(--surface-2);border-radius:8px;padding:10px 14px;margin-bottom:14px;font-size:.82rem;color:var(--text-secondary)">' +
        '<i class="fas fa-user"></i> <strong>' + (data.staff || '?') + '</strong> &middot; ' + (data.requested_at || '') +
        '</div>';

    var tableHtml = '<table class="diff-table">' +
        '<thead><tr><th>ক্ষেত্র</th><th>আগের মান</th><th>নতুন মান</th></tr></thead>' +
        '<tbody>' + rows + '</tbody></table>';

    document.getElementById('pendingEditModalBody').innerHTML = infoHtml + tableHtml;

    var csrfMeta = document.querySelector('meta[name="csrf-token"]');
    var csrf = csrfMeta ? csrfMeta.getAttribute('content') : '';

    var footer = document.getElementById('pendingEditModalFooter');
    footer.innerHTML =
        '<form method="POST" action="' + rejectUrl + '" style="margin:0">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<button type="submit" class="btn btn-outline" style="color:#ef4444;border-color:#ef4444">' +
                '<i class="fas fa-xmark"></i> বাতিল করুন' +
            '</button>' +
        '</form>' +
        '<form method="POST" action="' + approveUrl + '" style="margin:0">' +
            '<input type="hidden" name="_token" value="' + csrf + '">' +
            '<button type="submit" class="btn btn-primary">' +
                '<i class="fas fa-check"></i> অনুমোদন দিন' +
            '</button>' +
        '</form>';

    var modal = document.getElementById('pendingEditModal');
    modal.style.display = 'flex';
    modal.onclick = function(e) { if (e.target === modal) closePendingEditModal(); };
}

function closePendingEditModal() {
    document.getElementById('pendingEditModal').style.display = 'none';
}
</script>
