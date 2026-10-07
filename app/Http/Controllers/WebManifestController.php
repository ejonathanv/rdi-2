<?php

namespace App\Http\Controllers;

use App\Models\BrandingSetting;
use Illuminate\Http\JsonResponse;

class WebManifestController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $branding = BrandingSetting::current();

        $icons = collect(BrandingSetting::PwaIconSizes)
            ->flatMap(fn (int $size) => [
                [
                    'src' => $branding->pwaIconUrl($size),
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => $branding->pwaIconUrl($size),
                    'sizes' => "{$size}x{$size}",
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ])
            ->values()
            ->all();

        return response()
            ->json([
                'id' => '/guardia/app',
                'name' => BrandingSetting::PwaAppName,
                'short_name' => BrandingSetting::PwaAppName,
                'description' => 'Reporte de Incidentes: recorridos e incidencias, con o sin conexión.',
                'lang' => 'es',
                'start_url' => '/guardia/app',
                'scope' => '/',
                'display' => 'standalone',
                'orientation' => 'portrait',
                'background_color' => $branding->dark_color,
                'theme_color' => $branding->dark_color,
                'icons' => $icons,
            ], options: JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            ->header('Content-Type', 'application/manifest+json');
    }
}
