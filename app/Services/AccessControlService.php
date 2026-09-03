<?php
namespace App\Services;
use App\Models\User;use Illuminate\Support\Facades\DB;
class AccessControlService{public function sync(User $user,array $groupIds,array $permissionIds,int $actorId):void{DB::transaction(function()use($user,$groupIds,$permissionIds,$actorId){$pivot=['granted_by'=>$actorId,'granted_at'=>now()];$user->permissionGroups()->syncWithPivotValues(array_values(array_unique(array_map('intval',$groupIds))),$pivot);$user->permissions()->syncWithPivotValues(array_values(array_unique(array_map('intval',$permissionIds))),$pivot);});}}
