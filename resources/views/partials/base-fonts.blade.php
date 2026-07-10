{{-- Self-hosted base fonts for panels that always use Hind Siliguri (root/reseller/super/login/errors).
     Replaces Google Fonts <link> — no external fetch. Hind Siliguri (bengali+latin) + Inter (latin). --}}
<link rel="preload" href="{{ asset('fonts/hind-siliguri-400-bengali.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/inter-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@php
    $bnRange = 'U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1';
    $ltRange = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+2074,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';
@endphp
@foreach([300,400,500,600,700] as $w)
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:{{ $w }}; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-' . $w . '-bengali.woff2') }}') format('woff2'); unicode-range:{{ $bnRange }}; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:{{ $w }}; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-' . $w . '-latin.woff2') }}') format('woff2'); unicode-range:{{ $ltRange }}; }
@endforeach
@font-face { font-family:'Inter'; font-style:normal; font-weight:100 900; font-display:swap;
    src:url('{{ asset('fonts/inter-latin.woff2') }}') format('woff2'); unicode-range:{{ $ltRange }}; }
</style>
