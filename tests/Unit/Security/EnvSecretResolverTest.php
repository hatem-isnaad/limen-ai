<?php

namespace LimenAi\Tests\Unit\Security;

use LimenAi\Security\EnvSecretResolver;
use LimenAi\Tests\TestCase;

class EnvSecretResolverTest extends TestCase
{
    public function test_it_resolves_env_references(): void
    {
        putenv('LIMEN_TEST_SECRET=resolved-value');
        $_ENV['LIMEN_TEST_SECRET'] = 'resolved-value';

        $value = app(EnvSecretResolver::class)->resolve('env:LIMEN_TEST_SECRET');

        $this->assertSame('resolved-value', $value);
    }

    public function test_it_returns_empty_string_for_missing_env_key(): void
    {
        $value = app(EnvSecretResolver::class)->resolve('env:LIMEN_MISSING_SECRET_'.uniqid());

        $this->assertSame('', $value);
    }

    public function test_it_resolves_config_references(): void
    {
        config()->set('limen-ai.test.secret', 'config-value');

        $value = app(EnvSecretResolver::class)->resolve('limen-ai.test.secret');

        $this->assertSame('config-value', $value);
    }

    public function test_it_returns_literal_when_not_env_or_config(): void
    {
        $value = app(EnvSecretResolver::class)->resolve('plain-literal-token');

        $this->assertSame('plain-literal-token', $value);
    }

    public function test_it_prefers_env_prefix_over_config(): void
    {
        config()->set('LIMEN_TEST_KEY', 'from-config');

        putenv('LIMEN_TEST_KEY=from-env');
        $_ENV['LIMEN_TEST_KEY'] = 'from-env';

        $value = app(EnvSecretResolver::class)->resolve('env:LIMEN_TEST_KEY');

        $this->assertSame('from-env', $value);
    }
}
