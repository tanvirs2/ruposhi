@extends('errors.layout')

@section('title', 'সেশন মেয়াদ শেষ')
@section('code', '419')
@section('code-color-from', '#06b6d4')
@section('code-color-to', '#0891b2')
@section('icon', 'fas fa-hourglass-end')
@section('icon-color', '#22d3ee')
@section('icon-glow', 'rgba(34,211,238,.4)')
@section('btn-from', '#0891b2')
@section('btn-to', '#0e7490')

@section('title-text', 'সেশনের মেয়াদ শেষ হয়ে গেছে')

@section('description')
    আপনি অনেকক্ষণ কোনো কাজ না করায় আপনার সেশন বন্ধ হয়ে গেছে।
    এটি নিরাপত্তার জন্য স্বয়ংক্রিয়ভাবে হয়। আবার login করুন।
@endsection

@section('steps')
    <li><i class="fas fa-rotate-left"></i> আগের পাতায় ফিরে আবার সাবমিট করুন</li>
    <li><i class="fas fa-right-to-bracket"></i> আবার login করুন — সব ডেটা নিরাপদ আছে</li>
    <li><i class="fas fa-lightbulb"></i> পরের বার: অনেকক্ষণ কাজ না করলে save করে রাখুন</li>
@endsection

@section('buttons')
    <a href="javascript:history.back()" class="btn-primary-err">
        <i class="fas fa-rotate-left"></i> পেছনে যান
    </a>
    <a href="{{ route('login') }}" class="btn-ghost-err">
        <i class="fas fa-right-to-bracket"></i> আবার Login
    </a>
@endsection
