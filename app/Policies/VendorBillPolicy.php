<?php
namespace App\Policies;

use App\Models\User;
use App\Models\VendorBill;

class VendorBillPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canViewVendorBills();
    }

    public function view(User $user, VendorBill $vendorBill): bool
    {
        if (! $this->viewAny($user)) {
            return false;
        }

        if ($user->canManageAccountingRecords()) {
            return true;
        }

        $vendorBill->loadMissing('invoice.lead');

        if (! $vendorBill->invoice) {
            return true;
        }

        return $user->canViewInvoice($vendorBill->invoice);
    }

    public function create(User $user): bool
    {
        return $user->canManageAccountingRecords()
            || $user->hasPermission('vendor_bills.create');
    }

    public function update(User $user, VendorBill $vendorBill): bool
    {
        return ($user->canManageAccountingRecords() || $user->hasPermission('vendor_bills.edit'))
            && $this->view($user, $vendorBill);
    }
}
