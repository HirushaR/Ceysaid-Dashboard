<?php

namespace App\Filament\Resources\TourResource\Pages;

use App\Filament\Resources\TourResource;
use App\Services\TourCodeGenerator;
use Carbon\Carbon;
use Filament\Resources\Pages\CreateRecord;

class CreateTour extends CreateRecord
{
    protected static string $resource = TourResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (blank($data['tour_code'] ?? null) && filled($data['departure_date'] ?? null)) {
            $data['tour_code'] = app(TourCodeGenerator::class)->generate(
                null,
                Carbon::parse($data['departure_date']),
                $data['name'] ?? null
            );
        }

        return $data;
    }
}
