<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class StoreConfig extends Model
{
    protected $table = 'store_config';
    protected $fillable = ['key', 'value'];

    /**
     * Get a config value — cached for 24h to avoid repeated DB hits.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("store_config:{$key}", 86400, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }

    /**
     * Set a config value and clear its cache entry.
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("store_config:{$key}");
    }

    /**
     * Clear all store config cache (call after bulk settings update).
     */
    public static function clearCache(): void
    {
        $keys = static::pluck('key');
        foreach ($keys as $key) {
            Cache::forget("store_config:{$key}");
        }
    }
}
