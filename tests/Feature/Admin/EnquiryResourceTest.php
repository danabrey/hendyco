<?php

namespace Tests\Feature\Admin;

use App\Filament\Resources\Enquiries\Pages\ManageEnquiries;
use App\Models\Enquiry;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class EnquiryResourceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::factory()->create());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        auth()->logout();

        $this->get('/admin/enquiries')->assertRedirect('/admin/login');
    }

    public function test_enquiries_are_listed_newest_first(): void
    {
        $older = Enquiry::factory()->create(['created_at' => now()->subDay()]);
        $newer = Enquiry::factory()->create(['created_at' => now()]);

        Livewire::test(ManageEnquiries::class)
            ->assertCanSeeTableRecords([$newer, $older], inOrder: true);
    }

    public function test_an_enquiry_can_be_viewed_but_not_edited_or_created(): void
    {
        $enquiry = Enquiry::factory()->create(['message' => 'Two spaniels, walks on Tuesdays please.']);

        Livewire::test(ManageEnquiries::class)
            ->assertActionDoesNotExist('create')
            ->assertActionDoesNotExist(TestAction::make('edit')->table($enquiry))
            ->mountAction(TestAction::make('view')->table($enquiry))
            ->assertSee('Two spaniels, walks on Tuesdays please.');
    }

    public function test_an_enquiry_can_be_deleted(): void
    {
        $enquiry = Enquiry::factory()->create();

        Livewire::test(ManageEnquiries::class)
            ->callAction(TestAction::make('delete')->table($enquiry));

        $this->assertModelMissing($enquiry);
    }
}
