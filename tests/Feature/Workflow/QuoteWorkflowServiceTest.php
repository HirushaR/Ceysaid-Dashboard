<?php

namespace Tests\Feature\Workflow;

use App\Enums\QuoteStatus;
use App\Models\Lead;
use App\Models\Quote;
use App\Models\User;
use App\Services\QuoteWorkflowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class QuoteWorkflowServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_quote_moves_through_sent_and_accepted(): void
    {
        $user=User::factory()->create(['role'=>'sales']); $quote=$this->quote($user);
        $quote=app(QuoteWorkflowService::class)->transition($quote,QuoteStatus::Sent,$user);
        $quote=app(QuoteWorkflowService::class)->transition($quote,QuoteStatus::Accepted,$user);
        $this->assertNotNull($quote->sent_at); $this->assertNotNull($quote->accepted_at);
        $this->assertSame(QuoteStatus::Accepted,$quote->status);
    }

    public function test_revision_copies_lines_and_keeps_history(): void
    {
        $user=User::factory()->create(['role'=>'sales']); $quote=$this->quote($user);
        $quote->lineItems()->create(['sort_order'=>0,'description'=>'Package','quantity'=>2,'rate'=>100]);
        $quote=app(QuoteWorkflowService::class)->transition($quote,QuoteStatus::Sent,$user);
        $copy=app(QuoteWorkflowService::class)->revise($quote,$user);
        $this->assertSame(2,$copy->revision); $this->assertSame($quote->family_id,$copy->family_id);
        $this->assertSame(1,$copy->lineItems()->count()); $this->assertSame(QuoteStatus::Draft,$copy->status);
    }

    private function quote(User $user): Quote
    {
        $lead=Lead::factory()->create();
        return Quote::create(['lead_id'=>$lead->id,'family_id'=>(string)Str::uuid(),'revision'=>1,'quote_number'=>'QT/TEST/'.$lead->id,'status'=>QuoteStatus::Draft,'created_by'=>$user->id]);
    }
}
