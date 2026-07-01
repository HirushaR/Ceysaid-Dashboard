<?php

namespace App\Services;

class TourFinanceFilterStore
{
    private const SESSION_KEY = 'tour_finance_filters';

    /**
     * @return array<string, mixed>
     */
    public static function get(): array
    {
        $filters = session(self::SESSION_KEY, []);

        return is_array($filters) ? $filters : [];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public static function set(array $filters): void
    {
        session([self::SESSION_KEY => $filters]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }
}
