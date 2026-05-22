@extends('layouts.app')
@section('title', $defaultType === 'deposit' ? 'নতুন জমা' : 'নতুন খরচ')
@section('page-title', $defaultType === 'deposit' ? 'নতুন জমা' : 'নতুন খরচ')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('expenses.store') }}" id="expForm">
        @csrf

        {{-- Type toggle --}}
        <div style="margin-bottom:20px">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:8px;color:var(--text-secondary)">ধরন নির্বাচন করুন <span class="req">*</span></label>
            <div style="display:flex;gap:0;border:2px solid var(--border);border-radius:10px;overflow:hidden;width:fit-content">
                <label id="tab-deposit" style="display:flex;align-items:center;gap:7px;padding:10px 22px;cursor:pointer;font-weight:700;font-size:.9rem;transition:.15s">
                    <input type="radio" name="type" value="deposit" {{ old('type', $defaultType) === 'deposit' ? 'checked' : '' }} style="display:none" onchange="switchType()">
                    <i class="fas fa-arrow-down-to-line"></i> জমা (ক্যাশ ইন)
                </label>
                <label id="tab-expense" style="display:flex;align-items:center;gap:7px;padding:10px 22px;cursor:pointer;font-weight:700;font-size:.9rem;border-left:2px solid var(--border);transition:.15s">
                    <input type="radio" name="type" value="expense" {{ old('type', $defaultType) === 'expense' ? 'checked' : '' }} style="display:none" onchange="switchType()">
                    <i class="fas fa-arrow-up-from-line"></i> খরচ (ক্যাশ আউট)
                </label>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group-field">
                <label>শিরোনাম <span class="req">*</span></label>
                <input type="text" name="title" id="titleInput" value="{{ old('title') }}" required
                    placeholder="যেমন: মালিকের জমা / বিদ্যুৎ বিল...">
            </div>
            <div class="form-group-field">
                <label>ক্যাটাগরি</label>
                <input type="text" name="category" value="{{ old('category') }}"
                    placeholder="যেমন: বেতন, ভাড়া, মালিক জমা...">
            </div>
            <div class="form-group-field">
                <label>পরিমাণ (৳) <span class="req">*</span></label>
                <input type="text" inputmode="decimal" name="amount" value="{{ old('amount') }}" required
                    placeholder="0">
            </div>
            <div class="form-group-field">
                <label>তারিখ <span class="req">*</span></label>
                <input type="date" name="expense_date" value="{{ old('expense_date', date('Y-m-d')) }}" required>
            </div>
            <div class="form-group-field form-full">
                <label>মন্তব্য</label>
                <textarea name="notes" rows="2" placeholder="বিস্তারিত তথ্য (ঐচ্ছিক)...">{{ old('notes') }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('expenses.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" id="submitBtn" class="btn btn-primary">
                <i class="fas fa-save"></i> <span id="submitLabel">সংরক্ষণ</span>
            </button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function switchType() {
    const isDeposit = document.querySelector('input[name=type]:checked').value === 'deposit';

    const tabD = document.getElementById('tab-deposit');
    const tabE = document.getElementById('tab-expense');
    const lbl  = document.getElementById('submitLabel');
    const title= document.getElementById('titleInput');

    if (isDeposit) {
        tabD.style.background = '#dcfce7';
        tabD.style.color      = '#15803d';
        tabE.style.background = '';
        tabE.style.color      = '';
        lbl.textContent       = 'জমা সংরক্ষণ করুন';
        title.placeholder     = 'যেমন: মালিকের জমা, ঋণ গ্রহণ...';
    } else {
        tabE.style.background = '#fee2e2';
        tabE.style.color      = '#b91c1c';
        tabD.style.background = '';
        tabD.style.color      = '';
        lbl.textContent       = 'খরচ সংরক্ষণ করুন';
        title.placeholder     = 'যেমন: বিদ্যুৎ বিল, বেতন, ভাড়া...';
    }
}
// Apply on load
switchType();
</script>
@endpush
@endsection
