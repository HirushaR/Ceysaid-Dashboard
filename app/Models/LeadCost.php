<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadCost extends Model
{
    protected $fillable = [
        'lead_id',
        'invoice_number',
        'amount',
        'details',
        'vendor_bill',
        'vendor_amount',
        'is_paid',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'vendor_amount' => 'decimal:2',
        'is_paid' => 'boolean',
    ];

    public function lead()
    {
        return $this->belongsTo(Lead::class);
    }
}
