<?php
namespace App\Policies;
use App\Models\Lead;use App\Models\User;
class LeadPolicy{public function viewAny(User $u):bool{return true;}public function view(User $u,Lead $l):bool{return $u->isAdmin()||$u->isManager()||$l->created_by===$u->id||$l->assigned_to===$u->id||$l->assigned_operator===$u->id||($u->isOperation()&&$l->status==='info_gather_complete');}public function create(User $u):bool{return !$u->isAccount()&&!$u->isHR();}public function update(User $u,Lead $l):bool{return $u->isAdmin()||$u->isManager()||$l->assigned_to===$u->id||$l->assigned_operator===$u->id||$l->created_by===$u->id;}}
