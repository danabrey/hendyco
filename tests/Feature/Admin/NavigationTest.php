<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_menu_items_are_grouped_in_order(): void
    {
        $this->actingAs(User::factory()->create());

        $this->get('/admin')->assertOk()->assertSeeInOrder([
            'Clients &amp; bookings', 'Clients', 'Bookings', 'Calendar', 'Gift Cards',
            'Money', 'Expenses', 'Mileage Logs',
            'Reports', 'UC report', 'Business reports',
            'Settings', 'Services', 'Expense Categories',
        ], false);
    }

    public function test_panel_uses_the_hendy_and_co_branding(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('Hendy &amp; Co.', false)->assertSee('logo-mark.png', false);
    }
}
