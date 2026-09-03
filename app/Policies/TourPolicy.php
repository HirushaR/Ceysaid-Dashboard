<?php
namespace App\Policies;
use App\Models\Tour;use App\Models\User;
class TourPolicy{public function viewAny(User $u):bool{return $u->isAdmin()||$u->isAccount();}public function view(User $u,Tour $t):bool{return $this->viewAny($u);}public function create(User $u):bool{return $this->viewAny($u);}public function update(User $u,Tour $t):bool{return $this->viewAny($u);}}
