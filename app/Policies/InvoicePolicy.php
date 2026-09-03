<?php
namespace App\Policies;
use App\Models\Invoice;use App\Models\User;
class InvoicePolicy{public function viewAny(User $u):bool{return $u->isAdmin()||$u->isAccount()||$u->hasPermission('invoices.view');}public function view(User $u,Invoice $i):bool{return $u->canViewInvoice($i);}public function create(User $u):bool{return $u->canManageAccountingRecords()||$u->isSales()||$u->isOperation()||$u->canCreateResource('invoices');}public function update(User $u,Invoice $i):bool{return $u->canEditInvoices();}public function recordPayment(User $u,Invoice $i):bool{return $u->canManageAccountingRecords();}}
