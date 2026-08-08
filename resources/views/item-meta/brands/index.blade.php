@extends('layouts.app')
@section('title', 'ব্র্যান্ড')
@section('page-title', 'ব্র্যান্ড')

@section('content')
@include('partials.page-header', ['title' => 'ব্র্যান্ড তালিকা', 'createRoute' => route('brands.create'), 'createLabel' => 'নতুন ব্র্যান্ড'])

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>নাম</th><th>বিবরণ</th><th>আইটেম</th><th>অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->name }}</strong></td>
                    <td>{{ $row->description ?? '—' }}</td>
                    <td><span class="badge badge-green">{{ $row->items_count }}</span></td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('brands.edit', $row) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form class="admin-only" method="POST" action="{{ route('brands.destroy', $row) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-row">কোনো ব্র্যান্ড নেই</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
