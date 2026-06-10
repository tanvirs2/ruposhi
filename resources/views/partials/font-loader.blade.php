{{-- All Bengali fonts are self-hosted — no Google Fonts latency for Bengali text.
     Inter (UI numbers/latin) still loads from Google. --}}
@php
    $fontKeys = $keys ?? [\App\Support\BanglaFonts::currentKey()];
@endphp

{{-- Inter: UI numbers & latin --}}
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

@if(in_array('hind_siliguri', $fontKeys))
<style>
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:300; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-300-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:300; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-300-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:400; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-400-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:400; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-400-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:500; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-500-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:500; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-500-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:600; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-600-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:600; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-600-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:700; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-700-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:700; font-display:swap;
    src:url('{{ asset('fonts/hind-siliguri-700-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
</style>
@endif

@if(in_array('kalpurush', $fontKeys))
<style>
@font-face { font-family:'Kalpurush'; font-style:normal; font-weight:100 900; font-display:swap;
    src:url('{{ asset('fonts/Kalpurush.woff2') }}') format('woff2'); }
</style>
@endif

@if(in_array('noto_sans_bengali', $fontKeys))
<style>
@font-face { font-family:'Noto Sans Bengali'; font-style:normal; font-weight:100 900; font-display:swap;
    src:url('{{ asset('fonts/noto-sans-bengali-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Noto Sans Bengali'; font-style:normal; font-weight:100 900; font-display:swap;
    src:url('{{ asset('fonts/noto-sans-bengali-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
</style>
@endif

@if(in_array('tiro_bangla', $fontKeys))
<style>
@font-face { font-family:'Tiro Bangla'; font-style:normal; font-weight:400; font-display:swap;
    src:url('{{ asset('fonts/tiro-bangla-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Tiro Bangla'; font-style:normal; font-weight:400; font-display:swap;
    src:url('{{ asset('fonts/tiro-bangla-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
</style>
@endif

@if(in_array('baloo_da_2', $fontKeys))
<style>
@font-face { font-family:'Baloo Da 2'; font-style:normal; font-weight:400 700; font-display:swap;
    src:url('{{ asset('fonts/baloo-da-2-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Baloo Da 2'; font-style:normal; font-weight:400 700; font-display:swap;
    src:url('{{ asset('fonts/baloo-da-2-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
</style>
@endif
