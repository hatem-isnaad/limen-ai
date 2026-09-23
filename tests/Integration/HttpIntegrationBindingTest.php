<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Contracts\Integrations\HttpConnectorRepository;
use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Security\SecretResolver;
use LimenAi\Contracts\Security\UrlValidator;
use LimenAi\Integrations\ConfigHttpConnectorRepository;
use LimenAi\Integrations\DeclarativeHttpToolExecutor;
use LimenAi\Integrations\HttpIntegrationValidator;
use LimenAi\Integrations\HttpRequestBuilder;
use LimenAi\Security\EnvSecretResolver;
use LimenAi\Security\SsrfUrlValidator;
use LimenAi\Tests\TestCase;

class HttpIntegrationBindingTest extends TestCase
{
    public function test_http_integration_contracts_are_bound(): void
    {
        $this->assertInstanceOf(ConfigHttpConnectorRepository::class, app(HttpConnectorRepository::class));
        $this->assertInstanceOf(SsrfUrlValidator::class, app(UrlValidator::class));
        $this->assertInstanceOf(EnvSecretResolver::class, app(SecretResolver::class));
        $this->assertInstanceOf(HttpRequestBuilder::class, app(HttpRequestBuilder::class));
        $this->assertInstanceOf(DeclarativeHttpToolExecutor::class, app(HttpToolExecutor::class));
        $this->assertInstanceOf(HttpIntegrationValidator::class, app(HttpIntegrationValidator::class));
    }
}
