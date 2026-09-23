<?php

namespace LimenAi\Tests\Unit;

use LimenAi\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_config_is_registered(): void
    {
        $this->assertSame('example', config('limen-ai.default_agent'));
    }

    public function test_translations_are_available(): void
    {
        $this->assertSame(
            'Please login first.',
            trans('limen-ai::responses.unauthenticated')
        );
    }
}
