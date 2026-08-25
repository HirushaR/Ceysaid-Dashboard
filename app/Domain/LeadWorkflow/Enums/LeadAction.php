<?php

namespace App\Domain\LeadWorkflow\Enums;

enum LeadAction: string
{
    case AssignSalesOwner = 'assign_sales_owner';
    case ClaimInquiry = 'claim_inquiry';
    case StartQualification = 'start_qualification';
    case CompleteQualification = 'complete_qualification';
    case ReturnToQualification = 'return_to_qualification';
    case StartPricing = 'start_pricing';
    case SendQuote = 'send_quote';
    case StartAmendment = 'start_amendment';
    case ConfirmBooking = 'confirm_booking';
    case SubmitHandoff = 'submit_handoff';
    case AcceptHandoff = 'accept_handoff';
    case MarkReadyToTravel = 'mark_ready_to_travel';
    case RevokeReadiness = 'revoke_readiness';
    case MarkTravelCompleted = 'mark_travel_completed';
    case CloseLead = 'close_lead';
    case ReopenLead = 'reopen_lead';
}
