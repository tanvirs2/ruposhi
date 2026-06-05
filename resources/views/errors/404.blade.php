@extends('errors.layout')

@section('title', 'পাতা পাওয়া যায়নি')
@section('code', '404')
@section('code-color-from', '#6366f1')
@section('code-color-to', '#8b5cf6')
@section('icon', 'fas fa-map-location-dot')
@section('icon-color', '#818cf8')
@section('icon-glow', 'rgba(129,140,248,.4)')
@section('btn-from', '#4f46e5')
@section('btn-to', '#7c3aed')

@section('title-text', 'এই পাতাটি খুঁজে পাওয়া যায়নি')

@section('description')
    আপনি যে পাতায় যেতে চাইছেন সেটি সরানো হয়েছে, নাম পরিবর্তন হয়েছে,
    অথবা এটি কখনো ছিল না। চিন্তা নেই — আপনাকে সঠিক পথে নিয়ে যাওয়া যাবে।
@endsection

@section('steps')
    <li><i class="fas fa-arrow-left"></i> আগের পাতায় ফিরে যান এবং আবার চেষ্টা করুন</li>
    <li><i class="fas fa-house"></i> হোম পাতায় ফিরে নতুন করে শুরু করুন</li>
    <li><i class="fas fa-link-slash"></i> লিংকটি সঠিক কিনা চেক করুন — কোনো অক্ষর বাদ পড়েছে কিনা দেখুন</li>
    <li><i class="fas fa-user-shield"></i> যদি মনে হয় এটা ভুল, অ্যাডমিনকে জানান</li>
@endsection

@section('buttons')
    <a href="javascript:history.back()" class="btn-primary-err">
        <i class="fas fa-arrow-left"></i> পেছনে যান
    </a>
    <a href="{{ url('/dashboard') }}" class="btn-ghost-err">
        <i class="fas fa-house"></i> ড্যাশবোর্ড
    </a>
@endsection
