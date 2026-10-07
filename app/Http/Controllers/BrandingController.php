<?php

namespace App\Http\Controllers;

use App\Http\Requests\Branding\UpdateBrandingRequest;
use App\Models\BrandingSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BrandingController extends Controller
{
    public function edit(Request $request): Response
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        return Inertia::render('branding/edit', [
            'defaults' => [
                'primary_color' => BrandingSetting::DefaultPrimaryColor,
                'light_color' => BrandingSetting::DefaultLightColor,
                'dark_color' => BrandingSetting::DefaultDarkColor,
            ],
        ]);
    }

    public function update(UpdateBrandingRequest $request): RedirectResponse
    {
        $branding = BrandingSetting::current();

        $attributes = [
            'primary_color' => strtolower($request->validated('primary_color')),
            'light_color' => strtolower($request->validated('light_color')),
            'dark_color' => strtolower($request->validated('dark_color')),
        ];

        if ($request->hasFile('logo')) {
            $attributes['logo_path'] = $this->storeAsset($request->file('logo'), 'logo', $branding->logo_path);
        }

        if ($request->hasFile('favicon')) {
            $attributes['favicon_path'] = $this->storeAsset($request->file('favicon'), 'favicon', $branding->favicon_path);
        }

        $branding->update($attributes);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Imagen corporativa actualizada.')]);

        return to_route('branding.edit');
    }

    public function destroyLogo(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $branding = BrandingSetting::current();
        $this->deleteAsset($branding->logo_path);
        $branding->update(['logo_path' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Logotipo eliminado.')]);

        return to_route('branding.edit');
    }

    public function destroyFavicon(Request $request): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $branding = BrandingSetting::current();
        $this->deleteAsset($branding->favicon_path);
        $branding->update(['favicon_path' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Favicon eliminado.')]);

        return to_route('branding.edit');
    }

    private function storeAsset(UploadedFile $file, string $prefix, ?string $previousPath): string
    {
        $path = $file->storeAs(
            'branding',
            sprintf('%s-%d.%s', $prefix, now()->getTimestampMs(), strtolower($file->getClientOriginalExtension())),
            'public',
        );

        $this->deleteAsset($previousPath);

        return $path;
    }

    private function deleteAsset(?string $path): void
    {
        if ($path !== null) {
            Storage::disk('public')->delete($path);
        }
    }
}
