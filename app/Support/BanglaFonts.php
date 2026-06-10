<?php

namespace App\Support;

use App\Models\StoreConfig;

/**
 * Bangla UI font registry. Per-shop font is stored in store_config
 * under key `app_font`. Kalpurush is self-hosted (public/fonts/),
 * the rest load from Google Fonts.
 */
class BanglaFonts
{
    public const DEFAULT = 'hind_siliguri';

    public const FONTS = [
        'hind_siliguri' => [
            'label'  => 'Hind Siliguri',
            'family' => "'Hind Siliguri', sans-serif",
            'google' => 'Hind+Siliguri:wght@300;400;500;600;700',
        ],
        'kalpurush' => [
            'label'  => 'কালপুরুষ (Kalpurush)',
            'family' => "'Kalpurush', sans-serif",
            'google' => null, // self-hosted: public/fonts/Kalpurush.woff2
        ],
        'noto_sans_bengali' => [
            'label'  => 'Noto Sans Bengali',
            'family' => "'Noto Sans Bengali', sans-serif",
            'google' => 'Noto+Sans+Bengali:wght@300;400;500;600;700',
        ],
        'tiro_bangla' => [
            'label'  => 'Tiro Bangla',
            'family' => "'Tiro Bangla', serif",
            'google' => 'Tiro+Bangla',
        ],
        'baloo_da_2' => [
            'label'  => 'Baloo Da 2',
            'family' => "'Baloo Da 2', sans-serif",
            'google' => 'Baloo+Da+2:wght@400;500;600;700',
        ],
    ];

    public static function keys(): array
    {
        return array_keys(self::FONTS);
    }

    /** Currently selected font key for the active shop. */
    public static function currentKey(): string
    {
        $key = StoreConfig::get('app_font', self::DEFAULT);
        return isset(self::FONTS[$key]) ? $key : self::DEFAULT;
    }

    /** Currently selected font definition (label/family/google). */
    public static function current(): array
    {
        return self::FONTS[self::currentKey()];
    }

    /** Google Fonts URL for the given keys (null if none need Google). */
    public static function googleUrl(array $keys): ?string
    {
        $families = [];
        foreach ($keys as $key) {
            $g = self::FONTS[$key]['google'] ?? null;
            if ($g) $families[] = 'family=' . $g;
        }
        if (!$families) return null;
        return 'https://fonts.googleapis.com/css2?' . implode('&', $families) . '&display=swap';
    }

    /** Whether any of the given keys needs the self-hosted Kalpurush face. */
    public static function needsKalpurush(array $keys): bool
    {
        return in_array('kalpurush', $keys, true);
    }
}
