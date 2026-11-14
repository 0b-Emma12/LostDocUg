<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_shows_private_lost_and_found_actions(): void
    {
        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('Lost a document?')
            ->assertSee('Report a lost document')
            ->assertSee('Report a found document')
            ->assertSee('No public report search');
    }

    public function test_report_creation_requires_sign_in(): void
    {
        $this->get('/report/lost')
            ->assertRedirect(route('reports.create', ['kind' => 'lost']));

        $this->get(route('reports.create', ['kind' => 'lost']))
            ->assertRedirect(route('login'));
    }

    public function test_unknown_report_kind_is_not_supported(): void
    {
        $this->get('/report/unknown')->assertNotFound();
    }
}
