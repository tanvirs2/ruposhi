@php
    // Shared unicode-range subsets (same as Hind Siliguri below)
    $rangeBn    = 'U+0951-0952,U+0964-0965,U+0980-09FE,U+1CD0,U+1CD2,U+1CD5-1CD6,U+1CD8,U+1CE1,U+1CEA,U+1CED,U+1CF2,U+1CF5-1CF7,U+200C-200D,U+20B9,U+25CC,U+A8F1';
    $rangeLatin = 'U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+2074,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD';

    $fontKey  = \App\Models\StoreConfig::get('font_family', 'hind_siliguri');
    $fontDefs = [
        'hind_siliguri'   => ['css_name' => 'Hind Siliguri',    'google' => null],
        // Ex-Google fonts — now self-hosted (bengali + latin subsets in public/fonts/{subset}-{bengali|latin}.woff2)
        'noto_sans'       => ['css_name' => 'Noto Sans Bengali', 'google' => null, 'subset' => 'noto_sans',   'variable' => true],
        'baloo_da_2'      => ['css_name' => 'Baloo Da 2',        'google' => null, 'subset' => 'baloo_da',    'variable' => true],
        'tiro_bangla'     => ['css_name' => 'Tiro Bangla',       'google' => null, 'subset' => 'tiro_bangla', 'variable' => false],
        // Self-hosted, not on Google Fonts — 'file' is the public/fonts/{file}-{weight}.woff2 slug
        'bornomala'       => ['css_name' => 'Bornomala',       'google' => null, 'file' => 'bornomala',       'weights' => [400, 700]],
        'kazi_typo'       => ['css_name' => 'Kazi Typo',       'google' => null, 'file' => 'kazi_typo',       'weights' => [400, 700]],
        'potro_sans'      => ['css_name' => 'Potro Sans Bangla', 'google' => null, 'file' => 'potro_sans',    'weights' => [400, 700]],
        'fn_shorif_lalon' => ['css_name' => 'FN Shorif Lalon', 'google' => null, 'file' => 'fn_shorif_lalon', 'weights' => [400]],
        'b52_udayan'      => ['css_name' => 'B52 Udayan',      'google' => null, 'file' => 'b52_udayan',      'weights' => [400]],
        'fn_mamun_turio'  => ['css_name' => 'FN Mamun Turio',  'google' => null, 'file' => 'fn_mamun_turio',  'weights' => [400, 700]],
        'ruhul_amin'      => ['css_name' => 'Ruhul Amin',      'google' => null, 'file' => 'ruhul_amin',      'weights' => [400]],
        'shorif_borsha'   => ['css_name' => 'Shorif Borsha',   'google' => null, 'file' => 'shorif_borsha',   'weights' => [400]],
        'fn_kornofuli'    => ['css_name' => 'FN Kornofuli',    'google' => null, 'file' => 'fn_kornofuli',    'weights' => [400]],
        'sonali_borno'    => ['css_name' => 'Sonali Borno',    'google' => null, 'file' => 'sonali_borno',    'weights' => [400]],
    ];
    $activeFont = $fontDefs[$fontKey] ?? $fontDefs['hind_siliguri'];
@endphp
@if(!empty($activeFont['subset']))
{{-- {{ $activeFont['css_name'] }} — self-hosted (was Google Fonts), bengali + latin subsets --}}
@php $sw = !empty($activeFont['variable']) ? '100 900' : '400'; @endphp
<link rel="preload" href="{{ asset('fonts/' . $activeFont['subset'] . '-bengali.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family:'{{ $activeFont['css_name'] }}'; font-style:normal; font-weight:{{ $sw }}; font-display:block;
    src:url('{{ asset('fonts/' . $activeFont['subset'] . '-bengali.woff2') }}') format('woff2'); unicode-range:{{ $rangeBn }}; }
@font-face { font-family:'{{ $activeFont['css_name'] }}'; font-style:normal; font-weight:{{ $sw }}; font-display:block;
    src:url('{{ asset('fonts/' . $activeFont['subset'] . '-latin.woff2') }}') format('woff2'); unicode-range:{{ $rangeLatin }}; }
</style>
@elseif(!empty($activeFont['file']))
{{-- {{ $activeFont['css_name'] }} — self-hosted, not on Google Fonts.
     unicode-range pins these to Bengali only. Without it the face claims
     U+0-10FFFF, so its own Latin glyphs win — and these are Bengali display
     fonts whose ASCII comma sits high like an apostrophe, which looked wrong
     next to English digits (৳ 1,987,000). Bengali range keeps ৳ (U+09F3) and
     Bengali digits on-font; digits/comma/punctuation fall to Inter. --}}
<link rel="preload" href="{{ asset('fonts/' . $activeFont['file'] . '-400.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@foreach($activeFont['weights'] as $w)
@font-face { font-family:'{{ $activeFont['css_name'] }}'; font-style:normal; font-weight:{{ $w }}; font-display:block;
    src:url('{{ asset('fonts/' . $activeFont['file'] . '-' . $w . '.woff2') }}') format('woff2'); unicode-range:{{ $rangeBn }}; }
@endforeach
</style>
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
{{-- Inter — self-hosted, Latin subset only (variable font covers all weights).
     Was 35 Google faces (all subsets) + a network round-trip on every page;
     Inter only ever renders ASCII digits/labels here, so latin is all we need.
     Fewer @font-face = faster print-preview generation (Chrome resolves fewer faces). --}}
<link rel="preload" href="{{ asset('fonts/inter-latin.woff2') }}" as="font" type="font/woff2" crossorigin>
<style>
@font-face { font-family:'Inter'; font-style:normal; font-weight:100 900; font-display:swap;
    src:url('{{ asset('fonts/inter-latin.woff2') }}') format('woff2');
    unicode-range:U+0000-00FF,U+0131,U+0152-0153,U+02BB-02BC,U+02C6,U+02DA,U+02DC,U+0304,U+0308,U+0329,U+2000-206F,U+2074,U+20AC,U+2122,U+2191,U+2193,U+2212,U+2215,U+FEFF,U+FFFD; }
:root { --bn-font: '{{ $activeFont['css_name'] }}'; }
</style>
