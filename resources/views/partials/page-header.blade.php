<div class="page-header-bar">
    <div>
        <h2 class="page-h2">{{ $title }}</h2>
        @isset($subtitle)<p class="page-sub">{{ $subtitle }}</p>@endisset
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
        @isset($extraRoute)
        <a href="{{ $extraRoute }}" class="btn btn-ghost">
            <i class="fas {{ $extraIcon ?? 'fa-file-import' }}"></i> {{ $extraLabel ?? 'ইমপোর্ট' }}
        </a>
        @endisset
        @isset($createRoute)
        <a href="{{ $createRoute }}" class="btn btn-primary">
            <i class="fas fa-plus"></i> {{ $createLabel ?? 'নতুন যোগ করুন' }}
        </a>
        @endisset
    </div>
</div>
