<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

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

    /**
     * All payment rows across vendor bills for this supplier (newest first when queried with orderBy).
     */
    public function vendorBillPayments(): HasManyThrough
    {
        return $this->hasManyThrough(
            VendorBillPayment::class,
            VendorBill::class,
            'supplier_id',
            'vendor_bill_id',
            'id',
            'id'
        );
    }

    /** Sum of unpaid / partial balances on vendor bills (amount still owed to this supplier). */
    public function totalOutstandingPayable(): float
    {
        $this->loadMissing('vendorBills.vendorBillPayments');

        return round((float) $this->vendorBills->sum(fn (VendorBill $bill) => $bill->outstanding_amount), 2);
    }
}
