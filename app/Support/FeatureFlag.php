<?php

namespace App\Support;

use App\Models\User;

final class FeatureFlag
{
    public function enabled(string $flag, ?User $user = null): bool
    {
        $configKey = str_starts_with($flag, 'workflow.')
            ? 'workflow.flags.'.substr($flag, strlen('workflow.'))
            : 'workflow.'.$flag;
        $definition = config($configKey);

        if (! is_array($definition) || ! ($definition['enabled'] ?? false)) {
            return false;
        }

        if ($user === null) {
            return empty($definition['users']) && empty($definition['roles']);
        }

        $users = array_map('intval', $definition['users'] ?? []);
        $roles = $definition['roles'] ?? [];

        if ($users !== [] && in_array((int) $user->getKey(), $users, true)) {
            return true;
        }

        if ($roles !== [] && in_array($user->role, $roles, true)) {
            return true;
        }

        return $users === [] && $roles === [];
    }
}
