<?php

namespace App\Console\Commands\Concerns;

trait HasWorkflowMigrationOptions
{
    /** @return array<string, mixed> */
    protected function migrationOptions(): array
    {
        return [
            'dry_run' => (bool) $this->option('dry-run'),
            'chunk' => max(1, (int) $this->option('chunk')),
            'after_id' => $this->option('after-id') !== null ? (int) $this->option('after-id') : null,
            'until_id' => $this->option('until-id') !== null ? (int) $this->option('until-id') : null,
            'limit' => $this->option('limit') !== null ? (int) $this->option('limit') : null,
        ];
    }
}
