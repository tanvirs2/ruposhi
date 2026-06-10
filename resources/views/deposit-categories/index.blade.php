@extends('layouts.app')
@section('title', 'জমার ক্যাটাগরি')
@section('page-title', 'জমার ক্যাটাগরি')

@section('content')
@include('partials.page-header', ['title' => 'জমার ক্যাটাগরি', 'createRoute' => null])

@if(session('success'))
<div class="alert alert-success" style="margin-bottom:14px;padding:10px 16px;background:#dcfce7;border:1px solid #bbf7d0;border-radius:8px;color:#15803d;font-weight:600">
    <i class="fas fa-check-circle"></i> {{ session('success') }}
</div>
@endif

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start">

    {{-- Add form --}}
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-plus-circle" style="color:#1d4ed8"></i> নতুন ক্যাটাগরি যোগ করুন</h3></div>
        <div style="padding:20px">
            <form method="POST" action="{{ route('deposit-categories.store') }}">
                @csrf
                <div class="form-group-field" style="margin-bottom:14px">
                    <label>ক্যাটাগরির নাম <span class="req">*</span></label>
                    <input type="text" name="name" placeholder="যেমন: অগ্রিম, চেক, বিকাশ জমা…"
                        value="{{ old('name') }}" required style="width:100%">
                    @error('name')
                        <div style="color:#dc2626;font-size:.8rem;margin-top:4px">{{ $message }}</div>
                    @enderror
                </div>
                <button type="submit" class="btn btn-primary" style="width:100%;background:#1d4ed8">
                    <i class="fas fa-plus"></i> যোগ করুন
                </button>
            </form>
        </div>
    </div>

    {{-- List --}}
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-list"></i> সকল ক্যাটাগরি ({{ $categories->count() }}টি)</h3>
        </div>
        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>নাম</th>
                        <th class="tc">মুছুন</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($categories as $cat)
                    <tr>
                        <td class="mono" style="color:#94a3b8">{{ $loop->iteration }}</td>
                        <td>
                            <form method="POST" action="{{ route('deposit-categories.update', $cat) }}"
                                  style="display:flex;gap:6px;align-items:center">
                                @csrf @method('PUT')
                                <input type="text" name="name" value="{{ $cat->name }}"
                                    style="flex:1;padding:5px 8px;border:1.5px solid var(--border);border-radius:6px;font-size:.85rem">
                                <button type="submit" class="btn btn-ghost" style="padding:5px 10px;font-size:.78rem">
                                    <i class="fas fa-check" style="color:#0d9488"></i>
                                </button>
                            </form>
                        </td>
                        <td class="tc">
                            <form method="POST" action="{{ route('deposit-categories.destroy', $cat) }}"
                                  onsubmit="return confirm('\"{{ $cat->name }}\" মুছবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="3" class="empty-row">কোনো ক্যাটাগরি নেই — বাম থেকে যোগ করুন</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
