<?php

namespace Tests\Unit\Support;

use App\Models\User;
use App\Support\FeatureFlag;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FeatureFlagTest extends TestCase
{
    #[Test]
    public function flags_are_disabled_by_default(): void
    {
        $this->assertFalse(app(FeatureFlag::class)->enabled('workflow.schema_ready'));
    }

    #[Test]
    public function enabled_flags_can_target_users_and_roles(): void
    {
        config()->set('workflow.flags.schema_ready', ['enabled' => true, 'users' => [7], 'roles' => ['admin']]);

        $flags = app(FeatureFlag::class);
        $this->assertTrue($flags->enabled('workflow.schema_ready', (new User)->forceFill(['id' => 7, 'role' => 'sales'])));
        $this->assertTrue($flags->enabled('workflow.schema_ready', (new User)->forceFill(['id' => 8, 'role' => 'admin'])));
        $this->assertFalse($flags->enabled('workflow.schema_ready', (new User)->forceFill(['id' => 8, 'role' => 'sales'])));
    }
}
