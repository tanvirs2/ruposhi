@extends('layouts.app')
@section('title', 'নতুন বিক্রয়')
@section('page-title', 'নতুন বিক্রয়')

@section('content')
<form method="POST" action="{{ route('sales.store') }}" id="saleForm">
@csrf

{{-- Draft restore banner --}}
<div id="draftBanner" style="display:none;align-items:center;gap:12px;flex-wrap:wrap;
     background:#fffbeb;border:1.5px solid #fbbf24;border-radius:10px;
     padding:12px 18px;margin-bottom:16px;font-size:.88rem">
    <i class="fas fa-clock-rotate-left" style="color:#d97706;font-size:1.1rem"></i>
    <span id="draftBannerText" style="flex:1;color:#92400e;font-weight:600"></span>
    <button type="button" onclick="restoreDraftData()"
        style="padding:7px 16px;border-radius:7px;border:none;background:#d97706;
               color:#fff;font-weight:700;cursor:pointer;font-size:.85rem;font-family:inherit">
        <i class="fas fa-rotate-left"></i> পুনরুদ্ধার করুন
    </button>
    <button type="button" onclick="discardDraft()"
        style="padding:7px 14px;border-radius:7px;border:1.5px solid #fbbf24;
               background:transparent;color:#92400e;font-weight:600;cursor:pointer;
               font-size:.85rem;font-family:inherit">
        <i class="fas fa-trash"></i> বাতিল
    </button>
</div>

