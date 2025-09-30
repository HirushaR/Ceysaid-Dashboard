<?php

namespace App\Services;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class DateRangeService
{
    public const PRESET_TODAY = 'today';
    public const PRESET_YESTERDAY = 'yesterday';
    public const PRESET_LAST_7_DAYS = 'last_7_days';
    public const PRESET_LAST_30_DAYS = 'last_30_days';
    public const PRESET_MTD = 'mtd';
    public const PRESET_QTD = 'qtd';
    public const PRESET_YTD = 'ytd';
    public const PRESET_CUSTOM = 'custom';

    protected Carbon $startDate;
    protected Carbon $endDate;
    protected string $preset;
    protected string $timezone;

    public function __construct(string $timezone = null)
    {
        $this->timezone = $timezone ?? config('app.timezone', 'UTC');
        $this->setPreset(self::PRESET_LAST_30_DAYS);
    }

    public function setPreset(string $preset, array $customDates = []): self
    {
        $this->preset = $preset;
        
        switch ($preset) {
            case self::PRESET_TODAY:
                $this->startDate = Carbon::today($this->timezone);
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                break;
                
            case self::PRESET_YESTERDAY:
                $this->startDate = Carbon::yesterday($this->timezone);
                $this->endDate = Carbon::yesterday($this->timezone)->endOfDay();
                break;
                
            case self::PRESET_LAST_7_DAYS:
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                $this->startDate = $this->endDate->copy()->subDays(6)->startOfDay();
                break;
                
            case self::PRESET_LAST_30_DAYS:
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                $this->startDate = $this->endDate->copy()->subDays(29)->startOfDay();
                break;
                
            case self::PRESET_MTD:
                $this->startDate = Carbon::now($this->timezone)->startOfMonth();
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                break;
                
            case self::PRESET_QTD:
                $this->startDate = Carbon::now($this->timezone)->startOfQuarter();
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                break;
                
            case self::PRESET_YTD:
                $this->startDate = Carbon::now($this->timezone)->startOfYear();
                $this->endDate = Carbon::today($this->timezone)->endOfDay();
                break;
                
            case self::PRESET_CUSTOM:
                if (isset($customDates['start']) && isset($customDates['end'])) {
                    $this->startDate = Carbon::parse($customDates['start'], $this->timezone)->startOfDay();
                    $this->endDate = Carbon::parse($customDates['end'], $this->timezone)->endOfDay();
                } else {
                    $this->setPreset(self::PRESET_LAST_30_DAYS);
                }
                break;
                
            default:
                $this->setPreset(self::PRESET_LAST_30_DAYS);
        }
        
        return $this;
    }

    public function getStartDate(): Carbon
    {
        return $this->startDate;
    }

    public function getEndDate(): Carbon
    {
        return $this->endDate;
    }

    public function getPreset(): string
    {
        return $this->preset;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function getDaysDiff(): int
    {
        return $this->startDate->diffInDays($this->endDate);
    }

    public function getInterval(): string
    {
        $days = $this->getDaysDiff();
        
        if ($days <= 7) {
            return 'daily';
        } elseif ($days <= 90) {
            return 'weekly';
        } else {
            return 'monthly';
        }
    }

    public function getPeriod(): CarbonPeriod
    {
        return CarbonPeriod::create($this->startDate, $this->endDate);
    }

    public function getPresetOptions(): array
    {
        return [
            self::PRESET_TODAY => 'Today',
            self::PRESET_YESTERDAY => 'Yesterday',
            self::PRESET_LAST_7_DAYS => 'Last 7 Days',
            self::PRESET_LAST_30_DAYS => 'Last 30 Days',
            self::PRESET_MTD => 'Month to Date',
            self::PRESET_QTD => 'Quarter to Date',
            self::PRESET_YTD => 'Year to Date',
            self::PRESET_CUSTOM => 'Custom Range',
        ];
    }

    public function getDateRangeLabel(): string
    {
        $options = $this->getPresetOptions();
        $label = $options[$this->preset] ?? 'Custom Range';
        
        if ($this->preset === self::PRESET_CUSTOM) {
            $label .= " ({$this->startDate->format('M j')} - {$this->endDate->format('M j, Y')})";
        }
        
        return $label;
    }

    public function toArray(): array
    {
        return [
            'start' => $this->startDate->toDateString(),
            'end' => $this->endDate->toDateString(),
            'preset' => $this->preset,
            'timezone' => $this->timezone,
            'interval' => $this->getInterval(),
            'days_diff' => $this->getDaysDiff(),
        ];
    }
}
