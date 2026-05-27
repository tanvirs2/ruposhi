{{--
    Curved (umbrella/arc) store name — uses SVG <textPath>.
    Usage: @include('partials.store-name-arc', ['name' => $store['name'], 'size' => 32])
    Params:
        name  (string)   — text to render
        size  (int|null) — font-size in px (default 32)
        color (string)   — text color (default currentColor)
--}}
@php
    $arcId  = 'arc-' . uniqid();
    $size   = $size  ?? 32;
    $color  = $color ?? 'currentColor';
    // SVG viewBox sized so the arc rises ~30px from baseline
    // width 600 viewBox, scales fluidly with container width
@endphp
<svg viewBox="0 0 600 90" preserveAspectRatio="xMidYMid meet"
     style="width:100%;max-width:480px;display:block;margin:0 auto;overflow:visible">
    <defs>
        <path id="{{ $arcId }}" d="M 30,80 Q 300,-5 570,80" fill="none" />
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
