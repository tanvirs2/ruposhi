@extends('layouts.root')
@section('title', 'রিসেলার তালিকা')
@section('page-title', 'রিসেলার')

@section('content')
<div class="rt-card">
    <div class="rt-card-title" style="justify-content:space-between">
        <span><i class="fas fa-handshake"></i> সকল রিসেলার</span>
        <a href="{{ route('root.resellers.create') }}" class="rt-btn rt-btn-primary rt-btn-sm">
            <i class="fas fa-plus"></i> নতুন রিসেলার
        </a>
    </div>

    @if($resellers->isEmpty())
        <div class="rt-empty"><i class="fas fa-handshake"></i> কোনো রিসেলার নেই।</div>
    @else
    <table class="rt-table">
        <thead>
            <tr>
                <th>#</th>
                <th>নাম</th>
                <th>ইমেইল</th>
                <th>ক্লায়েন্ট</th>
                <th>সর্বোচ্চ শপ</th>
                <th>লাইসেন্স বাড়াতে পারে?</th>
                <th>কমিশন</th>
                <th>অ্যাকশন</th>
            </tr>
        </thead>
        <tbody>
            @foreach($resellers as $i => $r)
            @php $profile = $r->resellerProfile; @endphp
            <tr>
                <td style="color:#475569">{{ $i + 1 }}</td>
                <td>{{ $r->name }}</td>
                <td style="color:#64748b;font-size:.83rem">{{ $r->email }}</td>
                <td>{{ $profile?->shopCount() ?? 0 }}</td>
                <td>{{ $profile?->max_shops ?? '∞ (সীমাহীন)' }}</td>
                <td>
                    @if($profile?->can_extend_license)
                        <span class="rt-pill rt-pill-active">হ্যাঁ</span>
                    @else
                        <span class="rt-pill rt-pill-expired">না</span>
                    @endif
                </td>
                <td>{{ $profile?->commission_rate ?? 0 }}%</td>
                <td style="display:flex;gap:6px">
                    <a href="{{ route('root.resellers.show', $r) }}" class="rt-btn rt-btn-ghost rt-btn-sm" title="কমিশন ট্র্যাকিং">
                        <i class="fas fa-eye"></i>
                    </a>
                    <a href="{{ route('root.resellers.edit', $r) }}" class="rt-btn rt-btn-ghost rt-btn-sm">
                        <i class="fas fa-pen"></i>
                    </a>
                    <form method="POST" action="{{ route('root.resellers.destroy', $r) }}"
                          onsubmit="return confirm('{{ $r->name }} কে মুছবেন?')">
                        @csrf @method('DELETE')
                        <button class="rt-btn rt-btn-danger rt-btn-sm"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif
</div>
@endsection
