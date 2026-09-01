<?php

namespace Tests\Feature;

use App\Mail\NewEnquiryReceived;
use App\Models\Enquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_payload_creates_enquiry_and_returns_201(): void
    {
        $response = $this->postJson('/contact', [
            'name' => 'Jane Test',
            'email' => 'jane@example.com',
            'phone' => '07123456789',
            'message' => 'Looking for a 30-minute walk twice a week.',
            'source_page' => 'home',
        ]);

        $response->assertCreated();
        $response->assertJson(['message' => "Thanks! We'll be in touch soon."]);

        $this->assertDatabaseHas('enquiries', [
            'name' => 'Jane Test',
            'email' => 'jane@example.com',
            'phone' => '07123456789',
            'message' => 'Looking for a 30-minute walk twice a week.',
            'source_page' => 'home',
        ]);
    }

    public function test_valid_payload_emails_the_business_inbox(): void
    {
        Mail::fake();

        $this->postJson('/contact', [
            'name' => 'Jane Test',
            'email' => 'jane@example.com',
            'message' => 'Looking for a 30-minute walk twice a week.',
        ]);

        Mail::assertSent(NewEnquiryReceived::class, function (NewEnquiryReceived $mail) {
            return $mail->hasTo(config('mail.from.address'))
                && $mail->enquiry->email === 'jane@example.com';
        });
    }

    public function test_mail_failure_does_not_prevent_enquiry_from_being_saved(): void
    {
        Mail::shouldReceive('to->send')->andThrow(new \RuntimeException('SMTP unavailable'));

        $response = $this->postJson('/contact', [
            'name' => 'Jane Test',
            'email' => 'jane@example.com',
            'message' => 'Looking for a 30-minute walk twice a week.',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('enquiries', ['email' => 'jane@example.com']);
    }

    public function test_empty_payload_returns_422_with_required_field_errors(): void
    {
        $response = $this->postJson('/contact', []);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['name', 'email', 'message']);
        $this->assertSame(0, Enquiry::count());
    }

    public function test_invalid_email_returns_422(): void
    {
        $response = $this->postJson('/contact', [
            'name' => 'Jane Test',
            'email' => 'not-an-email',
            'message' => 'Looking for a walk.',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }
}
