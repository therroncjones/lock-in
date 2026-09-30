<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_the_landing_page_redirects_to_the_admin_login(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Enter a one rep max to see the loads.');
    }
}
