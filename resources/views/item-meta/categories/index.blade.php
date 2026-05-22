@extends('layouts.app')
@section('title', 'আইটেম ক্যাটাগরি')
@section('page-title', 'আইটেম ক্যাটাগরি')

@section('content')
@include('partials.page-header', ['title' => 'ক্যাটাগরি তালিকা', 'createRoute' => route('categories.create'), 'createLabel' => 'নতুন ক্যাটাগরি'])

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
                            <a href="{{ route('categories.edit', $row) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form method="POST" action="{{ route('categories.destroy', $row) }}" onsubmit="return confirm('মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="5" class="empty-row">কোনো ক্যাটাগরি নেই</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
