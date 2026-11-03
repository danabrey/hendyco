<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Services\Pages\ManageServices;
use App\Models\Service;
use App\Models\User;
use Database\Seeders\ServiceSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ServiceResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin/services')->assertRedirect('/admin/login');
    }

    public function test_user_can_create_a_service_with_price_stored_in_pence(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(ManageServices::class)
            ->callAction('create', ['name' => 'Group walk', 'default_price_pence' => 12.5, 'default_duration_minutes' => 60])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', ['name' => 'Group walk', 'default_price_pence' => 1250, 'default_duration_minutes' => 60]);
    }

    public function test_user_can_edit_a_service(): void
    {
        $this->actingAs(User::factory()->create());
        $service = Service::factory()->create(['default_price_pence' => 1000]);

        Livewire::test(ManageServices::class)
            ->callTableAction('edit', $service, ['name' => 'Renamed', 'default_price_pence' => 15])
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('services', ['id' => $service->id, 'name' => 'Renamed', 'default_price_pence' => 1500]);
    }

    public function test_seeder_creates_meet_and_greet_at_zero_pounds(): void
    {
        $this->seed(ServiceSeeder::class);
        $this->seed(ServiceSeeder::class);

        $this->assertDatabaseCount('services', 1);
        $this->assertDatabaseHas('services', ['name' => 'Meet & Greet', 'default_price_pence' => 0]);
    }
}
