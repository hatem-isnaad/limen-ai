<?php

namespace LimenAi\Tests\Integration;

use LimenAi\Authorization\LaravelAuthorizationService;
use LimenAi\Authorization\NullGuestSessionValidator;
use LimenAi\Contracts\Authorization\AuthorizationService;
use LimenAi\Contracts\Authorization\GuestSessionValidator;
use LimenAi\Tests\TestCase;

class AuthorizationBindingTest extends TestCase
{
    public function test_authorization_contracts_are_bound(): void
    {
        $this->assertInstanceOf(LaravelAuthorizationService::class, app(AuthorizationService::class));
        $this->assertInstanceOf(NullGuestSessionValidator::class, app(GuestSessionValidator::class));
    }
}
