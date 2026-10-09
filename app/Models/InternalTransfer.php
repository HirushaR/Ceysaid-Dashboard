<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InternalTransfer extends Model
{
    protected $fillable = [
        'transfer_number', 'transfer_date', 'from_account', 'to_account',
        'amount', 'reference_number', 'notes', 'created_by',
    ];

    protected $casts = ['transfer_date' => 'date', 'amount' => 'decimal:2'];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