<div class="pos-grid">

    {{-- Left: Items --}}
    <div class="pos-left">
        <div class="card" style="margin-bottom:16px">
            <div class="card-header"><h3><i class="fas fa-search"></i> আইটেম যোগ করুন</h3></div>
            <div style="padding:16px">
                <div class="search-box" style="margin-bottom:12px">
                    <i class="fas fa-search"></i>
                    <input type="text" id="itemSearch" placeholder="আইটেমের নাম লিখুন...">
                </div>
                <div id="itemSuggestions" class="suggestions-list"></div>
            </div>
        </div>

        <div class="card">
            <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
                <h3><i class="fas fa-list"></i> নির্বাচিত আইটেম</h3>
                <button type="button" id="profitToggleBtn" onclick="toggleProfitCols()" class="btn btn-ghost" style="font-size:.8rem;padding:5px 12px;gap:6px">
                    <i class="fas fa-eye-slash" id="profitToggleIcon"></i>
                    <span id="profitToggleText">লাভ দেখুন</span>
                </button>
            </div>
            <div class="table-wrap">
                <table class="data-table" id="itemsTable">
                    <thead>
                        <tr>
                            <th>আইটেম</th>
                            <th>পরিমাণ</th>
                            <th class="col-secret" style="display:none">ক্রয়মূল্য</th>
                            <th>বিক্রয়মূল্য <small style="font-weight:400;color:#94a3b8">(পরিবর্তনযোগ্য)</small>
                                <button type="button" class="info-btn" data-info="প্রতিটি আইটেমের বিক্রয় মূল্য এখানে পরিবর্তন করা যাবে। ডিফল্ট মূল্য আইটেম সেটআপ থেকে নেওয়া হয়। বিশেষ ছাড় বা দরাদরির ক্ষেত্রে এই ঘরে নতুন মূল্য লিখুন।">i</button>
                            </th>
                            <th class="col-secret" style="display:none">লাভ/বস্তা · মোট</th>
                            <th>মোট</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody">
                        <tr><td colspan="5" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>
                    </tbody>
                    <tfoot id="itemsFoot" style="display:none">
                        <tr class="tfoot-summary">
                            <td style="text-align:right;font-weight:700">সর্বমোট</td>
                            <td class="tc" style="font-weight:800" id="footQty">0</td>
                            <td class="col-secret" style="display:none"></td>
                            <td></td>
                            <td class="col-secret" style="display:none"></td>
                            <td class="tr" style="font-weight:800" id="footTotal">৳ 0</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    {{-- Right: Summary --}}
    <div class="pos-right">
        <div class="card">
            <div class="card-header"><h3><i class="fas fa-calculator"></i> বিক্রয় সারসংক্ষেপ</h3></div>
            <div style="padding:20px;display:flex;flex-direction:column;gap:14px">
                <div class="form-group-field">
                    <label style="display:flex;justify-content:space-between;align-items:center">
                        <span>কাস্টমার
                            <button type="button" class="info-btn" data-info="কাস্টমার নির্বাচন না করলেও বিক্রয় সম্পন্ন করা যাবে। তবে কাস্টমার নির্বাচন করলে বাকির হিসাব সেই কাস্টমারের অ্যাকাউন্টে যোগ হবে।">i</button>
                        </span>
                        <button type="button" onclick="openCustomerModal()"
                            style="font-size:.76rem;font-weight:700;color:var(--accent);background:var(--accent-light);
                                   border:1px solid var(--accent);border-radius:6px;padding:3px 10px;cursor:pointer">
                            <i class="fas fa-plus"></i> নতুন কাস্টমার
                        </button>
                    </label>
                    <input type="hidden" name="customer_id" id="customerIdInput">
                    <input type="text" id="customerSearch" placeholder="নাম লিখুন বা খুঁজুন..."
                        autocomplete="off" style="width:100%">
                    <div id="customerSelected" style="display:none;margin-top:6px;font-size:.85rem"></div>
                </div>
                <div class="form-group-field">
                    <label>তারিখ <span class="req">*</span></label>
                    <input type="date" name="sale_date" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="form-group-field">
                    <label>স্ট্যাটাস</label>
                    <select name="status" class="form-select">
                        <option value="completed">সম্পন্ন</option>
                        <option value="pending">মুলতুবি</option>
                    </select>
                </div>
                <hr style="border:none;border-top:1px solid var(--border)">

                {{-- Previous due — read-only; collected via the single payment field below --}}
                <div id="prevDueRow" style="display:none;justify-content:space-between;align-items:center;padding:10px 14px;background:#fff7ed;border:1px solid #fed7aa;border-radius:8px">
                    <span style="font-size:.83rem;font-weight:600;color:#92400e">
                        <i class="fas fa-clock-rotate-left"></i> পূর্বের বাকী
                    </span>
                    <span style="font-size:1rem;font-weight:800;color:#b45309" id="prevDueDisplay">৳ 0</span>
                </div>

                <div class="summary-row">
                    <span>মোট বিক্রয়:</span>
                    <span style="display:flex;align-items:center;gap:6px;flex-wrap:wrap;justify-content:flex-end">
                        <span id="totalDisplay">৳ 0.00</span>
                        <button type="button" id="discountToggleBtn" onclick="toggleField('discount')"
                            class="cost-toggle-btn" title="ছাড় যোগ করুন">+ ছাড়</button>
                        <button type="button" id="extraCostToggleBtn" onclick="toggleExtraCosts()"
                            class="cost-toggle-btn" title="অতিরিক্ত খরচ যোগ করুন">+ খরচ</button>
                    </span>
                </div>
                <div id="discountRow" style="display:none">
                    <div class="form-group-field" style="margin-bottom:0">
                        <label>ছাড় <span style="font-weight:400;color:#94a3b8">(টাকায়, শতাংশে নয়)</span>
                            <button type="button" class="info-btn" data-info="সম্পূর্ণ বিলের উপর ছাড় দিন। মানটি টাকায় (ফ্ল্যাট), শতাংশে নয়। যেমন ৫০ লিখলে মোট থেকে ৫০ টাকা বাদ যাবে।">i</button>
                        </label>
                        <span class="taka-input-wrap">
                            <input type="text" inputmode="decimal" name="discount" id="discountInput" value="0">
                        </span>
                    </div>
                </div>
                {{-- Categorised extra costs --}}
                <div id="extraRow" style="display:none">
                    <div style="border:1.5px solid #e2e8f0;border-radius:8px;padding:12px 12px 8px;background:#fafafa">
                        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px">
                            <span style="font-size:.82rem;font-weight:700;color:#475569">
                                <i class="fas fa-coins" style="color:#7c3aed"></i> অতিরিক্ত খরচ
                            </span>
                            <a href="{{ route('extra-cost-categories.index') }}" target="_blank"
                               style="font-size:.72rem;color:var(--accent);text-decoration:none">
                                <i class="fas fa-gear"></i> ক্যাটাগরি ম্যানেজ
                            </a>
                        </div>
                        <div id="extraCostRows"></div>
                        <button type="button" onclick="addExtraCostRow()"
                            style="margin-top:6px;width:100%;padding:7px;border:1.5px dashed #c4b5fd;
                                   border-radius:6px;background:transparent;color:#7c3aed;
                                   font-size:.8rem;font-weight:600;cursor:pointer">
                            <i class="fas fa-plus"></i> আরেকটি খরচ যোগ করুন
                        </button>
                    </div>
                </div>
                <div class="summary-row summary-total"><span>নেট মোট:</span><span id="netDisplay">৳ 0.00</span></div>

                {{-- Total to collect today = this sale + outstanding previous due --}}
                <div class="summary-row" id="collectRow" style="display:none;font-weight:700">
                    <span>আজ মোট নিতে হবে:</span><span id="collectDisplay" style="color:#b45309">৳ 0.00</span>
                </div>

                {{-- Single money-in field — system auto-splits between this sale & old due --}}
                <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:10px;padding:12px 14px">
                    <label style="display:block;font-size:.85rem;font-weight:700;color:#1d4ed8;margin-bottom:7px">
                        <i class="fas fa-hand-holding-dollar"></i> আজ গ্রাহক দিচ্ছেন (৳)
                        <button type="button" class="info-btn" data-info="গ্রাহক আজ মোট যত টাকা দিচ্ছেন তা এখানে লিখুন। সিস্টেম স্বয়ংক্রিয়ভাবে এই বিক্রয় ও পুরনো বাকীর মধ্যে ভাগ করে দেবে — আগে এই বিক্রয়, তারপর পুরনো বাকী।">i</button>
                    </label>
                    <div style="display:flex;gap:8px">
                        <input type="text" inputmode="decimal" name="paid_amount" id="paidInput" value="0"
                            style="flex:1;font-size:1.05rem;font-weight:700">
                        <button type="button" onclick="setFullPay()" title="সম্পূর্ণ পরিশোধ"
                            style="padding:0 14px;border-radius:var(--radius-sm);border:none;
                                   background:#1d4ed8;color:#fff;font-size:.82rem;
                                   font-weight:700;cursor:pointer;white-space:nowrap">
                            পুরোটা
                        </button>
                    </div>
                    <div id="paidWords" style="display:none;margin-top:5px;font-size:.78rem;font-weight:600;color:#1d4ed8"></div>

                    {{-- Auto-split breakdown (read-only) --}}
                    <div id="payBreakdown" style="display:none;margin-top:9px;font-size:.8rem;color:#1e40af;font-weight:600">
                        <span id="breakSale"></span><span id="breakOld"></span>
                    </div>
                    {{-- Resulting customer balance --}}
                    <div id="resultRow" style="display:none;margin-top:6px;font-size:.83rem;font-weight:700"></div>
                </div>

                {{-- Walk-in warning --}}
                <div id="walkinWarning" style="display:none;margin-top:2px;padding:8px 12px;
                    background:#fee2e2;border:1px solid #fecaca;border-radius:8px;
                    font-size:.82rem;color:#991b1b;font-weight:600">
                    <i class="fas fa-circle-exclamation"></i>
                    কাস্টমার ছাড়া বিক্রয়ে সম্পূর্ণ পরিশোধ আবশ্যক!
                </div>
                {{-- Extra payment warning (no items + customer has 0 due) --}}
                <div id="extraPayWarning" style="display:none;margin-top:2px;padding:8px 12px;
                    background:#fef9c3;border:1px solid #fde68a;border-radius:8px;
                    font-size:.82rem;color:#92400e;font-weight:600">
                    <i class="fas fa-triangle-exclamation"></i>
                    এই কাস্টমারের কোনো বাকী নেই — অতিরিক্ত পরিশোধ রিপোর্টে বাকীর হিসাবে প্রভাব ফেলবে না।
                </div>
                {{-- This sale's own outstanding (kept for clarity) --}}
                <div class="summary-row" id="saleDueRow" style="color:#ef4444"><span>এই বিক্রয়ে বাকী:</span><span id="dueDisplay">৳ 0.00</span></div>

                {{-- Payment Method (DB-driven) --}}
                <div class="form-group-field">
                    <label><i class="fas fa-credit-card" style="color:var(--accent)"></i> পরিশোধ মোড</label>
                    <select name="payment_method" id="paymentMethod" class="form-select">
                        @foreach($paymentMethods as $group => $names)
                        <optgroup label="— {{ $group }} —">
                            @foreach($names as $i => $name)
                            <option value="{{ $name }}" @if($group === array_key_first($paymentMethods) && $i === 0) selected @endif>
                                {{ $name }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endforeach
                    </select>
                    <div style="margin-top:5px;font-size:.77rem;color:#94a3b8">
                        <a href="{{ route('store-config.index') }}" target="_blank"
                            style="color:var(--accent);text-decoration:none;font-weight:500">
                            <i class="fas fa-plus-circle"></i> নতুন পদ্ধতি যোগ করুন (স্টোর কনফিগ)
                        </a>
                    </div>
                </div>

                <hr style="border:none;border-top:1px solid var(--border)">

                {{-- Profit panel (hidden until toggle) --}}
                <div class="profit-panel" id="profitPanel" style="display:none">
                    <div class="profit-panel-header">
                        <i class="fas fa-chart-line"></i> লাভের বিবরণ
                    </div>
                    <div class="profit-panel-body">
                        <div class="profit-row"><span>মোট খরচ (ক্রয়):</span><span id="costDisplay">৳ 0.00</span></div>
                        <div class="profit-row"><span>মোট আয় (বিক্রয়):</span><span id="revenueDisplay">৳ 0.00</span></div>
                        <div class="profit-row profit-net">
                            <span>আনুমানিক লাভ:</span>
                            <span id="profitDisplay">৳ 0.00</span>
                        </div>
                        <div style="margin-top:8px;text-align:center">
                            <span class="margin-badge" id="marginBadge">—</span>
                            <span id="marginPct" style="font-size:.8rem;color:#64748b;margin-left:6px"></span>
                        </div>
                    </div>
                </div>

                <div class="form-group-field">
                    <label>মন্তব্য</label>
                    <textarea name="notes" rows="2"></textarea>
                </div>
                {{-- spacer so content doesn't hide behind the fixed submit bar (height synced in JS) --}}
                <div id="submitBarSpacer" style="height:150px"></div>
            </div>
        </div>
    </div>
</div>

{{-- Floating submit button ──────────────────────────────── --}}
<div class="sale-submit-bar">
    {{-- Post-transaction summary — always visible directly above the CTA --}}
    <div id="txnSummary" style="display:none;border:1.5px solid var(--border);border-radius:10px;
         padding:8px 12px;margin-bottom:10px;background:var(--bg)">
        <div style="display:flex;justify-content:space-between;align-items:center;
                    font-size:.8rem;color:var(--text-secondary);margin-bottom:5px">
            <span><i class="fas fa-wallet" style="margin-right:4px"></i> আজ মোট জমা</span>
            <span id="txnCollected" style="font-weight:800;color:#16a34a">৳ 0</span>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;
                    border-top:1px dashed var(--border);padding-top:6px">
            <span style="font-size:.84rem;font-weight:700;color:var(--text-primary)">লেনদেনের পর সর্বমোট বাকী</span>
            <span id="txnDueAfter" style="font-size:1.2rem;font-weight:800;color:#dc2626">৳ 0</span>
        </div>
    </div>
    <button type="submit" form="saleForm" class="btn btn-primary"
            style="width:100%;justify-content:center;padding:14px;font-size:1rem">
        <i class="fas fa-check-circle"></i> বিক্রয় সম্পন্ন করুন
    </button>
</div>
</form>

{{-- ── নতুন কাস্টমার Popup Modal ───────────────────────────── --}}
<div id="customerModal" class="cust-modal-overlay" style="display:none">
    <div class="cust-modal">
        <div class="cust-modal-head">
            <h3><i class="fas fa-user-plus"></i> নতুন কাস্টমার যোগ করুন</h3>
            <button type="button" onclick="closeCustomerModal()" class="cust-modal-close">&times;</button>
        </div>
        <div class="cust-modal-body">
            <div id="custModalError" style="display:none;background:#fee2e2;color:#dc2626;padding:8px 12px;border-radius:6px;font-size:.84rem;margin-bottom:10px"></div>
            <div class="form-group-field">
                <label>প্রতিষ্ঠানের নাম <span class="req">*</span></label>
                <input type="text" id="cmName" placeholder="মেসার্স মোল্লা স্টোর" autocomplete="off">
            </div>
            <div class="form-group-field">
                <label>প্রোপ্রাইটরের নাম</label>
                <input type="text" id="cmProprietor" placeholder="মোঃ হুমায়ন মোল্লা" autocomplete="off">
            </div>
            <div class="form-group-field">
                <label>ফোন নম্বর</label>
                <input type="text" id="cmPhone" inputmode="numeric" autocomplete="off">
            </div>
            <div class="form-group-field">
                <label>এরিয়া</label>
                <select id="cmArea" class="form-select">
                    <option value="">এরিয়া নির্বাচন করুন</option>
                    @foreach($areas as $area)
                        <option value="{{ $area->id }}">{{ $area->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="form-group-field">
                <label>ঠিকানা</label>
                <textarea id="cmAddress" rows="2"></textarea>
            </div>
        </div>
        <div class="cust-modal-foot">
            <button type="button" onclick="closeCustomerModal()" class="btn btn-ghost">বাতিল</button>
            <button type="button" id="cmSaveBtn" onclick="saveNewCustomer()" class="btn btn-primary">
                <i class="fas fa-save"></i> সংরক্ষণ ও নির্বাচন
            </button>
        </div>
    </div>
</div>

@push('styles')
<style>
/* Cart table — compact rows */
#itemsTable th { height: 32px; padding: 0 10px; font-size: .68rem; }
#itemsTable td { height: 36px; padding: 0 10px; font-size: .85rem; }

/* ── নতুন কাস্টমার Modal ───────────────────────────────── */
.cust-modal-overlay {
    position: fixed; inset: 0; z-index: 9999;
    background: rgba(15, 23, 42, .55);
    display: flex; align-items: center; justify-content: center;
    padding: 16px;
    animation: custFade .15s ease;
}
@keyframes custFade { from { opacity: 0 } to { opacity: 1 } }
.cust-modal {
    background: var(--surface, #fff);
    border-radius: 14px;
    width: 100%; max-width: 460px;
    max-height: 92vh; overflow-y: auto;
    box-shadow: 0 20px 60px rgba(0,0,0,.3);
    animation: custSlide .2s ease;
}
@keyframes custSlide { from { transform: translateY(14px); opacity: .6 } to { transform: translateY(0); opacity: 1 } }
.cust-modal-head {
    display: flex; align-items: center; justify-content: space-between;
    padding: 16px 20px; border-bottom: 1px solid var(--border, #e2e8f0);
}
.cust-modal-head h3 { margin: 0; font-size: 1.02rem; color: var(--text-primary, #0f172a); }
.cust-modal-close {
    background: none; border: none; font-size: 1.6rem; line-height: 1;
    color: #94a3b8; cursor: pointer; padding: 0 4px;
}
.cust-modal-close:hover { color: #dc2626; }
.cust-modal-body { padding: 16px 20px; display: flex; flex-direction: column; gap: 12px; }
.cust-modal-body input, .cust-modal-body textarea, .cust-modal-body select {
    width: 100%; padding: 9px 12px;
    border: 1.5px solid var(--border, #e2e8f0); border-radius: 8px;
    font-family: inherit; font-size: .9rem; outline: none;
    background: var(--surface, #fff); color: var(--text-primary, #0f172a);
}
.cust-modal-body input:focus, .cust-modal-body textarea:focus, .cust-modal-body select:focus {
    border-color: var(--accent);
}
.cust-modal-body label { font-size: .82rem; font-weight: 600; color: var(--text-secondary, #475569); margin-bottom: 4px; display: block; }
.cust-modal-foot {
    display: flex; gap: 10px; justify-content: flex-end;
    padding: 14px 20px; border-top: 1px solid var(--border, #e2e8f0);
}

/* Profit column coloring */
.profit-good  { color: #16a34a; font-weight: 600; }
.profit-med   { color: #d97706; font-weight: 600; }
.profit-poor  { color: #dc2626; font-weight: 600; }

/* Margin badge */
.margin-badge {
    display:inline-block;
    padding:3px 12px;
    border-radius:20px;
    font-size:.8rem;
    font-weight:600;
    letter-spacing:.03em;
}
.margin-badge.good   { background:#dcfce7; color:#15803d; }
.margin-badge.med    { background:#fef9c3; color:#92400e; }
.margin-badge.poor   { background:#fee2e2; color:#b91c1c; }

/* Profit summary panel */
.profit-panel {
    border:1px solid var(--border);
    border-radius:10px;
    overflow:hidden;
}
.profit-panel-header {
    background:var(--bg);
    padding:10px 14px;
    font-size:.85rem;
    font-weight:600;
    color:var(--text-secondary);
    display:flex;
    align-items:center;
    gap:6px;
}
.profit-panel-body {
    padding:12px 14px;
    display:flex;
    flex-direction:column;
    gap:8px;
}
.profit-row {
    display:flex;
    justify-content:space-between;
    font-size:.88rem;
    color:var(--text-secondary);
}
.profit-row.profit-net {
    font-size:.95rem;
    font-weight:700;
    color:var(--text);
    border-top:1px dashed var(--border);
    padding-top:8px;
    margin-top:2px;
}

/* Cost/discount toggle buttons */
.cost-toggle-btn {
    font-size:.72rem;
    padding:2px 8px;
    border-radius:20px;
    border:1.5px dashed #94a3b8;
    background:transparent;
    color:#94a3b8;
    cursor:pointer;
    white-space:nowrap;
    font-weight:600;
    line-height:1.5;
    transition:all .2s;
}
.cost-toggle-btn:hover { color:var(--accent); border-color:var(--accent); }
.cost-toggle-btn.active { color:#ef4444; border-color:#fca5a5; border-style:solid; }

/* Cost hint under search suggestion */
.suggestion-item .cost-hint {
    font-size:.75rem;
    color:#94a3b8;
    margin-top:1px;
}

/* ৳ flat-amount adornment for ছাড় / খরচ inputs */
.taka-input-wrap { position: relative; display: block; }
.taka-input-wrap::before {
    content: '৳';
    position: absolute; left: 10px; top: 50%;
    transform: translateY(-50%);
    color: #94a3b8; font-weight: 700; font-size: .88rem;
    pointer-events: none; z-index: 1;
}
.taka-input-wrap > input { padding-left: 22px !important; }
</style>
@endpush

@push('scripts')
<script>
// ── Floating dropdown helper ─────────────────────────────────
// Attaches a fixed-position suggestion popup to any input element,
// so it always floats above parent overflow/card constraints.
function makeFloatingDropdown(inputEl, dropEl) {
    document.body.appendChild(dropEl);
    dropEl.style.cssText = `
        position:fixed;display:none;z-index:9999;
        background:#fff;border:1px solid #e2e8f0;
        border-radius:8px;box-shadow:0 8px 24px rgba(0,0,0,.12);
        overflow-y:auto;max-height:240px;
    `;

    function position() {
        const r = inputEl.getBoundingClientRect();
        dropEl.style.top   = (r.bottom + 4) + 'px';
        dropEl.style.left  = r.left + 'px';
        dropEl.style.width = r.width + 'px';
    }

    function show(html) {
        dropEl.innerHTML = html;
        position();
        dropEl.style.display = 'block';
    }

    function hide() {
        dropEl.style.display = 'none';
        dropEl.innerHTML = '';
    }

    window.addEventListener('scroll', position, true);
    window.addEventListener('resize', position);
    document.addEventListener('click', function(e) {
        if (!inputEl.contains(e.target) && !dropEl.contains(e.target)) hide();
    });

    return { show, hide, position };
}

// ── Customer search ──────────────────────────────────────────
// allCustomers is now a client-side CACHE, not the whole table.
// It is seeded with the pre-selected customer (if any) and grows as the
// user searches (server-side) or adds a customer via the popup.
let allCustomers      = [];
@if(!empty($preCustomer))
allCustomers.push(@json($preCustomer));
@endif
const customerSearch  = document.getElementById('customerSearch');
const customerIdInput = document.getElementById('customerIdInput');
const customerSelected= document.getElementById('customerSelected');
const prevDueRow      = document.getElementById('prevDueRow');
const prevDueDisplay  = document.getElementById('prevDueDisplay');
const customerDrop    = document.createElement('div');
const cDrop           = makeFloatingDropdown(customerSearch, customerDrop);
let currentPrevDue = 0;
let prevDuePay     = 0;   // how much of prev due customer will pay this time

let _custSearchTimer = null;
customerSearch.addEventListener('input', function() {
    const q = this.value.trim();
    if (!q) {
        cDrop.hide();
        customerIdInput.value = '';
        customerSelected.style.display = 'none';
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
        currentPrevDue = 0;
        document.getElementById('walkinWarning').style.display = 'none';
        return;
    }
    cDrop.show(`<div class="suggestion-item" style="color:#94a3b8">খুঁজছি…</div>`);
    clearTimeout(_custSearchTimer);
    _custSearchTimer = setTimeout(() => fetchCustomers(q), 250);
});

async function fetchCustomers(q) {
    try {
        const res = await fetch(`{{ route('customers.search') }}?q=${encodeURIComponent(q)}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        });
        const matches = await res.json();
        // Cache results so selectCustomer()/draft/pre-select can find them
        matches.forEach(c => { if (!allCustomers.find(x => x.id === c.id)) allCustomers.push(c); });
        renderCustomerMatches(matches);
    } catch (_) {
        cDrop.show(`<div class="suggestion-item" style="color:#94a3b8">খুঁজতে সমস্যা হয়েছে</div>`);
    }
}

function renderCustomerMatches(matches) {
    const html = matches.map(c => `
        <div class="suggestion-item" onclick="selectCustomer(${c.id})">
            <div style="display:flex;justify-content:space-between;align-items:center;gap:8px">
                <span>
                    ${c.proprietor
                        ? `<strong style="font-size:.92rem">${c.proprietor}</strong><span style="font-size:.78rem;color:#64748b;margin-left:6px">${c.name}</span>`
                        : `<strong style="font-size:.92rem">${c.name}</strong>`}
                </span>
                ${parseFloat(c.due_amount) > 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#dc2626;background:#fee2e2;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বাকী: ৳${parseFloat(c.due_amount).toLocaleString()}</span>`
                    : parseFloat(c.due_amount) < 0
                    ? `<span style="font-size:.78rem;font-weight:700;color:#1d4ed8;background:#eff6ff;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">অগ্রিম: ৳${Math.abs(parseFloat(c.due_amount)).toLocaleString()}</span>`
                    : `<span style="font-size:.78rem;font-weight:700;color:#16a34a;background:#dcfce7;padding:2px 8px;border-radius:20px;white-space:nowrap;flex-shrink:0">বাকীমুক্ত ✓</span>`}
            </div>
            <span style="font-size:.76rem;color:#94a3b8;display:block;margin-top:2px">
                ${c.phone ? `📞 ${c.phone}` : ''}${c.phone && c.area?.name ? ' &nbsp;·&nbsp; ' : ''}${c.area?.name ? `📍 ${c.area.name}` : ''}
            </span>
        </div>
    `).join('') || `<div class="suggestion-item" style="color:#94a3b8">কোনো কাস্টমার পাওয়া যায়নি</div>`;
    cDrop.show(html);
}

function selectCustomer(id) {
    const c = allCustomers.find(x => x.id === id);
    if (!c) return;

    customerIdInput.value = c.id;
    // Always show প্রতিষ্ঠান name in the search input
    customerSearch.value  = c.name;

    // Confirmation box below the input
    let html = `<div style="font-weight:700;color:#0d9488;font-size:.9rem">✓ ${c.name}</div>`;
    if (c.proprietor) {
        html += `<div style="font-size:.8rem;color:#475569;margin-top:1px">প্রোঃ ${c.proprietor}</div>`;
    }
    if (c.phone) {
        html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📞 ${c.phone}</div>`;
    }
    if (c.area?.name) {
        html += `<div style="font-size:.78rem;color:#94a3b8;margin-top:1px">📍 ${c.area.name}</div>`;
    }

    const due = parseFloat(c.due_amount) || 0;
    if (due > 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#fee2e2;border:1px solid #fecaca;
                             border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#991b1b;font-size:.83rem;font-weight:600">
                        <i class="fas fa-triangle-exclamation"></i> আগের বাকী আছে
                    </span>
                    <span style="color:#dc2626;font-size:1rem;font-weight:700">৳ ${due.toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
        prevDueDisplay.textContent = '৳ ' + due.toLocaleString('en', {minimumFractionDigits:2});
        prevDueRow.style.display = 'flex';
    } else if (due < 0) {
        html += `<div style="margin-top:6px;padding:8px 12px;background:#eff6ff;border:1px solid #bfdbfe;
                             border-radius:8px;display:flex;justify-content:space-between;align-items:center">
                    <span style="color:#1d4ed8;font-size:.83rem;font-weight:600">
                        <i class="fas fa-piggy-bank"></i> অগ্রিম পরিশোধ আছে
                    </span>
                    <span style="color:#1d4ed8;font-size:1rem;font-weight:700">৳ ${Math.abs(due).toLocaleString('en', {minimumFractionDigits:2})}</span>
                 </div>`;
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
    } else {
        html += `<div style="margin-top:6px;padding:6px 12px;background:#dcfce7;border:1px solid #bbf7d0;
                             border-radius:8px;font-size:.8rem;color:#15803d;font-weight:600">
                    ✓ কোনো বাকী নেই
                 </div>`;
        prevDueRow.style.display = 'none';
        resetPrevDuePay();
    }

    // Reset partial pay input whenever customer changes
    resetPrevDuePay();
    currentPrevDue = due;

    customerSelected.innerHTML = html;
    customerSelected.style.display = 'block';
    cDrop.hide();
}

// ── Items ────────────────────────────────────────────────────
const allItems = @json($items);
let cart = [];

const itemSearch    = document.getElementById('itemSearch');
const suggestions   = document.getElementById('itemSuggestions');
const itemsBody     = document.getElementById('itemsBody');
const profitPanel   = document.getElementById('profitPanel');

itemSearch.addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    if (!q) { suggestions.innerHTML = ''; return; }
    const matches = allItems.filter(i => i.name.toLowerCase().includes(q)).slice(0, 6);
    suggestions.innerHTML = matches.map(i => {
        const avail  = i.stock ? parseFloat(i.stock.quantity) : 0;
        const minQty = i.stock ? parseFloat(i.stock.min_quantity) : 0;
        const stockColor = avail <= 0 ? '#dc2626' : avail <= minQty ? '#d97706' : '#16a34a';
        const stockLabel = avail <= 0 ? '✗ স্টক নেই' : `স্টক: ${avail}`;
        const dimmed     = avail <= 0 ? 'opacity:.55;cursor:not-allowed' : '';
        return `<div class="suggestion-item" onclick="addItem(${i.id})" style="${dimmed}">
            <div style="display:flex;justify-content:space-between;align-items:center">
                <strong>${i.name}</strong>
                <span style="font-size:.78rem;font-weight:700;color:${stockColor};background:${avail<=0?'#fee2e2':avail<=minQty?'#fef9c3':'#dcfce7'};padding:2px 8px;border-radius:20px">${stockLabel}</span>
            </div>
            <div style="display:flex;gap:12px;margin-top:2px">
                <span style="font-size:.8rem;color:#64748b">বিক্রয়: ৳${parseFloat(i.sale_price).toLocaleString()}</span>
                ${profitVisible ? `<span style="font-size:.8rem;color:#94a3b8">ক্রয়: ৳${parseFloat(i.purchase_price).toLocaleString()}</span>` : ''}
            </div>
        </div>`;
    }).join('') || '<div class="suggestion-item" style="color:#94a3b8">কোনো আইটেম পাওয়া যায়নি</div>';
});

function addItem(id) {
    const item   = allItems.find(i => i.id === id);
    if (!item) return;
    const avail  = item.stock ? parseFloat(item.stock.quantity) : 0;

    const existing = cart.find(c => c.id === id);
    if (existing) {
        existing.qty++;
        // Warn when crossing into over-stock territory
        if (existing.qty > avail) {
            showStockToast(`⚠ "${item.name}" — স্টকে মাত্র ${avail} টি আছে, তবে বিক্রয় করা যাবে।`, 'warn');
        }
    } else {
        if (avail <= 0) {
            showStockToast(`⚠ "${item.name}" — স্টকে কোনো পণ্য নেই, তবে বিক্রয় করা যাবে।`, 'warn');
        }
        cart.push({
            id:           item.id,
            name:         item.name,
            cost:         parseFloat(item.purchase_price),
            price:        0,
            priceEntered: false,
            defaultPrice: parseFloat(item.sale_price),
            qty:          1,
            stock:        avail
        });
    }
    suggestions.innerHTML = '';
    itemSearch.value = '';
    renderCart();
}

function removeItem(id) {
    cart = cart.filter(c => c.id !== id);
    renderCart();
}

function updateQty(id, val) {
    const item = cart.find(c => c.id === id);
    if (!item) return;
    const newQty = parseFloat(toEnglishDigits(val)) || 0;
    item.qty = newQty;
    // Warn if over-stocking
    const badge = document.getElementById('stock-badge-' + id);
    if (badge) {
        if (newQty > item.stock) {
            badge.textContent  = '⚠ স্টক: ' + item.stock;
            badge.style.background = '#fef9c3';
            badge.style.color      = '#92400e';
        } else {
            badge.textContent  = 'স্টক: ' + item.stock;
            badge.style.background = '#dcfce7';
            badge.style.color      = '#15803d';
        }
    }
    updateRowTotal(id);
    updateSummary();
}

function updatePrice(id, val) {
    const item = cart.find(c => c.id === id);
    if (item) {
        item.price = parseFloat(toEnglishDigits(val)) || 0;
        item.priceEntered = val.trim() !== '';
    }
    updateRowTotal(id);
    updateSummary();
}

// Update only the row-total and profit cells — no full re-render, focus stays intact
function updateRowTotal(id) {
    const item = cart.find(c => c.id === id);
    if (!item) return;

    // Update total cell
    const cell = document.getElementById('row-total-' + id);
    if (cell) cell.textContent = '৳ ' + (item.qty * item.price).toFixed(0);

    // Update profit cell (price changes after render so must update separately)
    const profitCell = document.getElementById('row-profit-' + id);
    if (profitCell) {
        const profitPerUnit  = item.price - item.cost;
        const profitTotal    = profitPerUnit * (item.qty || 0);
        const pClass         = profitClass(profitPerUnit, item.cost);
        const sign           = v => v >= 0 ? '+৳' : '-৳';
        profitCell.className     = `col-secret ${pClass}`;
        profitCell.style.display = profitVisible ? '' : 'none';
        profitCell.innerHTML     = `${sign(profitPerUnit)}${Math.abs(profitPerUnit).toFixed(0)}<small style="display:block;font-size:.72rem;font-weight:500;opacity:.75">${sign(profitTotal)}${Math.abs(profitTotal).toFixed(0)} মোট</small>`;
    }

    // ── Loss warning per row ──────────────────────────────────
    const lossWarn = document.getElementById('loss-warn-' + id);
    if (lossWarn) {
        const isLoss = item.priceEntered && item.cost > 0 && item.price < item.cost;
        lossWarn.style.display = isLoss ? 'inline-flex' : 'none';
    }

    // ── Excessive profit warning per row (>25% margin) ────────
    const EXCESS_PCT = 25;
    const excessWarn = document.getElementById('excess-warn-' + id);
    if (excessWarn) {
        const pct = item.cost > 0 ? (item.price - item.cost) / item.cost * 100 : 0;
        const isExcess = item.priceEntered && item.cost > 0 && pct > EXCESS_PCT;
        excessWarn.style.display = isExcess ? 'inline-flex' : 'none';
        if (isExcess) excessWarn.title = `লাভ: ${pct.toFixed(1)}%`;
    }
}

function profitClass(profit, cost) {
    if (cost <= 0) return 'profit-good';
    const pct = profit / cost * 100;
    if (pct >= 8)  return 'profit-good';
    if (pct >= 4)  return 'profit-med';
    return 'profit-poor';
}

let profitVisible = false;

function toggleProfitCols() {
    profitVisible = !profitVisible;
    document.querySelectorAll('.col-secret').forEach(el => {
        el.style.display = profitVisible ? '' : 'none';
    });
    document.getElementById('profitToggleIcon').className = profitVisible ? 'fas fa-eye-slash' : 'fas fa-eye';
    document.getElementById('profitToggleText').textContent = profitVisible ? 'লাভ লুকান' : 'লাভ দেখুন';
    if (profitPanel) profitPanel.style.display = profitVisible && cart.length ? 'block' : 'none';
}

function emptyColspan() {
    return profitVisible ? 7 : 5;
}

function renderCart() {
    scheduleDraftSave();
    const itemsFoot = document.getElementById('itemsFoot');
    if (!cart.length) {
        itemsBody.innerHTML = `<tr><td colspan="${emptyColspan()}" class="empty-row">কোনো আইটেম যোগ করা হয়নি</td></tr>`;
        if (profitPanel) profitPanel.style.display = 'none';
        if (itemsFoot)  itemsFoot.style.display  = 'none';
        updateSummary();
        return;
    }
    if (itemsFoot) itemsFoot.style.display = '';

    itemsBody.innerHTML = cart.map((c, idx) => {
        const profitPerUnit  = c.price - c.cost;
        const profitTotal    = profitPerUnit * (c.qty || 0);
        const pClass         = profitClass(profitPerUnit, c.cost);
        const sign           = v => v >= 0 ? '+৳' : '-৳';
        const profitStr      = `${sign(profitPerUnit)}${Math.abs(profitPerUnit).toFixed(0)}<small style="display:block;font-size:.72rem;font-weight:500;opacity:.75">${sign(profitTotal)}${Math.abs(profitTotal).toFixed(0)} মোট</small>`;
        const secretDisplay = profitVisible ? '' : 'display:none';

        const overStock  = c.qty > c.stock;
        const stockBg    = overStock ? '#fef9c3' : '#dcfce7';
        const stockClr   = overStock ? '#92400e' : '#15803d';
        const stockTxt   = (overStock ? '⚠ স্টক: ' : 'স্টক: ') + (c.stock ?? '?');

        return `<tr>
            <td style="white-space:nowrap">
                ${c.name}
                <span id="stock-badge-${c.id}" style="font-size:.68rem;font-weight:700;background:${stockBg};color:${stockClr};padding:1px 6px;border-radius:20px;display:inline-block;margin-left:5px;vertical-align:middle">${stockTxt}</span>
                <input type="hidden" name="items[${idx}][id]" value="${c.id}">
            </td>
            <td>
                <input type="text" inputmode="decimal" name="items[${idx}][qty]" value="${c.qty}"
                    style="width:70px"
                    oninput="updateQty(${c.id},this.value)" class="inline-input">
            </td>
            <td class="col-secret" style="color:#94a3b8;font-size:.88rem;${secretDisplay}">৳ ${c.cost.toLocaleString()}</td>
            <td>
                <input type="text" inputmode="decimal" name="items[${idx}][price]" value="${c.priceEntered ? c.price : ''}"
                    style="width:100px"
                    oninput="updatePrice(${c.id},this.value)" class="inline-input">
                <span id="loss-warn-${c.id}"
                    style="display:${c.priceEntered && c.cost > 0 && c.price < c.cost ? 'inline-flex' : 'none'};
                           align-items:center;gap:3px;margin-left:5px;
                           font-size:.72rem;font-weight:700;color:#b91c1c;
                           background:#fee2e2;border:1px solid #fca5a5;
                           border-radius:20px;padding:1px 7px;vertical-align:middle;white-space:nowrap">
                    ⚠ লোকসান!
                </span>
                ${(()=>{ const pct=c.cost>0?(c.price-c.cost)/c.cost*100:0; const isEx=c.priceEntered&&c.cost>0&&pct>25; return `<span id="excess-warn-${c.id}"
                    title="${isEx?`লাভ: ${pct.toFixed(1)}%`:''}"
                    style="display:${isEx?'inline-flex':'none'};
                           align-items:center;gap:3px;margin-left:5px;
                           font-size:.72rem;font-weight:700;color:#92400e;
                           background:#fef9c3;border:1px solid #fde68a;
                           border-radius:20px;padding:1px 7px;vertical-align:middle;white-space:nowrap">
                    ⚠ অতিরিক্ত লাভ!
                </span>`; })()}
            </td>
            <td id="row-profit-${c.id}" class="col-secret ${pClass}" style="${secretDisplay}">${profitStr}</td>
            <td id="row-total-${c.id}">৳ ${(c.qty * c.price).toFixed(0)}</td>
            <td><button type="button" onclick="removeItem(${c.id})" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button></td>
        </tr>`;
    }).join('');

    if (profitPanel) profitPanel.style.display = profitVisible ? 'block' : 'none';
    // Re-attach Bengali converter to newly rendered inputs
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(itemsBody);
    updateSummary();
}

function updateSummary() {
    const totalQty = cart.reduce((s, c) => s + (parseFloat(c.qty) || 0), 0);
    const total    = cart.reduce((s, c) => s + c.qty * c.price, 0);
    const totalCost= cart.reduce((s, c) => s + c.qty * c.cost,  0);
    const discount = parseFloat(toEnglishDigits(document.getElementById('discountInput').value)) || 0;
    const extra    = getExtraCostTotal();
    const net      = Math.max(0, total - discount + extra);
    const paid     = parseFloat(toEnglishDigits(document.getElementById('paidInput').value)) || 0;
    const due      = Math.max(0, net - paid);
    const profit   = net - totalCost - extra;  // extras don't count as profit
    const marginPct= totalCost > 0 ? (profit / totalCost * 100) : 0;

    // Update cart tfoot totals
    const footQty   = document.getElementById('footQty');
    const footTotal = document.getElementById('footTotal');
    if (footQty)   footQty.textContent   = totalQty.toString();
    if (footTotal) footTotal.textContent = '৳ ' + total.toFixed(0);

    document.getElementById('totalDisplay').textContent   = '৳ ' + total.toFixed(2);
    document.getElementById('netDisplay').textContent     = '৳ ' + net.toFixed(2);
    document.getElementById('dueDisplay').textContent     = '৳ ' + due.toFixed(2);

    // profit panel
    document.getElementById('costDisplay').textContent    = '৳ ' + totalCost.toFixed(2);
    document.getElementById('revenueDisplay').textContent = '৳ ' + net.toFixed(2);
    document.getElementById('profitDisplay').textContent  = (profit >= 0 ? '+' : '') + '৳ ' + profit.toFixed(2);
    document.getElementById('profitDisplay').style.color  = profit >= 0 ? '#16a34a' : '#dc2626';

    const badge = document.getElementById('marginBadge');
    const pctEl = document.getElementById('marginPct');
    if (cart.length) {
        pctEl.textContent = `(${marginPct >= 0 ? '+' : ''}${marginPct.toFixed(1)}%)`;
        if (marginPct >= 8)      { badge.textContent = '✓ ভালো লাভ';    badge.className = 'margin-badge good'; }
        else if (marginPct >= 4) { badge.textContent = '~ মধ্যম লাভ';   badge.className = 'margin-badge med';  }
        else if (marginPct >= 0) { badge.textContent = '↓ কম লাভ';      badge.className = 'margin-badge poor'; }
        else                     { badge.textContent = '✗ লোকসান';       badge.className = 'margin-badge poor'; }
    }

    // ── Payment split: one input → this sale first, then old due ──
    const owed      = Math.max(0, currentPrevDue);     // outstanding old due (ignore advance)
    const toCollect = net + owed;                       // amount to clear everything
    const toSale    = Math.min(paid, net);
    const toOld     = Math.max(0, paid - net);
    const resultBal = currentPrevDue + net - paid;      // customer balance after this sale

    const collectRow = document.getElementById('collectRow');
    if (collectRow) {
        collectRow.style.display = owed > 0 ? 'flex' : 'none';
        if (owed > 0) document.getElementById('collectDisplay').textContent = '৳ ' + toCollect.toFixed(0);
    }

    const bd = document.getElementById('payBreakdown');
    if (bd) {
        if (paid > 0 && (cart.length || owed > 0)) {
            bd.style.display = 'block';
            document.getElementById('breakSale').textContent = `এই বিক্রয়ে ৳ ${toSale.toFixed(0)}`;
            document.getElementById('breakOld').textContent  = toOld > 0 ? `  •  পুরনো বাকীতে ৳ ${toOld.toFixed(0)}` : '';
        } else {
            bd.style.display = 'none';
        }
    }

    const rr = document.getElementById('resultRow');
    if (rr) {
        if (cart.length || owed > 0 || paid > 0) {
            rr.style.display = 'block';
            if (Math.abs(resultBal) < 0.005) {
                rr.innerHTML = `<span style="color:#15803d">✓ সম্পূর্ণ পরিশোধিত — কোনো বাকী থাকবে না</span>`;
            } else if (resultBal > 0) {
                rr.innerHTML = `<span style="color:#dc2626">বাকী থাকবে: ৳ ${resultBal.toFixed(0)}</span>`;
            } else {
                rr.innerHTML = `<span style="color:#1d4ed8">অগ্রিম জমা থাকবে: ৳ ${Math.abs(resultBal).toFixed(0)}</span>`;
            }
        } else {
            rr.style.display = 'none';
        }
    }

    // Hide the standalone "এই বিক্রয়ে বাকী" row when there's nothing to show
    const saleDueRow = document.getElementById('saleDueRow');
    if (saleDueRow) saleDueRow.style.display = (cart.length && due > 0) ? 'flex' : 'none';

    // ── Post-transaction summary above the submit CTA ─────────
    // total_due_after = (previous_due + net) − total_collected
    // where total_collected = the single "আজ গ্রাহক দিচ্ছেন" amount (paid)
    const txn = document.getElementById('txnSummary');
    if (txn) {
        const active = cart.length || owed > 0 || paid > 0;
        txn.style.display = active ? 'block' : 'none';
        if (active) {
            document.getElementById('txnCollected').textContent = '৳ ' + paid.toFixed(0);
            const after = document.getElementById('txnDueAfter');
            if (resultBal > 0.005) {
                after.textContent = '৳ ' + resultBal.toFixed(0);
                after.style.color = '#dc2626';      // red — still owes
            } else if (resultBal < -0.005) {
                after.textContent = 'অগ্রিম ৳ ' + Math.abs(resultBal).toFixed(0);
                after.style.color = '#1d4ed8';      // blue — credit balance
            } else {
                after.textContent = '৳ 0';
                after.style.color = '#16a34a';      // green — fully settled
            }
        }
    }

    syncSubmitBarSpacer();
}

// ── Categorised extra costs ──────────────────────────────────
const extraCategories = @json($extraCategories);
let extraCostRowCount = 0;

function getExtraCostTotal() {
    let total = 0;
    document.querySelectorAll('.extra-cost-amount').forEach(inp => {
        total += parseFloat(toEnglishDigits(inp.value)) || 0;
    });
    return total;
}

function toggleExtraCosts() {
    const row = document.getElementById('extraRow');
    const btn = document.getElementById('extraCostToggleBtn');
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent   = open ? '✕ খরচ' : '+ খরচ';
    btn.classList.toggle('active', open);
    if (open && document.getElementById('extraCostRows').children.length === 0) {
        addExtraCostRow(); // auto-add first row
    }
    if (!open) {
        // Clear rows and update total
        document.getElementById('extraCostRows').innerHTML = '';
        extraCostRowCount = 0;
        updateSummary();
    }
}

function addExtraCostRow() {
    const idx  = extraCostRowCount++;
    const opts = extraCategories.map(c =>
        `<option value="${c}">${c}</option>`
    ).join('');
    const row = document.createElement('div');
    row.id = `ecr-${idx}`;
    row.style.cssText = 'display:flex;gap:6px;align-items:center;margin-bottom:6px';
    row.innerHTML = `
        <select name="extra_costs[${idx}][category]" class="extra-cost-cat form-select"
            style="flex:1;padding:6px 8px;font-size:.82rem;min-width:0"
            onchange="updateSummary()">
            <option value="">-- ক্যাটাগরি --</option>
            ${opts}
        </select>
        <span class="taka-input-wrap" style="width:96px;flex-shrink:0">
            <input type="text" inputmode="decimal" name="extra_costs[${idx}][amount]"
                placeholder="পরিমাণ" value=""
                class="extra-cost-amount"
                style="width:100%;padding:6px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:.82rem"
                oninput="updateSummary()">
        </span>
        <button type="button" onclick="removeExtraCostRow(${idx})"
            style="padding:5px 8px;border:none;background:#fee2e2;color:#dc2626;border-radius:6px;cursor:pointer;flex-shrink:0">
            <i class="fas fa-times"></i>
        </button>`;
    document.getElementById('extraCostRows').appendChild(row);
    if (typeof attachBengaliConverter === 'function') attachBengaliConverter(row);
    row.querySelector('select').focus();
}

function removeExtraCostRow(idx) {
    const el = document.getElementById(`ecr-${idx}`);
    if (el) el.remove();
    updateSummary();
}

const fieldMap = {
    discount: { row: 'discountRow', btn: 'discountToggleBtn', inp: 'discountInput', labelOn: '✕ ছাড়', labelOff: '+ ছাড়' },
};

function toggleField(key) {
    const f   = fieldMap[key];
    const row = document.getElementById(f.row);
    const btn = document.getElementById(f.btn);
    const inp = document.getElementById(f.inp);
    const open = row.style.display === 'none';
    row.style.display = open ? 'block' : 'none';
    btn.textContent   = open ? f.labelOn : f.labelOff;
    btn.classList.toggle('active', open);
    if (!open) { inp.value = '0'; updateSummary(); }
    else { inp.focus(); }
}

// Backward-compat name (in case anything still calls toggleDiscount)
function toggleDiscount() { toggleField('discount'); }

document.getElementById('discountInput').addEventListener('input', updateSummary);
document.getElementById('paidInput').addEventListener('input', function() { updateSummary(); checkExtraPayWarning(); });

// ── Block non-numeric input on every decimal field ───────────
// Keeps only digits + a single decimal point (Bengali numerals → English).
// Capture phase so the value is cleaned before updateSummary/updateQty read it.
function sanitizeDecimalInput(el) {
    if (!el || el.tagName !== 'INPUT' || el.getAttribute('inputmode') !== 'decimal') return;
    let v = toEnglishDigits(el.value).replace(/[^0-9.]/g, '');
    const dot = v.indexOf('.');
    if (dot !== -1) v = v.slice(0, dot + 1) + v.slice(dot + 1).replace(/\./g, '');
    if (v !== el.value) el.value = v;
}
document.getElementById('saleForm').addEventListener('input', function(e) {
    sanitizeDecimalInput(e.target);
}, true);

// Ensure paid_amount always has a numeric value before submit
document.getElementById('paidInput').addEventListener('blur', function() {
    if (this.value.trim() === '') this.value = '0';
});

function getNet() {
    return Math.max(0,
        cart.reduce((s, c) => s + c.qty * c.price, 0)
        - (parseFloat(toEnglishDigits(document.getElementById('discountInput').value)) || 0)
        + getExtraCostTotal()
    );
}

function setFullPay() {
    // Clear this sale AND any outstanding old due (ignore advance/credit balance)
    const owed = Math.max(0, currentPrevDue);
    document.getElementById('paidInput').value = (getNet() + owed).toFixed(0);
    updateSummary();
}

// Called when prev-due partial input changes
function onPrevDuePayChange() {
    const raw = parseFloat(toEnglishDigits(document.getElementById('prevDuePayInput').value)) || 0;
    prevDuePay = Math.min(Math.max(0, raw), currentPrevDue);
    document.getElementById('paidInput').value = (getNet() + prevDuePay).toFixed(0);
    updateSummary();
}

// Fill partial input with full due amount
function setFullPrevDuePay() {
    document.getElementById('prevDuePayInput').value = currentPrevDue.toFixed(0);
    onPrevDuePayChange();
}

// Reset partial due pay (on customer clear / customer change)
// Never touch paidInput — user must click সম্পূর্ণ explicitly
function resetPrevDuePay() {
    prevDuePay = 0;
    const inp = document.getElementById('prevDuePayInput');
    if (inp) inp.value = '0';
    updateSummary();
}

function checkExtraPayWarning() {
    const warn     = document.getElementById('extraPayWarning');
    if (!warn) return;
    const hasItems = cart.length > 0;
    const paid     = parseFloat(toEnglishDigits(document.getElementById('paidInput').value)) || 0;
    const hasCustomer = !!document.getElementById('customerIdInput').value;
    // Show warning when: no items + customer selected + customer has 0 due + paid > 0
    warn.style.display = (!hasItems && hasCustomer && currentPrevDue === 0 && paid > 0) ? 'block' : 'none';
}

let _stockConfirmPending  = false;
let _lossConfirmPending   = false;
let _excessConfirmPending = false;
document.getElementById('saleForm').addEventListener('submit', function(e) {
    // Safety net: convert any remaining Bengali digits in all numeric inputs
    this.querySelectorAll('input[inputmode="decimal"], input[inputmode="numeric"], .extra-cost-amount').forEach(inp => {
        if (/[০-৯]/.test(inp.value)) inp.value = toEnglishDigits(inp.value);
    });

    // Ensure paid_amount is numeric (never empty)
    const paidEl     = document.getElementById('paidInput');
    if (paidEl.value.trim() === '') paidEl.value = '0';

    const hasItems   = cart.length > 0;
    const hasCustomer= !!document.getElementById('customerIdInput').value;
    const paid       = parseFloat(toEnglishDigits(paidEl.value)) || 0;
    const net        = cart.reduce((s, c) => s + c.qty * c.price, 0)
                       - (parseFloat(toEnglishDigits(document.getElementById('discountInput').value)) || 0)
                       + getExtraCostTotal();

    // No items: require customer + paid amount
    if (!hasItems) {
        if (!hasCustomer) {
            e.preventDefault();
            showStockToast('আইটেম ছাড়া বিক্রয়ে কাস্টমার নির্বাচন আবশ্যক!', 'error');
            document.getElementById('customerSearch').focus();
            return;
        }
        if (paid <= 0) {
            e.preventDefault();
            showStockToast('পরিশোধের পরিমাণ লিখুন!', 'error');
            paidEl.focus();
            return;
        }
        // allow submit — payment-only sale
        return;
    }

    // Walk-in: full payment required
    const noCustomer = !hasCustomer;
    if (noCustomer && paid < net) {
        e.preventDefault();
        document.getElementById('walkinWarning').style.display = 'block';
        paidEl.focus();
        showStockToast('ওয়াক-ইন কাস্টমারের জন্য সম্পূর্ণ পরিশোধ আবশ্যক!', 'error');
        return;
    }
    document.getElementById('walkinWarning').style.display = 'none';

    // ── Loss warning on submit ───────────────────────────────
    const lossItems = cart.filter(c => c.priceEntered && c.cost > 0 && c.price < c.cost);
    if (lossItems.length && !_lossConfirmPending) {
        e.preventDefault();
        const lines = lossItems.map(c =>
            `• ${c.name}\n  ক্রয়: ৳${c.cost.toFixed(0)}  →  বিক্রয়: ৳${c.price.toFixed(0)}  (লোকসান: ৳${(c.cost - c.price).toFixed(0)}/পিস)`
        ).join('\n');
        showLossConfirm(lines, () => {
            _lossConfirmPending = true;
            document.getElementById('saleForm').requestSubmit();
        });
        return;
    }
    _lossConfirmPending = false;

    // ── Excessive profit warning on submit ───────────────────
    const EXCESS_PCT = 25;
    const excessItems = cart.filter(c => c.priceEntered && c.cost > 0 && (c.price - c.cost) / c.cost * 100 > EXCESS_PCT);
    if (excessItems.length && !_excessConfirmPending) {
        e.preventDefault();
        const lines = excessItems.map(c => {
            const pct = ((c.price - c.cost) / c.cost * 100).toFixed(1);
            return `• ${c.name}\n  ক্রয়: ৳${c.cost.toFixed(0)}  →  বিক্রয়: ৳${c.price.toFixed(0)}  (লাভ: ${pct}%)`;
        }).join('\n');
        showExcessConfirm(lines, () => {
            _excessConfirmPending = true;
            document.getElementById('saleForm').requestSubmit();
        });
        return;
    }
    _excessConfirmPending = false;

    if (_stockConfirmPending) { _stockConfirmPending = false; return; }
    const overItems = cart.filter(c => c.qty > (c.stock ?? Infinity));
    if (overItems.length) {
        e.preventDefault();
        const lines = overItems.map(c => `• ${c.name} (চাহিদা: ${c.qty}, স্টক: ${c.stock})`).join('\n');
        showStockConfirm(lines, () => {
            _stockConfirmPending = true;
            document.getElementById('saleForm').requestSubmit();
        });
    }
});

// ── Stock over-stock confirm dialog ─────────────────────────
function showStockConfirm(details, onConfirm) {
    let d = document.getElementById('stockConfirmDialog');
    if (!d) {
        d = document.createElement('div');
        d.id = 'stockConfirmDialog';
        d.style.cssText = `
            position:fixed;inset:0;z-index:99998;
            background:rgba(0,0,0,.45);display:flex;
            align-items:center;justify-content:center;
        `;
        d.innerHTML = `
            <div style="background:#fff;border-radius:14px;padding:28px 26px;
                        max-width:400px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    <div style="width:40px;height:40px;border-radius:50%;background:#fef9c3;
                                color:#92400e;display:flex;align-items:center;justify-content:center;
                                font-size:1.2rem;flex-shrink:0">⚠</div>
                    <h3 style="font-size:1rem;color:#0f172a">স্টক অপর্যাপ্ত</h3>
                </div>
                <p style="font-size:.86rem;color:#475569;margin-bottom:10px">
                    নিচের পণ্যগুলোর চাহিদা স্টকের বেশি:
                </p>
                <pre id="stockConfirmLines" style="font-size:.83rem;color:#92400e;
                    background:#fef9c3;border:1px solid #fde68a;border-radius:8px;
                    padding:10px 12px;white-space:pre-wrap;margin-bottom:16px;
                    font-family:inherit"></pre>
                <p style="font-size:.84rem;color:#64748b;margin-bottom:20px">
                    তবুও কি বিক্রয় সম্পন্ন করবেন? স্টক মাইনাস (-) হবে।
                </p>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button id="stockConfirmCancel"
                        style="padding:9px 20px;border-radius:8px;border:1.5px solid #e2e8f0;
                               background:#fff;cursor:pointer;font-size:.88rem;font-weight:600;color:#475569">
                        বাতিল
                    </button>
                    <button id="stockConfirmOk"
                        style="padding:9px 20px;border-radius:8px;border:none;
                               background:#d97706;color:#fff;cursor:pointer;font-size:.88rem;font-weight:600">
                        হ্যাঁ, বিক্রয় করুন
                    </button>
                </div>
            </div>`;
        document.body.appendChild(d);
    }
    document.getElementById('stockConfirmLines').textContent = details;
    d.style.display = 'flex';
    document.getElementById('stockConfirmCancel').onclick = () => { d.style.display = 'none'; };
    document.getElementById('stockConfirmOk').onclick    = () => { d.style.display = 'none'; onConfirm(); };
}

// ── Excessive profit confirm dialog ─────────────────────────
function showExcessConfirm(details, onConfirm) {
    let d = document.getElementById('excessConfirmDialog');
    if (!d) {
        d = document.createElement('div');
        d.id = 'excessConfirmDialog';
        d.style.cssText = `
            position:fixed;inset:0;z-index:99998;
            background:rgba(0,0,0,.45);display:flex;
            align-items:center;justify-content:center;
        `;
        d.innerHTML = `
            <div style="background:#fff;border-radius:14px;padding:28px 26px;
                        max-width:440px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    <div style="width:44px;height:44px;border-radius:50%;background:#fef9c3;
                                color:#92400e;display:flex;align-items:center;justify-content:center;
                                font-size:1.4rem;flex-shrink:0">⚠</div>
                    <div>
                        <h3 style="font-size:1rem;color:#0f172a;margin:0">অতিরিক্ত লাভ!</h3>
                        <p style="font-size:.78rem;color:#64748b;margin:2px 0 0">লাভের পরিমাণ ২৫%-এর বেশি — নিশ্চিত করুন</p>
                    </div>
                </div>
                <pre id="excessConfirmLines" style="font-size:.82rem;color:#92400e;
                    background:#fef9c3;border:1px solid #fde68a;border-radius:8px;
                    padding:10px 12px;white-space:pre-wrap;margin-bottom:16px;
                    font-family:inherit;line-height:1.6"></pre>
                <p style="font-size:.84rem;color:#64748b;margin-bottom:20px">
                    মূল্য সঠিক আছে? তবুও বিক্রয় সম্পন্ন করবেন?
                </p>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button id="excessConfirmCancel"
                        style="padding:9px 20px;border-radius:8px;border:1.5px solid #e2e8f0;
                               background:#fff;cursor:pointer;font-size:.88rem;font-weight:600;color:#475569">
                        বাতিল — মূল্য ঠিক করুন
                    </button>
                    <button id="excessConfirmOk"
                        style="padding:9px 20px;border-radius:8px;border:none;
                               background:#d97706;color:#fff;cursor:pointer;font-size:.88rem;font-weight:600">
                        হ্যাঁ, সঠিক আছে
                    </button>
                </div>
            </div>`;
        document.body.appendChild(d);
    }
    document.getElementById('excessConfirmLines').textContent = details;
    d.style.display = 'flex';
    document.getElementById('excessConfirmCancel').onclick = () => { d.style.display = 'none'; };
    document.getElementById('excessConfirmOk').onclick    = () => { d.style.display = 'none'; onConfirm(); };
}

// ── Loss confirm dialog ──────────────────────────────────────
function showLossConfirm(details, onConfirm) {
    let d = document.getElementById('lossConfirmDialog');
    if (!d) {
        d = document.createElement('div');
        d.id = 'lossConfirmDialog';
        d.style.cssText = `
            position:fixed;inset:0;z-index:99998;
            background:rgba(0,0,0,.45);display:flex;
            align-items:center;justify-content:center;
        `;
        d.innerHTML = `
            <div style="background:#fff;border-radius:14px;padding:28px 26px;
                        max-width:440px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.25)">
                <div style="display:flex;align-items:center;gap:10px;margin-bottom:14px">
                    <div style="width:44px;height:44px;border-radius:50%;background:#fee2e2;
                                color:#b91c1c;display:flex;align-items:center;justify-content:center;
                                font-size:1.4rem;flex-shrink:0">⚠</div>
                    <div>
                        <h3 style="font-size:1rem;color:#0f172a;margin:0">লোকসানে বিক্রয়!</h3>
                        <p style="font-size:.78rem;color:#64748b;margin:2px 0 0">ক্রয়মূল্যের নিচে বিক্রয় হচ্ছে</p>
                    </div>
                </div>
                <pre id="lossConfirmLines" style="font-size:.82rem;color:#b91c1c;
                    background:#fee2e2;border:1px solid #fca5a5;border-radius:8px;
                    padding:10px 12px;white-space:pre-wrap;margin-bottom:16px;
                    font-family:inherit;line-height:1.6"></pre>
                <p style="font-size:.84rem;color:#64748b;margin-bottom:20px">
                    তবুও কি বিক্রয় সম্পন্ন করবেন?
                </p>
                <div style="display:flex;gap:10px;justify-content:flex-end">
                    <button id="lossConfirmCancel"
                        style="padding:9px 20px;border-radius:8px;border:1.5px solid #e2e8f0;
                               background:#fff;cursor:pointer;font-size:.88rem;font-weight:600;color:#475569">
                        বাতিল — মূল্য ঠিক করুন
                    </button>
                    <button id="lossConfirmOk"
                        style="padding:9px 20px;border-radius:8px;border:none;
                               background:#dc2626;color:#fff;cursor:pointer;font-size:.88rem;font-weight:600">
                        হ্যাঁ, লোকসানে বিক্রয় করুন
                    </button>
                </div>
            </div>`;
        document.body.appendChild(d);
    }
    document.getElementById('lossConfirmLines').textContent = details;
    d.style.display = 'flex';
    document.getElementById('lossConfirmCancel').onclick = () => { d.style.display = 'none'; };
    document.getElementById('lossConfirmOk').onclick    = () => { d.style.display = 'none'; onConfirm(); };
}

// ── Stock alert toast ────────────────────────────────────────
function showStockToast(msg, type) {
    let t = document.getElementById('stockToast');
    if (!t) {
        t = document.createElement('div');
        t.id = 'stockToast';
        t.style.cssText = `
            position:fixed;bottom:28px;left:50%;transform:translateX(-50%) translateY(20px);
            z-index:99999;padding:12px 22px;border-radius:10px;
            font-size:.88rem;font-weight:600;
            box-shadow:0 8px 24px rgba(0,0,0,.18);
            max-width:380px;text-align:center;white-space:pre-line;
            opacity:0;transition:opacity .25s,transform .25s;pointer-events:none;
        `;
        document.body.appendChild(t);
    }
    clearTimeout(t._timer);
    t.textContent = msg;
    if (type === 'error') {
        t.style.background = '#dc2626'; t.style.color = '#fff'; t.style.border = 'none';
    } else if (type === 'ok') {
        t.style.background = '#dcfce7'; t.style.color = '#15803d'; t.style.border = '1px solid #bbf7d0';
    } else {
        t.style.background = '#fef9c3'; t.style.color = '#92400e'; t.style.border = '1px solid #fde68a';
    }
    t.style.opacity   = '1';
    t.style.transform = 'translateX(-50%) translateY(0)';
    t._timer = setTimeout(() => {
        t.style.opacity   = '0';
        t.style.transform = 'translateX(-50%) translateY(20px)';
    }, 3500);
}

// ── Auto-select customer from URL param ──────────────────────
@if(request('customer_id'))
(function () {
    const preId = {{ (int) request('customer_id') }};
    const pre   = allCustomers.find(c => c.id === preId);
    if (pre) selectCustomer(pre.id);
})();
@endif

// ══════════════════════════════════════════════════════════════
// SALE DRAFT — auto-save to sessionStorage, restore on reload
// ══════════════════════════════════════════════════════════════
const DRAFT_KEY = 'sale_draft_{{ auth()->user()->shop_id }}_{{ auth()->id() }}';
let _draftTimer  = null;

function scheduleDraftSave() {
    clearTimeout(_draftTimer);
    _draftTimer = setTimeout(saveDraft, 1500);
}

function saveDraft() {
    // Don't save if form is completely empty
    if (cart.length === 0 && !document.getElementById('customerIdInput').value) return;

    // Collect extra cost rows
    const extraCosts = [];
    document.querySelectorAll('#extraCostRows > div').forEach(row => {
        const cat = row.querySelector('select')?.value  || '';
        const amt = row.querySelector('.extra-cost-amount')?.value || '0';
        extraCosts.push({ category: cat, amount: amt });
    });

    const draft = {
        cart,
        customerId:    document.getElementById('customerIdInput').value,
        customerName:  document.getElementById('customerSearch').value,
        customerObj:   allCustomers.find(c => String(c.id) === document.getElementById('customerIdInput').value) || null,
        customerDue:   currentPrevDue,
        prevDuePay:    document.getElementById('prevDuePayInput')?.value || '0',
        discount:      document.getElementById('discountInput').value,
        discountOpen:  document.getElementById('discountRow').style.display !== 'none',
        extraCosts,
        extraOpen:     document.getElementById('extraRow').style.display !== 'none',
        paidAmount:    document.getElementById('paidInput').value,
        paymentMethod: document.getElementById('paymentMethod').value,
        notes:         document.querySelector('textarea[name="notes"]').value,
        saleDate:      document.querySelector('input[name="sale_date"]').value,
        savedAt:       Date.now(),
    };

    try { sessionStorage.setItem(DRAFT_KEY, JSON.stringify(draft)); } catch(_) {}
}

function clearDraft() {
    sessionStorage.removeItem(DRAFT_KEY);
    _pendingDraft = null;
}

function discardDraft() {
    clearDraft();
    document.getElementById('draftBanner').style.display = 'none';
}

let _pendingDraft = null;

function restoreDraftData() {
    const draft = _pendingDraft;
    if (!draft) return;

    clearDraft();
    document.getElementById('draftBanner').style.display = 'none';

    // Restore cart
    cart = draft.cart || [];
    renderCart();

    // Restore customer
    if (draft.customerId) {
        // Seed the cache from the saved object (server-side search means the
        // customer may not be loaded yet)
        if (draft.customerObj && !allCustomers.find(c => String(c.id) === String(draft.customerId))) {
            allCustomers.push(draft.customerObj);
        }
        const cust = allCustomers.find(c => String(c.id) === String(draft.customerId));
        if (cust) {
            selectCustomer(cust.id);
            // After selectCustomer sets currentPrevDue, restore prevDuePay
            setTimeout(() => {
                const ppEl = document.getElementById('prevDuePayInput');
                if (ppEl && draft.prevDuePay) {
                    ppEl.value = draft.prevDuePay;
                    onPrevDuePayChange();
                }
            }, 100);
        } else {
            // Customer might have been deleted — show name only
            document.getElementById('customerSearch').value = draft.customerName || '';
        }
    }

    // Restore date
    if (draft.saleDate) {
        document.querySelector('input[name="sale_date"]').value = draft.saleDate;
    }

    // Restore discount
    if (draft.discountOpen) {
        document.getElementById('discountRow').style.display = 'block';
        document.getElementById('discountInput').value = draft.discount || '0';
    }

    // Restore extra costs
    if (draft.extraOpen && draft.extraCosts?.length) {
        document.getElementById('extraRow').style.display = 'block';
        // Clear existing rows first
        document.getElementById('extraCostRows').innerHTML = '';
        extraCostRowCount = 0;
        draft.extraCosts.forEach(ec => {
            addExtraCostRow();
            const rows = document.querySelectorAll('#extraCostRows > div');
            const last = rows[rows.length - 1];
            if (last) {
                const sel = last.querySelector('select');
                const inp = last.querySelector('.extra-cost-amount');
                if (sel) sel.value = ec.category;
                if (inp) inp.value = ec.amount;
            }
        });
    }

    // Restore paid + payment method + notes
    document.getElementById('paidInput').value = draft.paidAmount || '0';
    if (draft.paymentMethod) {
        document.getElementById('paymentMethod').value = draft.paymentMethod;
    }
    document.querySelector('textarea[name="notes"]').value = draft.notes || '';

    updateSummary();
}

// ── Check for existing draft on page load ─────────────────────
(function checkDraft() {
    @if(request('customer_id')) return; @endif  // pre-selected customer — skip draft
    try {
        const raw = sessionStorage.getItem(DRAFT_KEY);
        if (!raw) return;
        const draft = JSON.parse(raw);
        if (!draft || (!draft.cart?.length && !draft.customerId)) { clearDraft(); return; }

        _pendingDraft = draft;
        const age     = Date.now() - (draft.savedAt || 0);
        const minutes = Math.round(age / 60000);
        const timeStr = minutes < 1 ? 'এইমাত্র' : minutes < 60
            ? `${minutes} মিনিট আগে` : `${Math.round(minutes/60)} ঘণ্টা আগে`;
        const itemCount = draft.cart?.length || 0;

        document.getElementById('draftBannerText').textContent =
            `${itemCount}টি আইটেমসহ অসমাপ্ত বিক্রয় পাওয়া গেছে (${timeStr} সংরক্ষিত) — পুনরুদ্ধার করবেন?`;
        document.getElementById('draftBanner').style.display = 'flex';
    } catch(_) {}
})();

// ── Save on any form input change ─────────────────────────────
document.getElementById('saleForm').addEventListener('input',  scheduleDraftSave);
document.getElementById('saleForm').addEventListener('change', scheduleDraftSave);

// ── Clear draft on actual submission ─────────────────────────
// Natural path: runs AFTER original validator (e.defaultPrevented already set)
document.getElementById('saleForm').addEventListener('submit', function(e) {
    if (!e.defaultPrevented) clearDraft();
}, false);

// Confirm-dialog paths use requestSubmit() — patch to clear before resubmit
const _origReqSubmit = document.getElementById('saleForm').requestSubmit.bind(document.getElementById('saleForm'));
document.getElementById('saleForm').requestSubmit = function() {
    clearDraft();
    _origReqSubmit();
};

// ── নতুন কাস্টমার Popup ───────────────────────────────────
function openCustomerModal() {
    document.getElementById('custModalError').style.display = 'none';
    ['cmName','cmProprietor','cmPhone','cmAddress'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('cmArea').value = '';
    document.getElementById('customerModal').style.display = 'flex';
    // Pre-fill name from search box if user typed something
    const typed = document.getElementById('customerSearch').value.trim();
    if (typed) document.getElementById('cmName').value = typed;
    setTimeout(() => document.getElementById('cmName').focus(), 50);
    if (typeof attachBengaliConverter === 'function') {
        attachBengaliConverter(document.getElementById('customerModal'));
    }
}
function closeCustomerModal() {
    document.getElementById('customerModal').style.display = 'none';
}
// Close on overlay click / Esc
document.getElementById('customerModal').addEventListener('click', function(e) {
    if (e.target === this) closeCustomerModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape' && document.getElementById('customerModal').style.display === 'flex') closeCustomerModal();
});

async function saveNewCustomer() {
    const errBox = document.getElementById('custModalError');
    const name   = document.getElementById('cmName').value.trim();
    if (!name) {
        errBox.textContent = 'প্রতিষ্ঠানের নাম আবশ্যক।';
        errBox.style.display = 'block';
        return;
    }
    const btn = document.getElementById('cmSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> সংরক্ষণ হচ্ছে...';
    errBox.style.display = 'none';

    const payload = {
        name:       name,
        proprietor: document.getElementById('cmProprietor').value.trim(),
        phone:      toEnglishDigits(document.getElementById('cmPhone').value.trim()),
        area_id:    document.getElementById('cmArea').value || '',
        address:    document.getElementById('cmAddress').value.trim(),
    };

    try {
        const res = await fetch("{{ route('customers.store') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify(payload),
        });

        if (!res.ok) {
            const data = await res.json().catch(() => ({}));
            let msg = 'সংরক্ষণ ব্যর্থ হয়েছে।';
            if (data.errors) msg = Object.values(data.errors).flat().join(' ');
            throw new Error(msg);
        }

        const data = await res.json();
        const c = data.customer;
        // Add to in-memory list and select immediately
        allCustomers.push(c);
        closeCustomerModal();
        selectCustomer(c.id);
    } catch (err) {
        errBox.textContent = err.message || 'সংরক্ষণ ব্যর্থ হয়েছে।';
        errBox.style.display = 'block';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> সংরক্ষণ ও নির্বাচন';
    }
}

// Keep the bottom spacer tall enough that মন্তব্য never hides behind the fixed submit bar
function syncSubmitBarSpacer() {
    const bar = document.querySelector('.sale-submit-bar');
    const sp  = document.getElementById('submitBarSpacer');
    if (bar && sp) sp.style.height = (bar.offsetHeight + 24) + 'px';
}
window.addEventListener('resize', syncSubmitBarSpacer);
document.addEventListener('DOMContentLoaded', syncSubmitBarSpacer);

document.addEventListener('DOMContentLoaded', () => bnWatchTakaWords('paidInput', 'paidWords'));
</script>
@endpush
@endsection
