@extends('layouts.root')
@section('title', 'সম্পাদনা — ' . $superAdmin->name)
@section('page-title', 'সুপার অ্যাডমিন সম্পাদনা')

@section('content')
<div class="rt-card" style="max-width:560px">
    <div class="rt-card-title"><i class="fas fa-pen"></i> {{ $superAdmin->name }}</div>

    @if($errors->any())
        <div class="rt-alert rt-alert-error">
            @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
        </div>
    @endif

    <form method="POST" action="{{ route('root.super-admins.update', $superAdmin) }}">
        @csrf @method('PUT')

        <div class="rt-grid-2">
            <div class="rt-field">
                <label class="rt-label">নাম *</label>
                <input class="rt-input" name="name" value="{{ old('name', $superAdmin->name) }}" required>
            </div>
            <div class="rt-field">
                <label class="rt-label">ইমেইল *</label>
                <input class="rt-input" type="email" name="email" value="{{ old('email', $superAdmin->email) }}" required>
            </div>
        </div>

        <div class="rt-field">
            <label class="rt-label">নতুন পাসওয়ার্ড (ফাঁকা রাখলে পরিবর্তন হবে না)</label>
            <input class="rt-input" type="password" name="password">
        </div>

        <div style="display:flex;gap:10px">
            <button type="submit" class="rt-btn rt-btn-primary"><i class="fas fa-check"></i> সংরক্ষণ</button>
            <a href="{{ route('root.super-admins.index') }}" class="rt-btn rt-btn-ghost"><i class="fas fa-arrow-left"></i> বাতিল</a>
        </div>
    </form>
</div>
@endsection
