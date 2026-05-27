{{--
    Curved (umbrella/arc) store name — uses SVG <textPath>.
    Curve is intentionally GENTLE because Bengali script has matras (ি ী ু ্)
    and ligatures (যুক্তাক্ষর). A sharp arc per-glyph rotates each Unicode
    code point and breaks combined-character rendering.

    Usage: @include('partials.store-name-arc', ['name' => $store['name'], 'size' => 32])
    Params:
        name  (string)   — text to render
        size  (int|null) — font-size in px (default 30)
        color (string)   — text color (default currentColor)
--}}
@php
    $arcId = 'arc-' . uniqid();
    $size  = $size  ?? 30;
    $color = $color ?? 'currentColor';
@endphp
<svg viewBox="0 0 600 90" preserveAspectRatio="xMidYMid meet"
     style="width:100%;max-width:540px;display:block;margin:0 auto">
    <defs>
        {{-- Gentle arc: endpoints at y=72, control y=18 → ~28px peak above baseline.
             Subtle enough that adjacent Bengali glyphs stay aligned. --}}
        <path id="{{ $arcId }}" d="M 40,72 Q 300,18 560,72" fill="none" />
    </defs>
    <text font-size="{{ $size }}" font-weight="800"
          font-family="'Hind Siliguri', sans-serif"
          fill="{{ $color }}">
        <textPath href="#{{ $arcId }}" startOffset="50%" text-anchor="middle">
            {{ $name }}
        </textPath>
    </text>
</svg>
