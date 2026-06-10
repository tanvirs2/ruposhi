{{-- Loads Bangla font face(s). $keys = font keys to load (default: shop's selected font). --}}
@php
    $fontKeys      = $keys ?? [\App\Support\BanglaFonts::currentKey()];
    $needsGoogle   = collect($fontKeys)->filter(fn($k) => $k !== 'hind_siliguri' && $k !== 'kalpurush' && isset(\App\Support\BanglaFonts::FONTS[$k]['google']))->values();
    $fontGoogleUrl = $needsGoogle->isNotEmpty()
        ? 'https://fonts.googleapis.com/css2?' . $needsGoogle->map(fn($k) => 'family=' . \App\Support\BanglaFonts::FONTS[$k]['google'])->join('&') . '&display=swap'
        : null;
@endphp

{{-- Inter (UI numbers/latin) always from Google --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

{{-- Hind Siliguri: self-hosted — loads instantly, no Google latency --}}
@if(in_array('hind_siliguri', $fontKeys))
<style>
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 300;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-300-bengali.woff2') }}') format('woff2');
    unicode-range: U+0951-0952, U+0964-0965, U+0980-09FE, U+1CD0, U+1CD2, U+1CD5-1CD6, U+1CD8, U+1CE1, U+1CEA, U+1CED, U+1CF2, U+1CF5-1CF7, U+200C-200D, U+20B9, U+25CC, U+A8F1;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 300;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-300-latin.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-400-bengali.woff2') }}') format('woff2');
    unicode-range: U+0951-0952, U+0964-0965, U+0980-09FE, U+1CD0, U+1CD2, U+1CD5-1CD6, U+1CD8, U+1CE1, U+1CEA, U+1CED, U+1CF2, U+1CF5-1CF7, U+200C-200D, U+20B9, U+25CC, U+A8F1;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 400;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-400-latin.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-500-bengali.woff2') }}') format('woff2');
    unicode-range: U+0951-0952, U+0964-0965, U+0980-09FE, U+1CD0, U+1CD2, U+1CD5-1CD6, U+1CD8, U+1CE1, U+1CEA, U+1CED, U+1CF2, U+1CF5-1CF7, U+200C-200D, U+20B9, U+25CC, U+A8F1;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 500;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-500-latin.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 600;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-600-bengali.woff2') }}') format('woff2');
    unicode-range: U+0951-0952, U+0964-0965, U+0980-09FE, U+1CD0, U+1CD2, U+1CD5-1CD6, U+1CD8, U+1CE1, U+1CEA, U+1CED, U+1CF2, U+1CF5-1CF7, U+200C-200D, U+20B9, U+25CC, U+A8F1;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 600;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-600-latin.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-700-bengali.woff2') }}') format('woff2');
    unicode-range: U+0951-0952, U+0964-0965, U+0980-09FE, U+1CD0, U+1CD2, U+1CD5-1CD6, U+1CD8, U+1CE1, U+1CEA, U+1CED, U+1CF2, U+1CF5-1CF7, U+200C-200D, U+20B9, U+25CC, U+A8F1;
}
@font-face {
    font-family: 'Hind Siliguri';
    font-style: normal;
    font-weight: 700;
    font-display: swap;
    src: url('{{ asset('fonts/hind-siliguri-700-latin.woff2') }}') format('woff2');
    unicode-range: U+0000-00FF, U+0131, U+0152-0153, U+02BB-02BC, U+02C6, U+02DA, U+02DC, U+0304, U+0308, U+0329, U+2000-206F, U+20AC, U+2122, U+2191, U+2193, U+2212, U+2215, U+FEFF, U+FFFD;
}
</style>
@endif

{{-- Kalpurush: self-hosted --}}
@if(\App\Support\BanglaFonts::needsKalpurush($fontKeys))
<style>
@font-face {
    font-family: 'Kalpurush';
    font-display: swap;
    font-style: normal;
    font-weight: 100 900;
    src: url('{{ asset('fonts/Kalpurush.woff2') }}') format('woff2');
}
</style>
@endif

{{-- Other fonts (Noto Sans Bengali, Tiro Bangla, Baloo Da 2): still from Google --}}
@if($fontGoogleUrl)
<link href="{{ $fontGoogleUrl }}" rel="stylesheet">
@endif
