<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class AdminParityAudit extends Command
{
    protected $signature = 'admin:parity-audit {--strict : Fail while any Filament routes remain}';
    protected $description = 'Audit native admin capability coverage and Filament cutover readiness';

    public function handle(): int
    {
        $names = collect(Route::getRoutes()->getRoutes())->map(fn ($route) => $route->getName())->filter();
        $required = collect(config('admin_parity.required_routes', []));
        $missing = $required->diff($names)->values();
        $capabilities = collect(config('admin_parity.capabilities', []));
        $missingCapabilities = $this->missingCapabilities($capabilities, $names);
        $legacy = $names->filter(fn ($name) => str_starts_with((string) $name, 'filament.'))->values();

        $this->info('Native route coverage: '.($required->count() - $missing->count()).'/'.$required->count());
        $this->info('Legacy capability coverage: '.($capabilities->count() - $missingCapabilities->count()).'/'.$capabilities->count());

        if ($missing->isNotEmpty()) {
            $this->error('Missing native routes:');
            $missing->each(fn ($name) => $this->line(' - '.$name));
        } else {
            $this->info('All required native route groups are present.');
        }

        if ($missingCapabilities->isNotEmpty()) {
            $this->error('Uncovered legacy capabilities:');
            $missingCapabilities->each(fn ($routes, $capability) => $this->line(' - '.$capability.' ('.implode(', ', $routes).')'));
        } else {
            $this->info('Every catalogued Filament capability has a native replacement.');
        }

        $this->line('Legacy Filament routes remaining: '.$legacy->count());
        if ($legacy->isNotEmpty()) {
            $this->warn('Filament removal is not yet safe. Legacy routes remain under /legacy-admin.');
        } else {
            $this->info('No Filament routes remain; dependency removal gate is clear.');
        }

        return $missing->isNotEmpty()
            || $missingCapabilities->isNotEmpty()
            || ($this->option('strict') && $legacy->isNotEmpty())
                ? self::FAILURE
                : self::SUCCESS;
    }

    private function missingCapabilities(Collection $capabilities, Collection $routeNames): Collection
    {
        return $capabilities->filter(
            fn (array $routes) => collect($routes)->contains(fn (string $route) => ! $routeNames->contains($route)),
        );
    }
}
