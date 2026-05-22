<div class="page-header-bar">
    <div>
        <h2 class="page-h2">{{ $title }}</h2>
        @isset($subtitle)<p class="page-sub">{{ $subtitle }}</p>@endisset
    </div>
    @isset($createRoute)
    <a href="{{ $createRoute }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> {{ $createLabel ?? 'নতুন যোগ করুন' }}
    </a>
    @endisset
</div>
