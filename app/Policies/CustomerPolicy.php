<?php
namespace App\Policies;
use App\Models\Customer;use App\Models\User;
class CustomerPolicy{public function viewAny(User $u):bool{return $u->isAdmin()||$u->hasPermission('customers.view');}public function view(User $u,Customer $c):bool{return $this->viewAny($u);}public function create(User $u):bool{return $u->isAdmin()||$u->hasPermission('customers.create');}public function update(User $u,Customer $c):bool{return $u->isAdmin()||$u->hasPermission('customers.edit');}}
