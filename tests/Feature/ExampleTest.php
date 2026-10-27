<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        \Illuminate\Support\Facades\DB::purge('sqlite');
        (require database_path('migrations/2026_10_27_000001_create_department_budgets.php'))->up();
    }

    /** Verify the public entry point routes users to login. */
    public function test_the_application_redirects_to_admin_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('admin.login'));
    }
}
