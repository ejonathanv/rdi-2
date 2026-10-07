<?php

namespace Tests\Feature;

use App\Enums\AreaRole;
use App\Models\Area;
use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PwaTest extends TestCase
{
    use RefreshDatabase;

    public function test_manifest_uses_branding_and_default_icons(): void
    {
        BrandingSetting::current()->update(['dark_color' => '#112233']);

        $this->get(route('pwa.manifest'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/manifest+json')
            ->assertJsonPath('name', 'RDI')
            ->assertJsonPath('short_name', 'RDI')
            ->assertJsonPath('start_url', '/guardia/app')
            ->assertJsonPath('display', 'standalone')
            ->assertJsonPath('theme_color', '#112233')
            ->assertJsonPath('icons.0.src', asset('img/icon-192.png'))
            ->assertJsonPath('icons.0.sizes', '192x192');
    }

    public function test_uploading_png_favicon_generates_pwa_icons_used_by_manifest(): void
    {
        Storage::fake('public');
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('branding.update'), [
            'primary_color' => '#13395f',
            'light_color' => '#eef3f8',
            'dark_color' => '#13395f',
            'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
        ])->assertSessionHasNoErrors();

        $branding = BrandingSetting::current();

        foreach (BrandingSetting::PwaIconSizes as $size) {
            Storage::disk('public')->assertExists((string) $branding->pwaIconPath($size));
        }

        $this->get(route('pwa.manifest'))
            ->assertJsonPath('icons.0.src', asset('storage/'.$branding->pwaIconPath(192)));

        $this->actingAs($admin)->delete(route('branding.favicon.destroy'));

        Storage::disk('public')->assertMissing((string) $branding->pwaIconPath(192));
    }

    public function test_app_layout_links_manifest(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('<link rel="manifest" href="/manifest.webmanifest">', false)
            ->assertSee('name="theme-color"', false);
    }

    public function test_guard_can_open_offline_app_shell(): void
    {
        $area = Area::factory()->create();
        $guard = User::factory()->create();
        $guard->areas()->attach($area->id, ['role' => AreaRole::Guard->value]);

        $this->actingAs($guard)
            ->get(route('guard.app'))
            ->assertOk()
            ->assertSee('id="guard-app"', false)
            ->assertSee('name="csrf-token"', false);
    }

    public function test_non_guard_is_redirected_from_offline_app_to_home(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('guard.app'))
            ->assertRedirect(route('dashboard', absolute: false));
    }
}
