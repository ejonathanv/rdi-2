<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_landing_page_is_publicly_accessible(): void
    {
        $this->get(route('landingpage'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('landingpage'));
    }
}
