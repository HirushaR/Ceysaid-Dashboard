<?php

namespace Tests\Feature\Analytics;

use App\Models\Lead;
use App\Models\Invoice;
use App\Models\User;
use App\Models\CallCenterCall;
use App\Models\Leave;
use App\Services\DateRangeService;
use App\Enums\LeadStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->salesUser = User::factory()->create(['role' => 'sales', 'name' => 'Sales User']);
        $this->operationUser = User::factory()->create(['role' => 'operation', 'name' => 'Operation User']);
        $this->adminUser = User::factory()->create(['role' => 'admin', 'name' => 'Admin User']);
    }

    public function test_date_range_service_presets()
    {
        $dateRange = new DateRangeService();
        
        // Test today preset
        $dateRange->setPreset(DateRangeService::PRESET_TODAY);
        $this->assertTrue($dateRange->getStartDate()->isToday());
        $this->assertTrue($dateRange->getEndDate()->isToday());
        
        // Test last 7 days preset
        $dateRange->setPreset(DateRangeService::PRESET_LAST_7_DAYS);
        $this->assertEquals(6, $dateRange->getDaysDiff());
        
        // Test custom preset
        $dateRange->setPreset(DateRangeService::PRESET_CUSTOM, [
            'start' => '2024-01-01',
            'end' => '2024-01-31',
        ]);
        $this->assertEquals('2024-01-01', $dateRange->getStartDate()->format('Y-m-d'));
        $this->assertEquals('2024-01-31', $dateRange->getEndDate()->format('Y-m-d'));
    }

    public function test_lead_analytics_scopes()
    {
        // Create test leads
        $lead1 = Lead::factory()->create([
            'assigned_to' => $this->salesUser->id,
            'platform' => 'facebook',
            'status' => LeadStatus::NEW->value,
            'created_at' => now()->subDays(5),
        ]);
        
        $lead2 = Lead::factory()->create([
            'assigned_to' => $this->salesUser->id,
            'platform' => 'whatsapp',
            'status' => LeadStatus::CONFIRMED->value,
            'created_at' => now()->subDays(3),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        // Test date range scope
        $leadsInRange = Lead::forDateRange($startDate, $endDate)->get();
        $this->assertCount(2, $leadsInRange);

        // Test sales user scope
        $salesLeads = Lead::bySalesUser($this->salesUser->id)->get();
        $this->assertCount(2, $salesLeads);

        // Test lead source scope
        $facebookLeads = Lead::byLeadSource('facebook')->get();
        $this->assertCount(1, $facebookLeads);

        // Test pipeline stage scope
        $newLeads = Lead::byPipelineStage(LeadStatus::NEW->value)->get();
        $this->assertCount(1, $newLeads);

        // Test converted scope
        $convertedLeads = Lead::converted()->get();
        $this->assertCount(1, $convertedLeads);
    }

    public function test_invoice_analytics_scopes()
    {
        $lead = Lead::factory()->create(['assigned_to' => $this->salesUser->id]);
        
        $invoice1 = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 1000.00,
            'status' => 'paid',
            'created_at' => now()->subDays(5),
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 500.00,
            'status' => 'pending',
            'created_at' => now()->subDays(3),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        // Test date range scope
        $invoicesInRange = Invoice::forDateRange($startDate, $endDate)->get();
        $this->assertCount(2, $invoicesInRange);

        // Test paid scope
        $paidInvoices = Invoice::paid()->get();
        $this->assertCount(1, $paidInvoices);

        // Test pending scope
        $pendingInvoices = Invoice::pending()->get();
        $this->assertCount(1, $pendingInvoices);

        // Test sales user scope
        $salesInvoices = Invoice::bySalesUser($this->salesUser->id)->get();
        $this->assertCount(2, $salesInvoices);
    }

    public function test_call_center_analytics_scopes()
    {
        $lead = Lead::factory()->create();
        
        $call1 = CallCenterCall::factory()->create([
            'lead_id' => $lead->id,
            'assigned_call_center_user' => $this->operationUser->id,
            'status' => CallCenterCall::STATUS_PENDING,
            'created_at' => now()->subDays(5),
        ]);
        
        $call2 = CallCenterCall::factory()->create([
            'lead_id' => $lead->id,
            'assigned_call_center_user' => $this->operationUser->id,
            'status' => CallCenterCall::STATUS_COMPLETED,
            'created_at' => now()->subDays(3),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        // Test date range scope
        $callsInRange = CallCenterCall::forDateRange($startDate, $endDate)->get();
        $this->assertCount(2, $callsInRange);

        // Test pending scope
        $pendingCalls = CallCenterCall::pending()->get();
        $this->assertCount(1, $pendingCalls);

        // Test completed scope
        $completedCalls = CallCenterCall::completed()->get();
        $this->assertCount(1, $completedCalls);

        // Test overdue scope
        $overdueCalls = CallCenterCall::overdue(2)->get();
        $this->assertCount(1, $overdueCalls);

        // Test operation user scope
        $operationCalls = CallCenterCall::byOperationUser($this->operationUser->id)->get();
        $this->assertCount(2, $operationCalls);
    }

    public function test_conversion_rate_calculation()
    {
        // Create leads
        $lead1 = Lead::factory()->create([
            'status' => LeadStatus::NEW->value,
            'created_at' => now()->subDays(5),
        ]);
        
        $lead2 = Lead::factory()->create([
            'status' => LeadStatus::CONFIRMED->value,
            'created_at' => now()->subDays(3),
        ]);
        
        $lead3 = Lead::factory()->create([
            'status' => LeadStatus::DOCUMENT_UPLOAD_COMPLETE->value,
            'created_at' => now()->subDays(2),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $totalLeads = Lead::forDateRange($startDate, $endDate)->count();
        $convertedLeads = Lead::forDateRange($startDate, $endDate)->converted()->count();
        
        $conversionRate = $totalLeads > 0 ? ($convertedLeads / $totalLeads) * 100 : 0;

        $this->assertEquals(3, $totalLeads);
        $this->assertEquals(2, $convertedLeads);
        $this->assertEquals(66.67, round($conversionRate, 2));
    }

    public function test_revenue_calculation()
    {
        $lead = Lead::factory()->create(['assigned_to' => $this->salesUser->id]);
        
        $invoice1 = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 1000.00,
            'status' => 'paid',
            'created_at' => now()->subDays(5),
        ]);
        
        $invoice2 = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 500.00,
            'status' => 'paid',
            'created_at' => now()->subDays(3),
        ]);
        
        $invoice3 = Invoice::factory()->create([
            'lead_id' => $lead->id,
            'total_amount' => 750.00,
            'status' => 'pending',
            'created_at' => now()->subDays(2),
        ]);

        $startDate = now()->subDays(10);
        $endDate = now();

        $totalRevenue = Invoice::forDateRange($startDate, $endDate)
            ->paid()
            ->sum('total_amount');

        $this->assertEquals(1500.00, $totalRevenue);
    }

    public function test_leave_calendar_data()
    {
        $user1 = User::factory()->create(['role' => 'sales']);
        $user2 = User::factory()->create(['role' => 'operation']);

        $leave1 = Leave::factory()->create([
            'user_id' => $user1->id,
            'start_date' => now()->subDays(2),
            'end_date' => now()->addDays(2),
            'status' => 'approved',
            'type' => 'annual',
        ]);

        $leave2 = Leave::factory()->create([
            'user_id' => $user2->id,
            'start_date' => now()->addDays(1),
            'end_date' => now()->addDays(3),
            'status' => 'approved',
            'type' => 'sick',
        ]);

        $startDate = now()->subDays(5);
        $endDate = now()->addDays(5);

        $leaves = Leave::query()
            ->with('user')
            ->where('status', 'approved')
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_date', [$startDate, $endDate])
                      ->orWhereBetween('end_date', [$startDate, $endDate])
                      ->orWhere(function ($q) use ($startDate, $endDate) {
                          $q->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                      });
            })
            ->get();

        $this->assertCount(2, $leaves);
        $this->assertTrue($leaves->contains('id', $leave1->id));
        $this->assertTrue($leaves->contains('id', $leave2->id));
    }

    public function test_analytics_dashboard_access_permission()
    {
        // Test admin access
        $this->actingAs($this->adminUser);
        $response = $this->get('/admin/analytics-dashboard');
        $response->assertStatus(200);

        // Test non-admin access denied
        $this->actingAs($this->salesUser);
        $response = $this->get('/admin/analytics-dashboard');
        $response->assertStatus(403);
    }
}
