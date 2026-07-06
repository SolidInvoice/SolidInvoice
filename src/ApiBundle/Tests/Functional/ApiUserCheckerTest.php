<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\ApiBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\ApiTokenManager;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Client;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Component\HttpFoundation\Response;
use Zenstruck\Foundry\Test\Factories;

/**
 * Verifies the global disabled-account block is enforced at API authentication
 * via the firewall's {@see \SolidInvoice\UserBundle\Security\VerifiedUserChecker}.
 */
#[Group('functional')]
final class ApiUserCheckerTest extends ApiTestCase
{
    use Factories;

    protected function getResourceClass(): string
    {
        return Client::class;
    }

    public function testDisabledUserIsBlockedFromApi(): void
    {
        $disabled = UserFactory::createOne(['companies' => [$this->company], 'enabled' => false]);

        $manager = self::getContainer()->get(ApiTokenManager::class);
        self::assertInstanceOf(ApiTokenManager::class, $manager);
        $token = $manager->create($disabled, 'Disabled User Token');

        self::$client->request('GET', '/api/clients', [
            'headers' => [
                'X-API-TOKEN' => $token->plaintext,
                'accept' => 'application/ld+json',
            ],
        ]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNAUTHORIZED);
    }
}
