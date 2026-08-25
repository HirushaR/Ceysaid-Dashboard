<?php

namespace App\Domain\LeadWorkflow\Data;

use Illuminate\Support\Str;

final readonly class WorkflowRequestData
{
    public function __construct(
        public string $idempotencyKey,
        public int $expectedLockVersion,
        public string $source = 'ui',
        public ?string $requestId = null,
        public ?string $correlationId = null,
    ) {}

    public function correlationId(): string
    {
        return $this->correlationId ?? (string) Str::uuid();
    }
}
