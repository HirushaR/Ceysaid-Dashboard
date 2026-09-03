<?php
namespace App\Policies;
use App\Models\Leave;use App\Models\User;
class LeavePolicy{public function viewAny(User $u):bool{return true;}public function view(User $u,Leave $l):bool{return $u->isAdmin()||$u->isHR()||$l->user_id===$u->id;}public function create(User $u):bool{return true;}public function review(User $u,Leave $l):bool{return $u->isAdmin()||$u->isHR();}}
