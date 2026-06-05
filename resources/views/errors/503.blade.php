@extends('errors.layout')

@section('title', 'রক্ষণাবেক্ষণ চলছে')
@section('code', '503')
@section('code-color-from', '#8b5cf6')
@section('code-color-to', '#6d28d9')
@section('icon', 'fas fa-screwdriver-wrench')
@section('icon-color', '#a78bfa')
@section('icon-glow', 'rgba(167,139,250,.4)')
@section('btn-from', '#7c3aed')
@section('btn-to', '#6d28d9')

@section('title-text', 'সাময়িক রক্ষণাবেক্ষণ চলছে')

@section('description')
    সফটওয়্যারটি এই মুহূর্তে আপডেট ও রক্ষণাবেক্ষণের জন্য সাময়িকভাবে বন্ধ আছে।
    শীঘ্রই আবার চালু হবে। অসুবিধার জন্য দুঃখিত।
@endsection

@section('steps')
    <li><i class="fas fa-clock"></i> কিছুক্ষণ পরে আবার চেষ্টা করুন</li>
    <li><i class="fas fa-rotate-right"></i> পাতাটি রিফ্রেশ করুন (F5)</li>
    <li><i class="fas fa-headset"></i> জরুরি প্রয়োজনে অ্যাডমিনের সাথে যোগাযোগ করুন</li>
@endsection

@section('buttons')
    <a href="javascript:location.reload()" class="btn-primary-err">
        <i class="fas fa-rotate-right"></i> আবার চেষ্টা করুন
    </a>
@endsection
