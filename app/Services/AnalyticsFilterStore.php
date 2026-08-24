<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;

class AnalyticsFilterStore
{
    private const SESSION_KEY = 'analytics_dashboard_filters';

    /**
     * @return array<string, mixed>
     */
    public static function getState(): array
    {
        $defaults = [
            'datePreset' => DateRangeService::PRESET_LAST_30_DAYS,
            'startDate' => null,
            'endDate' => null,
            'salesUser' => null,
            'operationUser' => null,
            'leadSource' => null,
            'pipelineStage' => null,
        ];

        $stored = session(self::SESSION_KEY, []);

        return array_merge($defaults, is_array($stored) ? $stored : []);
    }

    /**
     * @param  array<string, mixed>  $state
     */
    public static function set(array $state): void
    {
        session([self::SESSION_KEY => $state]);
    }

    public static function clear(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    public static function getDateRange(): DateRangeService
    {
        $state = self::getState();
        $dateRange = new DateRangeService();

        if ($state['datePreset'] === DateRangeService::PRESET_CUSTOM) {
            $dateRange->setPreset(DateRangeService::PRESET_CUSTOM, [
                'start' => $state['startDate'],
                'end' => $state['endDate'],
            ]);
        } else {
            $dateRange->setPreset((string) $state['datePreset']);
        }

        return $dateRange;
    }

    /**
     * @return array<string, mixed>
     */
    public static function getFilters(): array
    {
        $state = self::getState();

        return array_filter([
            'sales_user' => $state['salesUser'],
            'operation_user' => $state['operationUser'],
            'lead_source' => $state['leadSource'],
            'pipeline_stage' => $state['pipelineStage'],
        ], fn ($value) => $value !== null && $value !== '');
    }

    public static function getCacheKey(): string
    {
        return 'analytics_'.(Auth::id() ?? 0).'_'.md5(serialize([
            'date_range' => self::getDateRange()->toArray(),
            'filters' => self::getFilters(),
        ]));
    }
}
