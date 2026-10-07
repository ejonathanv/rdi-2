<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property string $primary_color
 * @property string $light_color
 * @property string $dark_color
 * @property string|null $logo_path
 * @property string|null $favicon_path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['primary_color', 'light_color', 'dark_color', 'logo_path', 'favicon_path'])]
class BrandingSetting extends Model
{
    public const string DefaultPrimaryColor = '#13395f';

    public const string DefaultLightColor = '#eef3f8';

    public const string DefaultDarkColor = '#13395f';

    private const string CacheKey = 'branding_settings';

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CacheKey));
        static::deleted(fn () => Cache::forget(self::CacheKey));
    }

    public static function current(): self
    {
        $attributes = Cache::get(self::CacheKey);

        if (! is_array($attributes)) {
            $attributes = self::query()->firstOrCreate([], [
                'primary_color' => self::DefaultPrimaryColor,
                'light_color' => self::DefaultLightColor,
                'dark_color' => self::DefaultDarkColor,
            ])->getAttributes();

            Cache::forever(self::CacheKey, $attributes);
        }

        return (new self)->newFromBuilder($attributes);
    }

    public function hasCustomLogo(): bool
    {
        return $this->logo_path !== null && Storage::disk('public')->exists($this->logo_path);
    }

    public function hasCustomFavicon(): bool
    {
        return $this->favicon_path !== null && Storage::disk('public')->exists($this->favicon_path);
    }

    public function logoUrl(): string
    {
        return $this->hasCustomLogo()
            ? asset('storage/'.$this->logo_path)
            : asset('img/rdi-logo.svg');
    }

    /**
     * Raster logo URL for clients that cannot render SVG (e.g. email).
     */
    public function rasterLogoUrl(): string
    {
        return $this->hasCustomLogo()
            ? asset('storage/'.$this->logo_path)
            : asset('img/favicon.png');
    }

    public function logoAbsolutePath(): string
    {
        return $this->hasCustomLogo()
            ? Storage::disk('public')->path($this->logo_path)
            : public_path('img/favicon.png');
    }

    public function faviconUrl(): string
    {
        return $this->hasCustomFavicon()
            ? asset('storage/'.$this->favicon_path)
            : asset('img/favicon.png');
    }

    /**
     * @return array{primary_color: string, light_color: string, dark_color: string, logo_url: string, favicon_url: string, has_custom_logo: bool, has_custom_favicon: bool}
     */
    public function toSharedArray(): array
    {
        return [
            'primary_color' => $this->primary_color,
            'light_color' => $this->light_color,
            'dark_color' => $this->dark_color,
            'logo_url' => $this->logoUrl(),
            'favicon_url' => $this->faviconUrl(),
            'has_custom_logo' => $this->hasCustomLogo(),
            'has_custom_favicon' => $this->hasCustomFavicon(),
        ];
    }
}
