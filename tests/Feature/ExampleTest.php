<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_guests_see_welcome_page(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Welcome'));
    }

    public function test_login_page_is_available(): void
    {
        $this->get('/login')->assertOk();
    }
}
