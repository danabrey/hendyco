<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Filament\Resources\Clients\Pages\EditClient;
use App\Filament\Resources\Clients\Pages\ListClients;
use App\Filament\Resources\Clients\Pages\ViewClient;
use App\Filament\Resources\Clients\RelationManagers\DogsRelationManager;
use App\Models\Client;
use App\Models\Dog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ClientResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/clients')->assertRedirect('/admin/login');
    }

    public function test_login_page_exists_and_registration_does_not(): void
    {
        $this->get('/admin/login')->assertOk();
        $this->get('/admin/register')->assertNotFound();
    }

    public function test_user_can_create_a_client(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm([
                'name' => 'Jane Doe',
                'phone' => '07123 456789',
                'emergency_contact_name' => 'John Doe',
                'emergency_contact_phone' => '07987 654321',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('clients', [
            'name' => 'Jane Doe',
            'phone' => '07123 456789',
            'emergency_contact_name' => 'John Doe',
            'emergency_contact_phone' => '07987 654321',
        ]);
    }

    public function test_client_name_is_required(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(CreateClient::class)
            ->fillForm(['name' => ''])
            ->call('create')
            ->assertHasFormErrors(['name' => 'required']);
    }

    public function test_user_can_edit_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $client->fresh()->name);
    }

    public function test_user_can_view_a_client_with_their_dogs(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();
        $dog = Dog::factory()->for($client)->create(['name' => 'Biscuit']);

        Livewire::test(ViewClient::class, ['record' => $client->getKey()])
            ->assertOk();

        Livewire::test(DogsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => ViewClient::class,
        ])->assertCanSeeTableRecords([$dog]);
    }

    public function test_user_can_add_several_dogs_to_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        $manager = Livewire::test(DogsRelationManager::class, [
            'ownerRecord' => $client,
            'pageClass' => EditClient::class,
        ]);

        $manager->callTableAction('create', data: ['name' => 'Biscuit'])->assertHasNoFormErrors();
        $manager->callTableAction('create', data: ['name' => 'Rex'])->assertHasNoFormErrors();

        $this->assertSame(['Biscuit', 'Rex'], $client->dogs()->orderBy('name')->pluck('name')->all());
    }

    public function test_user_can_edit_a_dog(): void
    {
        $this->actingAs(User::factory()->create());
        $dog = Dog::factory()->create();

        Livewire::test(DogsRelationManager::class, [
            'ownerRecord' => $dog->client,
            'pageClass' => EditClient::class,
        ])
            ->callTableAction('edit', $dog, data: ['name' => 'Renamed'])
            ->assertHasNoFormErrors();

        $this->assertSame('Renamed', $dog->fresh()->name);
    }

    public function test_archived_clients_are_hidden_by_default_but_keep_their_data(): void
    {
        $this->actingAs(User::factory()->create());
        $active = Client::factory()->create();
        $archived = Client::factory()->archived()->has(Dog::factory()->count(2))->create();

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$active])
            ->assertCanNotSeeTableRecords([$archived])
            ->removeTableFilter('archived_at')
            ->assertCanSeeTableRecords([$active, $archived]);

        $this->assertCount(2, $archived->fresh()->dogs);
    }

    public function test_user_can_archive_and_unarchive_a_client(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->has(Dog::factory())->create();

        Livewire::test(ListClients::class)
            ->assertCanSeeTableRecords([$client])
            ->assertTableActionVisible('archive', $client)
            ->callTableAction('archive', $client);

        $this->assertTrue($client->fresh()->isArchived());
        $this->assertCount(1, $client->fresh()->dogs);

        Livewire::test(ListClients::class)
            ->assertCanNotSeeTableRecords([$client])
            ->set('tableFilters.archived_at.value', true)
            ->assertCanSeeTableRecords([$client])
            ->callTableAction('unarchive', $client);

        $this->assertFalse($client->fresh()->isArchived());
    }

    public function test_deleting_a_client_soft_deletes_it_and_hides_it(): void
    {
        $this->actingAs(User::factory()->create());
        $client = Client::factory()->create();

        Livewire::test(EditClient::class, ['record' => $client->getKey()])
            ->callAction('delete');

        $this->assertSoftDeleted($client);

        Livewire::test(ListClients::class)
            ->assertCanNotSeeTableRecords([$client]);
    }

    public function test_deleting_a_dog_soft_deletes_it(): void
    {
        $this->actingAs(User::factory()->create());
        $dog = Dog::factory()->create();

        Livewire::test(DogsRelationManager::class, [
            'ownerRecord' => $dog->client,
            'pageClass' => EditClient::class,
        ])
            ->callTableAction('delete', $dog);

        $this->assertSoftDeleted($dog);
        $this->assertCount(0, $dog->client->dogs);
    }
}
