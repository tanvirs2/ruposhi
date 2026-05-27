{{--
    Curved (umbrella/arc) store name — uses SVG <textPath>.
    Goal: large, bold, arched header — but the curve is intentionally
    moderate so Bengali matras (ী, ু, ্) and ligatures (যুক্তাক্ষর) stay intact.

    Usage: @include('partials.store-name-arc', ['name' => $store['name'], 'size' => 44])
    Params:
        name  (string) — text to render
        size  (int)    — font-size in px (default 44, big & bold like a signboard)
        color (string) — text color (default #1f2937 — near-black for impact)
--}}
@php
    $arcId = 'arc-' . uniqid();
    $size  = $size  ?? 44;
    $color = $color ?? '#1f2937';
@endphp
<svg viewBox="0 0 700 140" preserveAspectRatio="xMidYMid meet"
     style="width:100%;max-width:640px;display:block;margin:0 auto">
    <defs>
        {{-- Moderate arch: endpoints at y=120, control y=-10 → ~65px peak.
             Enough lift to look like an umbrella while keeping per-glyph
             rotation small so Bengali shaping isn't destroyed. --}}
        <path id="{{ $arcId }}" d="M 30,120 Q 350,-10 670,120" fill="none" />
    </defs>
    <text font-size="{{ $size }}"
          font-weight="900"
          font-family="'Hind Siliguri', sans-serif"
          fill="{{ $color }}"
          stroke="{{ $color }}"
          stroke-width="0.5"
          style="paint-order:stroke fill">
        <textPath href="#{{ $arcId }}" startOffset="50%" text-anchor="middle">
            {{ $name }}
        </textPath>
    </text>
</svg>
