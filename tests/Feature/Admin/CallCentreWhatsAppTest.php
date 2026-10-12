<?php

namespace Tests\Feature\Admin;

use App\Enums\LeadStatus;
use App\Jobs\SendWhatsAppMessageJob;
use App\Livewire\Admin\CallCentre\Show as CallShow;
use App\Livewire\Admin\WhatsApp\Index as WhatsAppIndex;
use App\Livewire\Admin\WhatsApp\Show as WhatsAppShow;
use App\Models\CallCenterCall;
use App\Models\Lead;
use App\Models\User;
use App\Models\WhatsAppContact;
use App\Models\WhatsAppConversation;
use App\Models\WhatsAppMessage;
use App\Notifications\WhatsAppConversationNotification;
use App\Services\WhatsAppWebhookHandler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class CallCentreWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_call_centre_user_records_attempt_and_completes_call(): void
    {
        $agent = User::factory()->create(['role' => 'call_center']);
        $lead = Lead::factory()->create();
        $call = CallCenterCall::create(['lead_id' => $lead->id, 'assigned_call_center_user' => $agent->id, 'call_type' => 'pre_departure', 'status' => 'assigned', 'call_attempts' => 0]);
        $this->actingAs($agent);
        Livewire::test(CallShow::class, ['call' => $call])->call('recordAttempt', 'not_answered')->assertHasNoErrors();
        $this->assertSame(1, $call->fresh()->call_attempts);
        Livewire::test(CallShow::class, ['call' => $call->fresh()])->set('status', 'completed')->set('call_notes', 'Customer confirmed documents')->call('save')->assertHasNoErrors();
        $this->assertSame('completed', $call->fresh()->status);
    }

    public function test_other_call_centre_user_cannot_open_assigned_call(): void
    {
        $owner = User::factory()->create(['role' => 'call_center']);
        $other = User::factory()->create(['role' => 'call_center']);
        $call = CallCenterCall::create(['lead_id' => Lead::factory()->create()->id, 'assigned_call_center_user' => $owner->id, 'call_type' => 'post_arrival', 'status' => 'assigned']);
        $this->actingAs($other)->get(route('admin.calls.show', $call))->assertForbidden();
    }

    public function test_sales_whatsapp_conversion_assigns_new_lead_and_reply_is_queued(): void
    {
        Bus::fake();
        $sales = User::factory()->create(['role' => 'sales']);
        $chat = $this->conversationFor($sales);
        WhatsAppMessage::create(['whatsapp_conversation_id' => $chat->id, 'wamid' => 'wamid-test', 'direction' => 'inbound', 'type' => 'text', 'body' => 'Need a holiday', 'status' => 'received', 'sent_at' => now()]);
        $this->actingAs($sales);

        Livewire::test(WhatsAppShow::class, ['conversation' => $chat])
            ->set('reply', 'We can help')
            ->call('send')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('whatsapp_messages', ['direction' => 'outbound', 'body' => 'We can help']);
        Livewire::test(WhatsAppShow::class, ['conversation' => $chat->fresh()])->call('convert')->assertHasNoErrors();
        $lead = $chat->fresh()->lead;
        $this->assertSame($sales->id, $lead->assigned_to);
        $this->assertSame(LeadStatus::ASSIGNED_TO_SALES->value, $lead->status);
    }

    public function test_sales_user_can_send_whatsapp_attachment_with_an_optional_caption(): void
    {
        Storage::fake('whatsapp-media');
        Bus::fake();
        $sales = User::factory()->create(['role' => 'sales']);
        $chat = $this->conversationFor($sales);
        $this->actingAs($sales);

        Livewire::test(WhatsAppShow::class, ['conversation' => $chat])
            ->set('reply', 'Your itinerary')
            ->set('attachment', UploadedFile::fake()->create('itinerary.pdf', 300, 'application/pdf'))
            ->call('send')
            ->assertHasNoErrors();

        $message = WhatsAppMessage::query()->where('direction', 'outbound')->firstOrFail();
        $this->assertSame('document', $message->type);
        $this->assertSame('Your itinerary', $message->body);
        $this->assertSame('itinerary.pdf', $message->media_filename);
        Storage::disk('whatsapp-media')->assertExists($message->media_path);
        Bus::assertDispatched(SendWhatsAppMessageJob::class, fn (SendWhatsAppMessageJob $job) => $job->messageId === $message->id);
    }

    public function test_whatsapp_attachment_rejects_an_unsupported_file_type(): void
    {
        Storage::fake('whatsapp-media');
        $sales = User::factory()->create(['role' => 'sales']);
        $chat = $this->conversationFor($sales);
        $this->actingAs($sales);

        Livewire::test(WhatsAppShow::class, ['conversation' => $chat])
            ->set('attachment', UploadedFile::fake()->create('unsafe.exe', 10, 'application/x-msdownload'))
            ->call('send')
            ->assertHasErrors(['attachment']);

        $this->assertDatabaseMissing('whatsapp_messages', ['direction' => 'outbound']);
    }

    public function test_sales_manager_can_assign_an_unassigned_whatsapp_chat(): void
    {
        Notification::fake();
        $manager = User::factory()->create(['role' => 'sales', 'is_manager' => true]);
        $sales = User::factory()->create(['role' => 'sales', 'is_manager' => false]);
        $chat = $this->conversationFor();
        $this->actingAs($manager);

        Livewire::test(WhatsAppIndex::class)
            ->assertSet('scope', 'unassigned')
            ->assertSee('Needs assignment')
            ->call('assignConversation', $chat->id, (string) $sales->id)
            ->assertHasNoErrors();

        $this->assertSame($sales->id, $chat->fresh()->assigned_to);
        $this->assertNotNull($chat->fresh()->assigned_at);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'WhatsAppConversation.assigned',
            'subject_id' => $chat->id,
        ]);
        Notification::assertSentTo($sales, WhatsAppConversationNotification::class);
        $this->actingAs($sales)->get(route('admin.whatsapp.show', $chat))->assertOk();
    }

    public function test_admin_can_open_unassigned_chat_but_regular_sales_cannot_assign_it(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $sales = User::factory()->create(['role' => 'sales', 'is_manager' => false]);
        $chat = $this->conversationFor();

        $this->actingAs($admin)->get(route('admin.whatsapp.show', $chat))->assertOk()->assertSee('Assign this conversation');
        $this->actingAs($sales)->get(route('admin.whatsapp.show', $chat))->assertForbidden();
        Livewire::actingAs($sales)
            ->test(WhatsAppIndex::class)
            ->call('assignConversation', $chat->id, (string) $sales->id)
            ->assertForbidden();
    }

    public function test_new_whatsapp_chat_notifies_admins_and_sales_managers_for_assignment(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $manager = User::factory()->create(['role' => 'sales', 'is_manager' => true]);
        $sales = User::factory()->create(['role' => 'sales', 'is_manager' => false]);

        app(WhatsAppWebhookHandler::class)->handle([
            'object' => 'whatsapp_business_account',
            'entry' => [[
                'changes' => [[
                    'field' => 'messages',
                    'value' => [
                        'contacts' => [['wa_id' => '94771111111', 'profile' => ['name' => 'New WhatsApp Customer']]],
                        'messages' => [[
                            'from' => '94771111111',
                            'id' => 'wamid-new-assignment',
                            'timestamp' => (string) now()->timestamp,
                            'type' => 'text',
                            'text' => ['body' => 'I need a holiday package'],
                        ]],
                    ],
                ]],
            ]],
        ]);

        $this->assertDatabaseHas('whatsapp_conversations', ['assigned_to' => null]);
        Notification::assertSentTo([$admin, $manager], WhatsAppConversationNotification::class, fn ($notification) => $notification->event === 'unassigned');
        Notification::assertNotSentTo($sales, WhatsAppConversationNotification::class);
    }

    private function conversationFor(?User $sales = null): WhatsAppConversation
    {
        $contact = WhatsAppContact::create(['wa_id' => '94770000000', 'phone' => '94770000000', 'profile_name' => 'WhatsApp Guest']);

        return WhatsAppConversation::create([
            'whatsapp_contact_id' => $contact->id,
            'assigned_to' => $sales?->id,
            'assigned_at' => $sales ? now() : null,
            'last_message_at' => now(),
            'last_message_preview' => 'Need a holiday',
            'unread_count' => 1,
        ]);
    }
}
