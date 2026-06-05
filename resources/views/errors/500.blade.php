@extends('errors.layout')

@section('title', 'সার্ভার সমস্যা')
@section('code', '500')
@section('code-color-from', '#ef4444')
@section('code-color-to', '#dc2626')
@section('icon', 'fas fa-triangle-exclamation')
@section('icon-color', '#f87171')
@section('icon-glow', 'rgba(248,113,113,.4)')
@section('btn-from', '#dc2626')
@section('btn-to', '#b91c1c')

@section('title-text', 'সার্ভারে একটি সমস্যা হয়েছে')

@section('description')
    আমাদের সার্ভারে একটি অপ্রত্যাশিত সমস্যা ঘটেছে।
    এটি আপনার কারণে নয় — আমাদের পক্ষ থেকে সমস্যা হয়েছে।
    কিছুক্ষণ পরে আবার চেষ্টা করুন।
@endsection

@section('steps')
    <li><i class="fas fa-rotate-right"></i> পাতাটি রিফ্রেশ করুন (F5 বা Ctrl+R)</li>
    <li><i class="fas fa-clock"></i> ২-৩ মিনিট অপেক্ষা করে আবার চেষ্টা করুন</li>
    <li><i class="fas fa-house"></i> কাজ না হলে হোম পাতায় ফিরে যান</li>
    <li><i class="fas fa-headset"></i> সমস্যা চলতে থাকলে অবশ্যই অ্যাডমিনকে জানান</li>
@endsection

@section('buttons')
    <a href="javascript:location.reload()" class="btn-primary-err">
        <i class="fas fa-rotate-right"></i> আবার চেষ্টা করুন
    </a>
    <a href="{{ url('/dashboard') }}" class="btn-ghost-err">
        <i class="fas fa-house"></i> ড্যাশবোর্ড
    </a>
@endsection
