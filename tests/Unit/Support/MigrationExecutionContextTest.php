<?php

namespace Tests\Unit\Support;

use App\Support\MigrationExecutionContext;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MigrationExecutionContextTest extends TestCase
{
    #[Test]
    public function context_is_nested_and_always_resets(): void
    {
        $context = app(MigrationExecutionContext::class);

        try {
            $context->run(function () use ($context) {
                $this->assertTrue($context->active());
                $context->run(fn () => $this->assertTrue($context->active()));
                throw new RuntimeException('stop');
            });
        } catch (RuntimeException) {
            // Expected: verify finally restored the process-scoped context.
        }

        $this->assertFalse($context->active());
    }
}
