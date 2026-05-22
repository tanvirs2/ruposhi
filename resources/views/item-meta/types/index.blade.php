@extends('layouts.app')
@section('title', 'আইটেম টাইপ')
@section('page-title', 'আইটেম টাইপ')

@section('content')
@include('partials.page-header', ['title' => 'টাইপ তালিকা', 'createRoute' => route('item-types.create'), 'createLabel' => 'নতুন টাইপ'])

<div class="card">
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr><th>#</th><th>নাম</th><th>আইটেম</th><th>অ্যাকশন</th></tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td><strong>{{ $row->name }}</strong></td>
                    <td><span class="badge badge-green">{{ $row->items_count }}</span></td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('item-types.edit', $row) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form method="POST" action="{{ route('item-types.destroy', $row) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="4" class="empty-row">কোনো টাইপ নেই</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
