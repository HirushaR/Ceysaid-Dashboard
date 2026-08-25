<?php

namespace App\Domain\LeadWorkflow\Data;

final readonly class GateResult
{
    /** @param list<array{code:string,message:string}> $blockers */
    public function __construct(public array $blockers = []) {}

    public function passed(): bool
    {
        return $this->blockers === [];
    }
}
