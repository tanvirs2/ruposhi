@extends('layouts.app')
@section('title', 'ব্যবহারকারী')
@section('page-title', 'ব্যবহারকারী ব্যবস্থাপনা')

@section('content')
@include('partials.page-header', [
    'title'       => 'ব্যবহারকারী তালিকা',
    'subtitle'    => 'এই শপের লগইন অ্যাকাউন্টসমূহ',
    'createRoute' => route('users.create'),
    'createLabel' => 'নতুন ব্যবহারকারী',
])

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>নাম</th><th>ইমেইল</th><th>রোল</th><th>অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($users as $u)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $u->name }}</strong>
                        @if($u->id === auth()->id())
                            <span class="badge badge-blue" style="margin-right:4px">আপনি</span>
                        @endif
                    </td>
                    <td>{{ $u->email }}</td>
                    <td>
                        @if($u->role === 'admin')
                            <span class="badge badge-green"><i class="fas fa-user-shield"></i> অ্যাডমিন</span>
                        @else
                            <span class="badge badge-gray"><i class="fas fa-user"></i> স্টাফ</span>
                        @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('users.edit', $u) }}" class="btn-icon-sm" title="সম্পাদনা"><i class="fas fa-pen"></i></a>
                            @if($u->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $u) }}"
                                  data-confirm-msg="{{ $u->name }} ({{ $u->role }}) — ব্যবহারকারী মুছে ফেলবেন? লগইন অ্যাক্সেস চলে যাবে।">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger" title="মুছুন"><i class="fas fa-trash"></i></button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-row">কোনো ব্যবহারকারী নেই</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
