<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'reference_id',
        'customer_name',
        'customer_id',
        'platform',
        'tour',
        'message',
        'created_by',
        'assigned_to',
        'assigned_operator',
        'status',
        'contact_method',
        'contact_value',
        'subject',
        'country',
        'destination',
        'number_of_adults',
        'number_of_children',
        'number_of_infants',
        'priority',
        'arrival_date',
        'depature_date',
        'number_of_days',
        'tour_details',
        'air_ticket_status',
        'hotel_status',
        'visa_status',
        'land_package_status',
    ];

    protected $casts = [
        'tour_details' => 'array',
        'arrival_date' => 'date',
        'depature_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedOperator()
    {
        return $this->belongsTo(User::class, 'assigned_operator');
    }

    public function callCenterCalls()
    {
        return $this->hasMany(CallCenterCall::class);
    }

    public function preDepartureCall()
    {
        return $this->hasOne(CallCenterCall::class)->where('call_type', CallCenterCall::CALL_TYPE_PRE_DEPARTURE);
    }

    public function postArrivalCall()
    {
        return $this->hasOne(CallCenterCall::class)->where('call_type', CallCenterCall::CALL_TYPE_POST_ARRIVAL);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->hasMany(\App\Models\Attachment::class);
    }

    public function leadCosts()
    {
        return $this->hasMany(LeadCost::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function vendorBills()
    {
        return $this->hasManyThrough(VendorBill::class, Invoice::class);
    }

    public function actionLogs()
    {
        return $this->hasMany(LeadActionLog::class)->orderBy('created_at', 'desc');
    }

    // Analytics Scopes
    public function scopeForDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeBySalesUser($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeByLeadSource($query, $source)
    {
        return $query->where('platform', $source);
    }

    public function scopeByPipelineStage($query, $stage)
    {
        return $query->where('status', $stage);
    }

    public function scopeConverted($query)
    {
        return $query->whereIn('status', ['confirmed', 'document_upload_complete']);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', ['mark_closed', 'operation_complete']);
    }

    public function scopeByDestination($query, $destination)
    {
        return $query->where('destination', $destination);
    }

    public function scopeWithRevenue($query)
    {
        return $query->whereHas('invoices', function ($q) {
            $q->where('status', 'paid');
        });
    }
}
