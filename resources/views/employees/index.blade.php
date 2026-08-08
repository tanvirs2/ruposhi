@extends('layouts.app')
@section('title', 'কর্মচারী')
@section('page-title', 'কর্মচারী')

@section('content')
@include('partials.page-header', ['title' => 'কর্মচারী তালিকা', 'createRoute' => route('employees.create'), 'createLabel' => 'নতুন কর্মচারী'])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম বা ফোন...">
            </div>
            <button type="submit" class="btn btn-secondary">খুঁজুন</button>
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>নাম</th><th>পদবি</th><th>ফোন</th><th>বেতন</th><th>যোগদানের তারিখ</th><th>অবস্থা</th><th>অ্যাকশন</th></tr></thead>
            <tbody>
                @forelse($employees as $emp)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td><strong>{{ $emp->name }}</strong></td>
                    <td>{{ $emp->position ?? '—' }}</td>
                    <td>{{ $emp->phone ?? '—' }}</td>
                    <td>৳ {{ number_format($emp->salary) }}</td>
                    <td>{{ $emp->join_date?->format('d M Y') ?? '—' }}</td>
                    <td>
                        @if($emp->status==='active') <span class="badge badge-green">সক্রিয়</span>
                        @else <span class="badge badge-red">নিষ্ক্রিয়</span> @endif
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('employees.edit', $emp) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('employees.destroy', $emp) }}"
                                  data-confirm-msg="{{ $emp->name }} — কর্মচারী মুছে ফেলবেন? বেতন ইতিহাস মুছে যাবে।">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো কর্মচারী পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $employees->withQueryString()->links() }}</div>
</div>
@endsection
