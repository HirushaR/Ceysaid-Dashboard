<?php
namespace App\Policies;
use App\Models\Quote;use App\Models\User;
class QuotePolicy{public function viewAny(User $u):bool{return $u->isAdmin()||$u->isAccount()||$u->hasPermission('quotes.view');}public function view(User $u,Quote $q):bool{return $u->canViewQuote($q);}public function create(User $u):bool{return $u->isAdmin()||$u->isAccount()||$u->isSales()||$u->canCreateResource('quotes');}public function update(User $u,Quote $q):bool{return ($u->isAdmin()||$u->isAccount()||$u->isSales())&&$q->isEditable();}}
