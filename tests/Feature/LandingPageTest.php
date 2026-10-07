<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_is_publicly_accessible(): void
    {
        $this->get(route('landingpage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('landingpage'));
    }
}
