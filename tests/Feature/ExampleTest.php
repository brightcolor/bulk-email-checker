<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_login_or_dashboard(): void
    {
        $response = $this->get('/');
        // Root redirects (to login or dashboard)
        $response->assertRedirect();
    }
}
