{{--
    Curved (umbrella/arc) store name — uses SVG <textPath>.
    Usage: @include('partials.store-name-arc', ['name' => $store['name'], 'size' => 32])
    Params:
        name  (string)   — text to render
        size  (int|null) — font-size in px (default 30)
        color (string)   — text color (default currentColor)
--}}
@php
    $arcId  = 'arc-' . uniqid();
    $size   = $size  ?? 30;
    $color  = $color ?? 'currentColor';
    // viewBox is 600 wide × 160 tall — taller box so the umbrella curve has room to rise
    // Quadratic Bezier: endpoints near bottom (y=140), control point above top (y=-60)
    // Effective peak height = 140 - ((140 + (-60)) / 2) ≈ 100px of upward arch
@endphp
<svg viewBox="0 0 600 160" preserveAspectRatio="xMidYMid meet"
     style="width:100%;max-width:520px;display:block;margin:0 auto">
    <defs>
        <path id="{{ $arcId }}" d="M 50,140 Q 300,-60 550,140" fill="none" />
    </defs>
    <text font-size="{{ $size }}" font-weight="800"
          font-family="'Hind Siliguri', sans-serif"
          fill="{{ $color }}"
          style="letter-spacing:.02em">
        <textPath href="#{{ $arcId }}" startOffset="50%" text-anchor="middle">
            {{ $name }}
        </textPath>
    </text>
</svg>
