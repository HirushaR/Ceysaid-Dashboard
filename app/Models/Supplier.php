<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Supplier extends Model
{
    protected $fillable = [
        'name',
        'address',
        'tax_id',
        'contact_name',
        'phone',
        'email',
        'bank_details',
    ];

    public function vendorBills(): HasMany
    {
        return $this->hasMany(VendorBill::class);
    }
}
