<?php

namespace App\Enums;

enum LeadType: string
{
    case Standard = 'standard';
    case Group = 'group';
    case Cruise = 'cruise';
    case Visa = 'visa';
    case Other = 'other';
}
