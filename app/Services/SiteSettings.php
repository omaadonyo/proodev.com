<?php

namespace App\Services;

use App\Models\SiteSetting;
use Illuminate\Support\Facades\Cache;

/**
 * Lightweight typed access to admin-editable site-wide settings
 * (SEO keywords, meta description, ...). Values are cached briefly
 * so they can be read from hot rendering paths (public pages, head partials)
 * without hammering the database.
 */
class SiteSettings
{
    /**
     * Get a setting value, falling back to the given default.
     */
    public function get(string $key, ?string $default = null): ?string
    {
        $value = Cache::remember('site_setting.'.$key, 60, fn () => SiteSetting::where('key', $key)->value('value'));

        return $value ?? $default;
    }

    /**
     * Persist a setting and clear its cached value.
     */
    public function set(string $key, string $value): void
    {
        SiteSetting::updateOrCreate(['key' => $key], ['value' => $value]);

        Cache::forget('site_setting.'.$key);
    }

    /**
     * The admin-editable SEO meta keywords, or the app default.
     */
    public function metaKeywords(): string
    {
        return (string) $this->get('seo.meta_keywords', 'proodev, engineering magnitude, evidence-backed portfolio, engineer, ai engineering analysis');
    }
}
