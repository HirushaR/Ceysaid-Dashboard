<?php
namespace App\Policies;
use App\Models\Supplier;use App\Models\User;
class SupplierPolicy{public function viewAny(User $u):bool{return $u->canViewSuppliers();}public function view(User $u,Supplier $s):bool{return $this->viewAny($u);}public function create(User $u):bool{return $u->canManageAccountingRecords();}public function update(User $u,Supplier $s):bool{return $u->canManageAccountingRecords();}}
