<?php

namespace App\Enums;

enum LeadLifecycleStage: string
{
    case NewInquiry = 'new_inquiry';
    case Assigned = 'assigned';
    case Qualification = 'qualification';
    case ReadyForPricing = 'ready_for_pricing';
    case Pricing = 'pricing';
    case QuoteSent = 'quote_sent';
    case Negotiation = 'negotiation';
    case Confirmed = 'confirmed';
    case OperationsHandover = 'operations_handover';
    case InFulfilment = 'in_fulfilment';
    case ReadyToTravel = 'ready_to_travel';
    case TravelCompleted = 'travel_completed';
    case Closed = 'closed';
}
