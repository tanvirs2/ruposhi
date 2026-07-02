@php
    $fontKey  = \App\Models\StoreConfig::get('font_family', 'hind_siliguri');
    $fontDefs = [
        'hind_siliguri' => ['css_name' => 'Hind Siliguri',    'google' => null],
        'noto_sans'     => ['css_name' => 'Noto Sans Bengali', 'google' => 'Noto+Sans+Bengali:wght@300;400;500;600;700'],
        'baloo_da_2'    => ['css_name' => 'Baloo Da 2',        'google' => 'Baloo+Da+2:wght@400;500;600;700;800'],
        'tiro_bangla'   => ['css_name' => 'Tiro Bangla',       'google' => 'Tiro+Bangla:ital,wght@0,400;1,400'],
    ];
    $activeFont = $fontDefs[$fontKey] ?? $fontDefs['hind_siliguri'];
@endphp
@if($activeFont['google'])
<link href="https://fonts.googleapis.com/css2?family={{ $activeFont['google'] }}&display=swap" rel="stylesheet">
@else
{{-- Hind Siliguri — self-hosted, all weights, Bengali + Latin subsets --}}
<link rel="preload" href="{{ asset('fonts/hind-siliguri-400-bengali.woff2') }}" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="{{ asset('fonts/hind-siliguri-600-bengali.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:300; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-300-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:300; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-300-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:400; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-400-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:400; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-400-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:500; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-500-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:500; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-500-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:600; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-600-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:600; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-600-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:700; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-700-bengali.woff2') }}') format('woff2');
    unicode-range:U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1; }
@font-face { font-family:'Hind Siliguri'; font-style:normal; font-weight:700; font-display:block;
    src:url('{{ asset('fonts/hind-siliguri-700-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
</style>
@endif
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<style>:root { --bn-font: '{{ $activeFont['css_name'] }}'; }</style>
