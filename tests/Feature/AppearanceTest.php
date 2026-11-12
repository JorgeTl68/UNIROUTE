<?php

namespace Tests\Feature;

use Tests\TestCase;

class AppearanceTest extends TestCase
{
    public function test_the_app_is_dark_by_default(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('class="dark"', false);
    }

    public function test_the_chosen_light_theme_is_applied_by_the_server(): void
    {
        $this->withUnencryptedCookie('appearance', 'light')
            ->get(route('login'))
            ->assertOk()
            ->assertDontSee('class="dark"', false);
    }

    public function test_following_the_system_starts_dark_and_checks_the_system_preference(): void
    {
        $this->withUnencryptedCookie('appearance', 'system')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('class="dark"', false)
            ->assertSee('prefers-color-scheme: dark', false);
    }

    public function test_an_unknown_value_falls_back_to_dark(): void
    {
        $this->withUnencryptedCookie('appearance', '"><script>')
            ->get(route('login'))
            ->assertOk()
            ->assertSee('class="dark"', false)
            ->assertDontSee('"><script>', false);
    }
}
