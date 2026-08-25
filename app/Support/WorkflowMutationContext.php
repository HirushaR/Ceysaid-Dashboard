<?php

namespace App\Support;

final class WorkflowMutationContext
{
    private int $depth = 0;

    public function active(): bool
    {
        return $this->depth > 0;
    }

    public function run(callable $callback): mixed
    {
        $this->depth++;
        try {
            return $callback();
        } finally {
            $this->depth--;
        }
    }
}
