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

namespace SolidInvoice\CoreBundle\Tests\Doctrine\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Doctrine\Listener\CompanyOwnerListener;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\UserBundle\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[CoversClass(CompanyOwnerListener::class)]
final class CompanyOwnerListenerTest extends TestCase
{
    public function testTheSignedInUserBecomesTheOwner(): void
    {
        $user = new User();
        $company = new Company();

        new CompanyOwnerListener($this->signedIn($user))->prePersist($company);

        self::assertSame($user, $company->getOwner());
    }

    public function testAnOwnerTheCallerSetIsKept(): void
    {
        $owner = new User();
        $company = new Company()->setOwner($owner);

        new CompanyOwnerListener($this->signedIn(new User()))->prePersist($company);

        self::assertSame($owner, $company->getOwner());
    }

    public function testNoOwnerWithoutASignedInUser(): void
    {
        $company = new Company();

        new CompanyOwnerListener(new TokenStorage())->prePersist($company);

        self::assertNull($company->getOwner());
    }

    private function signedIn(User $user): TokenStorage
    {
        $tokenStorage = new TokenStorage();
        $tokenStorage->setToken(new UsernamePasswordToken($user, 'main', $user->getRoles()));

        return $tokenStorage;
    }
}
