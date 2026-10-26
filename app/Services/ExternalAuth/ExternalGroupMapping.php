<?php

declare(strict_types=1);

namespace App\Services\ExternalAuth;

use RuntimeException;

final class ExternalGroupMapping
{
    /** @return array{admitted: bool, groups: array} */
    public static function resolve(array $provider, array $externalGroups, array $roles, array $groups): array
    {
        if (!isset($roles[$provider['base_role']])) throw new RuntimeException('External base role is unavailable.');
        $mapped = [];
        $admitted = false;
        foreach ($externalGroups as $source) {
            if (!array_key_exists($source, $provider['group_map'])) continue;
            $admitted = true;
            foreach ($provider['group_map'][$source] as $group) {
                if (!isset($groups[$group])) throw new RuntimeException('External mapped group is unavailable.');
                $mapped[] = $group;
            }
        }
        $mapped = array_values(array_unique($mapped));
        sort($mapped);
        return ['admitted' => $admitted, 'groups' => $mapped];
    }
}
