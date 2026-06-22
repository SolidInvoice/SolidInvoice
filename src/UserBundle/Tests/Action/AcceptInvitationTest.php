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

namespace SolidInvoice\UserBundle\Tests\Action;

use Carbon\CarbonImmutable;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Uid\Ulid;

#[Group('functional')]
final class AcceptInvitationTest extends WebTestCase
{
    use EnsureApplicationInstalled;

    /**
     * A 26-character string that passes the security access control regex
     * (/invite/accept/[a-zA-Z0-9-]{26}) but is NOT a valid ULID because it
     * contains characters excluded from ULID's Crockford base32 alphabet
     * (e.g. 'O', 'L', 'I' are not valid ULID characters).
     *
     * Before the fix, this caused an unhandled InvalidArgumentException / 500.
     * After the fix, it returns a clean 404.
     */
    public function testReturns404ForInvalidUlidFormat(): void
    {
        // 26 chars (correct length), but uses 'O' and 'L' which are excluded from ULID's
        // Crockford base32 alphabet, so this passes the security regex but fails Ulid::isValid()
        $invalidUlid = '01JXKZ1ABLOOOO1LOOO1LOOOO1';

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/invite/accept/' . $invalidUlid);

        self::assertResponseStatusCodeSame(404);
    }

    public function testReturns404WhenInvitationNotFound(): void
    {
        $validUlid = (string) new Ulid();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/invite/accept/' . $validUlid);

        self::assertResponseStatusCodeSame(404);
    }

    public function testExpiredInvitationIsRejectedAndMarkedExpired(): void
    {
        $inviter = new User();
        $inviter->setEmail('inviter@example.com');
        $inviter->setPassword('invalid');
        $inviter->setEnabled(true);

        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $manager = $registry->getManager();
        $manager->persist($inviter);

        // Build an invitation whose validity window has already elapsed.
        CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS + 1));
        $invitation = new UserInvitation();
        $invitation->setEmail('expired-invite@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($this->company)
            ->setStatus(InvitationStatus::Pending);
        CarbonImmutable::setTestNow();

        $manager->persist($invitation);
        $manager->flush();

        $id = (string) $invitation->getId();
        $manager->clear();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->request(Request::METHOD_GET, '/invite/accept/' . $id);

        self::assertResponseStatusCodeSame(404);

        // The invitation is retained but flagged as expired when it is rejected.
        $invitation = self::getContainer()->get('doctrine')->getRepository(UserInvitation::class)->find($id);
        self::assertInstanceOf(UserInvitation::class, $invitation);
        self::assertSame(InvitationStatus::Expired, $invitation->getStatus());
    }
}
