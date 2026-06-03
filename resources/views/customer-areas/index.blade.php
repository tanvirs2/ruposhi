@extends('layouts.app')
@section('title', 'কাস্টমার এরিয়া')
@section('page-title', 'কাস্টমার এরিয়া')

@section('content')
@include('partials.page-header', ['title' => 'এরিয়া তালিকা', 'createRoute' => route('customer-areas.create'), 'createLabel' => 'নতুন এরিয়া'])

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead><tr><th>#</th><th>এরিয়ার নাম</th><th>কাস্টমার সংখ্যা</th><th>অ্যাকশন</th></tr></thead>
            <tbody>
                @forelse($areas as $i => $area)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $area->name }}</strong></td>
                    <td>{{ $area->customers_count }} জন</td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('customer-areas.edit', $area) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('customer-areas.destroy', $area) }}"
                                onsubmit="return confirm('এই এরিয়া মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-row">কোনো এরিয়া যোগ করা হয়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
