<?php

namespace App\Services\Migrations;

use App\Models\DataMigrationIssue;
use App\Models\DataMigrationRun;
use Illuminate\Support\Str;
use Throwable;

final class MigrationRunManager
{
    public function start(string $key, string $mode, array $options = [], ?string $runUuid = null): DataMigrationRun
    {
        if ($runUuid !== null) {
            return DataMigrationRun::where('run_uuid', $runUuid)->firstOrFail();
        }

        return DataMigrationRun::create([
            'migration_key' => $key,
            'run_uuid' => (string) Str::uuid(),
            'mode' => $mode,
            'status' => 'running',
            'started_at' => now(),
            'started_by' => auth()->id(),
            'options' => $options,
        ]);
    }

    public function checkpoint(DataMigrationRun $run, int $lastId, int $processed = 1): void
    {
        $run->forceFill([
            'last_processed_id' => $lastId,
            'processed_count' => $run->processed_count + $processed,
        ])->save();
    }

    public function issue(DataMigrationRun $run, string $code, string $severity, string $sourceType, ?int $sourceId, string $message, array $details = []): DataMigrationIssue
    {
        $issue = $run->issues()->create([
            'issue_code' => $code,
            'severity' => $severity,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'message' => $message,
            'details' => $details,
        ]);

        $run->increment('error_count');

        return $issue;
    }

    public function complete(DataMigrationRun $run, array $summary): void
    {
        $run->forceFill(['status' => 'completed', 'completed_at' => now(), 'summary' => $summary])->save();
    }

    public function fail(DataMigrationRun $run, Throwable $exception): void
    {
        $run->forceFill(['status' => 'failed', 'completed_at' => now(), 'last_error' => $exception->getMessage()])->save();
    }
}
