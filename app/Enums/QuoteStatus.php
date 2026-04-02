<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Draft = 'draft';
    case Converted = 'converted';
}
