<?php

namespace Tests\Feature\Admin;

use App\Livewire\Admin\Notifications\Index as NotificationIndex;
use App\Models\Invoice;
use App\Models\Lead;
use App\Models\User;
use App\Notifications\LeadDatabaseNotification;
use App\Support\AdminNotificationAction;
use App\Support\AdminNotificationMessage;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class HardeningMilestoneTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_finance_dashboard_does_not_expose_company_financials(): void
    {
        Invoice::factory()->create(['balance_amount' => 987654]);
        $sales = User::factory()->create(['role' => 'sales']);

        $this->actingAs($sales)->get(route('admin.dashboard'))
            ->assertOk()
            ->assertDontSee('Outstanding invoices')
            ->assertDontSee('Cash movement')
            ->assertDontSee('987,654');
    }

    public function test_notification_centre_marks_and_removes_only_own_notifications(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $message = AdminNotificationMessage::make()->title('Assigned lead')->body('Please follow up')->actions([
            AdminNotificationAction::make('view')->label('View lead')->url(route('admin.dashboard')),
        ]);
        $user->notify(new LeadDatabaseNotification($message));
        $other->notify(new LeadDatabaseNotification($message));
        $notification = $user->notifications()->firstOrFail();

        $this->actingAs($user);
        Livewire::test(NotificationIndex::class)->assertSee('Assigned lead')->call('markRead', $notification->id);
        $this->assertNotNull($notification->fresh()->read_at);
        Livewire::test(NotificationIndex::class)->call('delete', $notification->id);
        $this->assertDatabaseHas('notifications', ['notifiable_id' => $other->id]);
    }

    public function test_export_is_audited_and_spreadsheet_formulas_are_neutralized(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Lead::factory()->create(['customer_name' => '=HYPERLINK("bad")', 'created_at' => today()]);

        $response = $this->actingAs($admin)->get(route('admin.exports.download', ['type' => 'leads', 'from' => today()->toDateString(), 'to' => today()->toDateString()]));
        $response->assertOk();
        $this->assertStringContainsString("'=HYPERLINK", $response->streamedContent());
        $this->assertDatabaseHas('audit_logs', ['user_id' => $admin->id, 'event' => 'export.downloaded']);
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('admin.password.email'), ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_placeholder_route_has_been_removed(): void
    {
        $this->assertFalse(app('router')->has('admin.module'));
    }
}
