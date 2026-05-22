@extends('layouts.app')
@section('title', $expense->type === 'deposit' ? 'জমা সম্পাদনা' : 'খরচ সম্পাদনা')
@section('page-title', $expense->type === 'deposit' ? 'জমা সম্পাদনা' : 'খরচ সম্পাদনা')

@section('content')
<div class="form-card">
    <form method="POST" action="{{ route('expenses.update', $expense) }}">
        @csrf @method('PUT')

        {{-- Type toggle --}}
        <div style="margin-bottom:20px">
            <label style="display:block;font-size:.85rem;font-weight:600;margin-bottom:8px;color:var(--text-secondary)">ধরন <span class="req">*</span></label>
            <div style="display:flex;gap:0;border:2px solid var(--border);border-radius:10px;overflow:hidden;width:fit-content">
                <label id="tab-deposit" style="display:flex;align-items:center;gap:7px;padding:10px 22px;cursor:pointer;font-weight:700;font-size:.9rem;transition:.15s">
                    <input type="radio" name="type" value="deposit" {{ old('type', $expense->type) === 'deposit' ? 'checked' : '' }} style="display:none" onchange="switchType()">
                    <i class="fas fa-arrow-down-to-line"></i> জমা (ক্যাশ ইন)
                </label>
                <label id="tab-expense" style="display:flex;align-items:center;gap:7px;padding:10px 22px;cursor:pointer;font-weight:700;font-size:.9rem;border-left:2px solid var(--border);transition:.15s">
                    <input type="radio" name="type" value="expense" {{ old('type', $expense->type) === 'expense' ? 'checked' : '' }} style="display:none" onchange="switchType()">
                    <i class="fas fa-arrow-up-from-line"></i> খরচ (ক্যাশ আউট)
                </label>
            </div>
        </div>

        <div class="form-grid">
            <div class="form-group-field">
                <label>শিরোনাম <span class="req">*</span></label>
                <input type="text" name="title" value="{{ old('title', $expense->title) }}" required>
            </div>
            <div class="form-group-field">
                <label>ক্যাটাগরি</label>
                <input type="text" name="category" value="{{ old('category', $expense->category) }}">
            </div>
            <div class="form-group-field">
                <label>পরিমাণ (৳) <span class="req">*</span></label>
                <input type="text" inputmode="decimal" name="amount" value="{{ old('amount', $expense->amount) }}" required>
            </div>
            <div class="form-group-field">
                <label>তারিখ <span class="req">*</span></label>
                <input type="date" name="expense_date" value="{{ old('expense_date', $expense->expense_date->toDateString()) }}" required>
            </div>
            <div class="form-group-field form-full">
                <label>মন্তব্য</label>
                <textarea name="notes" rows="2">{{ old('notes', $expense->notes) }}</textarea>
            </div>
        </div>

        <div class="form-actions">
            <a href="{{ route('expenses.index') }}" class="btn btn-ghost">বাতিল</a>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> আপডেট</button>
        </div>
    </form>
</div>

@push('scripts')
<script>
function switchType() {
    const isDeposit = document.querySelector('input[name=type]:checked').value === 'deposit';
    const tabD = document.getElementById('tab-deposit');
    const tabE = document.getElementById('tab-expense');
    if (isDeposit) {
        tabD.style.background = '#dcfce7'; tabD.style.color = '#15803d';
        tabE.style.background = '';        tabE.style.color = '';
    } else {
        tabE.style.background = '#fee2e2'; tabE.style.color = '#b91c1c';
        tabD.style.background = '';        tabD.style.color = '';
    }
}
switchType();
</script>
@endpush
@endsection
