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
}
