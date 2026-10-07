<?php

namespace Tests\Feature;

use App\Models\BrandingSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BrandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    public function test_super_admin_can_view_branding_page(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('branding.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('branding/edit')
                ->where('defaults.primary_color', BrandingSetting::DefaultPrimaryColor)
                ->where('branding.primary_color', BrandingSetting::DefaultPrimaryColor)
                ->where('branding.has_custom_logo', false)
            );
    }

    public function test_non_super_admin_cannot_access_branding(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('branding.edit'))->assertForbidden();

        $this->actingAs($user)->post(route('branding.update'), [
            'primary_color' => '#ff0000',
            'light_color' => '#ffffff',
            'dark_color' => '#000000',
        ])->assertForbidden();

        $this->actingAs($user)->delete(route('branding.logo.destroy'))->assertForbidden();
        $this->actingAs($user)->delete(route('branding.favicon.destroy'))->assertForbidden();

        $this->assertSame(BrandingSetting::DefaultPrimaryColor, BrandingSetting::current()->primary_color);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('branding.edit'))->assertRedirect(route('login'));
    }

    public function test_super_admin_can_update_colors_logo_and_favicon(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('branding.update'), [
                'primary_color' => '#AA0000',
                'light_color' => '#FFF5F5',
                'dark_color' => '#330000',
                'logo' => UploadedFile::fake()->image('logo.png', 400, 120),
                'favicon' => UploadedFile::fake()->image('favicon.png', 64, 64),
            ])
            ->assertRedirect(route('branding.edit'))
            ->assertSessionHasNoErrors();

        $branding = BrandingSetting::current();

        $this->assertSame('#aa0000', $branding->primary_color);
        $this->assertSame('#fff5f5', $branding->light_color);
        $this->assertSame('#330000', $branding->dark_color);
        $this->assertTrue($branding->hasCustomLogo());
        $this->assertTrue($branding->hasCustomFavicon());
        Storage::disk('public')->assertExists($branding->logo_path);
        Storage::disk('public')->assertExists($branding->favicon_path);
        $this->assertSame(asset('storage/'.$branding->logo_path), $branding->logoUrl());
        $this->assertSame(asset('storage/'.$branding->favicon_path), $branding->faviconUrl());
    }

    public function test_uploading_new_logo_replaces_previous_file(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $colors = ['primary_color' => '#13395f', 'light_color' => '#eef3f8', 'dark_color' => '#13395f'];

        $this->actingAs($admin)->post(route('branding.update'), [
            ...$colors,
            'logo' => UploadedFile::fake()->image('first.png'),
        ]);
        $firstPath = BrandingSetting::current()->logo_path;

        $this->travel(1)->seconds();

        $this->actingAs($admin)->post(route('branding.update'), [
            ...$colors,
            'logo' => UploadedFile::fake()->image('second.png'),
        ]);
        $secondPath = BrandingSetting::current()->logo_path;

        $this->assertNotSame($firstPath, $secondPath);
        Storage::disk('public')->assertMissing($firstPath);
        Storage::disk('public')->assertExists($secondPath);
    }

    public function test_update_validates_colors_and_files(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('branding.update'), [
                'primary_color' => 'red',
                'light_color' => '#fff',
                'dark_color' => '',
                'logo' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
                'favicon' => UploadedFile::fake()->image('favicon.png')->size(1024),
            ])
            ->assertSessionHasErrors(['primary_color', 'light_color', 'dark_color', 'logo', 'favicon']);

        $this->assertSame(BrandingSetting::DefaultPrimaryColor, BrandingSetting::current()->primary_color);
    }

    public function test_super_admin_can_remove_logo_and_favicon(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('branding.update'), [
            'primary_color' => '#13395f',
            'light_color' => '#eef3f8',
            'dark_color' => '#13395f',
            'logo' => UploadedFile::fake()->image('logo.png'),
            'favicon' => UploadedFile::fake()->image('favicon.png'),
        ]);

        $branding = BrandingSetting::current();
        $logoPath = $branding->logo_path;
        $faviconPath = $branding->favicon_path;

        $this->actingAs($admin)->delete(route('branding.logo.destroy'))->assertRedirect(route('branding.edit'));
        $this->actingAs($admin)->delete(route('branding.favicon.destroy'))->assertRedirect(route('branding.edit'));

        $branding = BrandingSetting::current();

        $this->assertNull($branding->logo_path);
        $this->assertNull($branding->favicon_path);
        Storage::disk('public')->assertMissing($logoPath);
        Storage::disk('public')->assertMissing($faviconPath);
        $this->assertSame(asset('img/rdi-logo.svg'), $branding->logoUrl());
        $this->assertSame(asset('img/favicon.png'), $branding->faviconUrl());
    }

    public function test_branding_is_shared_with_inertia_pages(): void
    {
        BrandingSetting::current()->update(['primary_color' => '#123456']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('branding.edit'))
            ->assertInertia(fn (Assert $page) => $page
                ->where('branding.primary_color', '#123456')
                ->where('branding.logo_url', asset('img/rdi-logo.svg'))
                ->where('branding.favicon_url', asset('img/favicon.png'))
            );
    }

    public function test_mail_uses_brand_colors_and_logo(): void
    {
        BrandingSetting::current()->update(['primary_color' => '#aa0000', 'dark_color' => '#220000']);

        $html = (string) (new MailMessage)
            ->line('Hola')
            ->action('Ver detalle', 'https://example.com')
            ->render();

        $this->assertStringContainsString('#aa0000', $html);
        $this->assertStringContainsString('#220000', $html);
        $this->assertStringContainsString(asset('img/favicon.png'), $html);
    }

    public function test_root_view_renders_brand_colors_and_favicon(): void
    {
        BrandingSetting::current()->update(['primary_color' => '#abcdef', 'dark_color' => '#101010']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get(route('branding.edit'))
            ->assertSee('--brand-primary: #abcdef', false)
            ->assertSee('--brand-dark: #101010', false)
            ->assertSee('<link rel="icon" href="'.asset('img/favicon.png').'">', false);
    }
}
