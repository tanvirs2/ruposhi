@extends('errors.layout')

@section('title', 'অনুমতি নেই')
@section('code', '403')
@section('code-color-from', '#f59e0b')
@section('code-color-to', '#ef4444')
@section('icon', 'fas fa-lock')
@section('icon-color', '#fbbf24')
@section('icon-glow', 'rgba(251,191,36,.4)')
@section('btn-from', '#d97706')
@section('btn-to', '#b45309')

@section('title-text', 'এই পাতায় প্রবেশের অনুমতি নেই')

@section('description')
    আপনার অ্যাকাউন্টে এই পাতা দেখার অধিকার নেই।
    ভুল অ্যাকাউন্ট দিয়ে login করা হয়ে থাকতে পারে।
@endsection

@section('steps')
    <li><i class="fas fa-right-from-bracket"></i> লগআউট করে সঠিক অ্যাকাউন্ট দিয়ে আবার login করুন</li>
    <li><i class="fas fa-house"></i> নিজের ড্যাশবোর্ডে ফিরে যান</li>
    <li><i class="fas fa-headset"></i> মনে হচ্ছে ভুল হয়েছে? অ্যাডমিনকে জানান</li>
@endsection

@section('buttons')
    <a href="{{ url('/dashboard') }}" class="btn-primary-err">
        <i class="fas fa-house"></i> ড্যাশবোর্ড
    </a>
    <a href="{{ route('logout') }}"
       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
       class="btn-ghost-err">
        <i class="fas fa-right-from-bracket"></i> লগআউট
    </a>
    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display:none">
        @csrf
    </form>
@endsection
