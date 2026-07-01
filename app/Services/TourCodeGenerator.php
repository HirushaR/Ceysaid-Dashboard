<?php

namespace App\Services;

use App\Models\Tour;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class TourCodeGenerator
{
    /**
     * Generate a unique tour code: {DEST}-{DDMON}-{YYYY}
     * Example: CHN-25JUL-2026
     */
    public function generate(?string $destination, CarbonInterface $departureDate, ?string $name = null): string
    {
        $destCode = $this->destinationCode($destination, $name);
        $datePart = strtoupper($departureDate->format('dM'));
        $year = $departureDate->format('Y');
        $base = "{$destCode}-{$datePart}-{$year}";

        if (! Tour::query()->where('tour_code', $base)->exists()) {
            return $base;
        }

        $suffix = 2;
        while (Tour::query()->where('tour_code', "{$base}-{$suffix}")->exists()) {
            $suffix++;
        }

        return "{$base}-{$suffix}";
    }

    private function destinationCode(?string $destination, ?string $name): string
    {
        $source = trim((string) ($destination ?: $name ?: 'TOUR'));

        $words = preg_split('/[\s,\/\-]+/', $source, -1, PREG_SPLIT_NO_EMPTY);
        if ($words === false || $words === []) {
            return 'TOUR';
        }

        $first = strtoupper(Str::ascii((string) $words[0]));

        if (strlen($first) >= 3) {
            return substr($first, 0, 3);
        }

        $second = isset($words[1]) ? strtoupper(Str::ascii((string) $words[1])) : '';

        return str_pad(substr($first.$second, 0, 3), 3, 'X');
    }
}
