{{-- Loads Bangla font face(s). $keys = font keys to load (default: shop's selected font). --}}
@php
    $fontKeys = $keys ?? [\App\Support\BanglaFonts::currentKey()];
    $fontGoogleUrl = \App\Support\BanglaFonts::googleUrl($fontKeys);
@endphp
@if($fontGoogleUrl)
    <link href="{{ $fontGoogleUrl }}&family=Inter:wght@300;400;500;600;700" rel="stylesheet">
@else
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
@endif
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
