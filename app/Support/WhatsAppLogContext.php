<?php

namespace App\Support;

class WhatsAppLogContext
{
    /**
     * Flatten arrays for logging — Laravel's log normalizer aborts after 9 nested levels.
     *
     * @param  array<string, mixed>  $context
     * @return array<string, string|int|float|bool|null>
     */
    public static function flatten(array $context): array
    {
        $flattened = [];

        foreach ($context as $key => $value) {
            $flattened[$key] = match (true) {
                is_array($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
                is_object($value) => json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: null,
                default => $value,
            };
        }

        return $flattened;
    }
}
