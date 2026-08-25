<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class SalesWorkspaceQuery
{
    public function leads(User $user): Builder
    {
        $query = Lead::query()->notArchived()->where('is_other_lead', false);

        if ($user->isAdmin()) {
            return $query;
        }

        $ownerIds = [$user->id];
        if ($user->isManager()) {
            $ownerIds = array_merge($ownerIds, $user->teamMembers()->pluck('id')->all());
        }

        return $query->whereIn('sales_owner_id', array_unique($ownerIds));
    }
}
