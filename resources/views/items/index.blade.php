@extends('layouts.app')
@section('title', 'আইটেমস')
@section('page-title', 'আইটেমস')

@section('content')
@include('partials.page-header', ['title' => 'আইটেম তালিকা', 'createRoute' => route('items.create'), 'createLabel' => 'নতুন আইটেম'])

<div class="card">
    <div class="card-filter">
        <form method="GET" class="filter-form">
            <div class="search-box"><i class="fas fa-search"></i>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="নাম বা কোড...">
            </div>
            <select name="category_id" class="form-select">
                <option value="">সব ক্যাটাগরি</option>
                @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" @selected(request('category_id') == $cat->id)>{{ $cat->name }}</option>
                @endforeach
            </select>
            <select name="brand_id" class="form-select">
                <option value="">সব ব্র্যান্ড</option>
                @foreach($itemBrands as $brand)
                    <option value="{{ $brand->id }}" @selected(request('brand_id') == $brand->id)>{{ $brand->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-secondary">ফিল্টার</button>
            @if(request()->hasAny(['search','category_id','brand_id']))
                <a href="{{ route('items.index') }}" class="btn btn-ghost">পরিষ্কার</a>
            @endif
        </form>
    </div>
    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>নাম</th>
                    <th>ব্র্যান্ড</th>
                    <th>ক্যাটাগরি</th>
                    <th>ক্রয় মূল্য</th>
                    <th>বিক্রয় মূল্য</th>
                    <th>স্টক</th>
                    <th>অ্যাকশন</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                <tr>
                    <td class="mono">{{ $loop->iteration }}</td>
                    <td>
                        <strong>{{ $item->name }}</strong>
                        @if($item->code)
                            <div style="font-size:.75rem;color:#94a3b8" class="mono">{{ $item->code }}</div>
                        @endif
                    </td>
                    <td>{{ $item->itemBrand?->name ?? '—' }}</td>
                    <td>{{ $item->category?->name ?? '—' }}</td>
                    <td>৳ {{ number_format($item->purchase_price, 2) }}</td>
                    <td>৳ {{ number_format($item->sale_price, 2) }}</td>
                    <td>
                        @php $qty = $item->stock?->quantity ?? 0; $low = $item->stock?->isLow(); @endphp
                        <span class="badge {{ $low ? 'badge-red' : 'badge-green' }}">
                            {{ $qty }} {{ $item->unitType?->short ?? $item->unit }}
                        </span>
                    </td>
                    <td>
                        <div class="action-btns">
                            <a href="{{ route('items.edit', $item) }}" class="btn-icon-sm"><i class="fas fa-pen"></i></a>
                            <form method="POST" action="{{ route('items.destroy', $item) }}" onsubmit="return confirm('এই আইটেম মুছে ফেলবেন?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn-icon-sm btn-icon-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr><td colspan="8" class="empty-row">কোনো আইটেম পাওয়া যায়নি</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="pagination-wrap">{{ $items->withQueryString()->links() }}</div>
</div>
@endsection
