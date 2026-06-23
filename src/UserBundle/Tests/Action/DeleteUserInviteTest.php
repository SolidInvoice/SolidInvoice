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
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\Entity\UserInvitation;
use SolidInvoice\UserBundle\Enum\InvitationStatus;
use SolidInvoice\UserBundle\Test\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Zenstruck\Foundry\Test\Factories;

#[Group('functional')]
final class DeleteUserInviteTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    public function testDeletesPendingInvitation(): void
    {
        $id = $this->createInvitation(InvitationStatus::Pending);

        $this->loginAndRequestDelete($id);

        self::assertResponseRedirects('/users');
        self::assertNull(
            self::getContainer()->get('doctrine')->getRepository(UserInvitation::class)->find($id)
        );
    }

    public function testDeletesExpiredInvitation(): void
    {
        $id = $this->createInvitation(InvitationStatus::Expired);

        $this->loginAndRequestDelete($id);

        self::assertResponseRedirects('/users');
        self::assertNull(
            self::getContainer()->get('doctrine')->getRepository(UserInvitation::class)->find($id)
        );
    }

    private function createInvitation(InvitationStatus $status): string
    {
        $inviter = new User();
        $inviter->setEmail('inviter@example.com');
        $inviter->setPassword('invalid');
        $inviter->setEnabled(true);

        $manager = self::getContainer()->get('doctrine')->getManager();
        self::assertInstanceOf(ObjectManager::class, $manager);
        $manager->persist($inviter);

        if ($status === InvitationStatus::Expired) {
            CarbonImmutable::setTestNow(CarbonImmutable::now()->subDays(UserInvitation::VALIDITY_DAYS + 1));
        }

        $invitation = new UserInvitation();
        $invitation->setEmail('invitee@example.com')
            ->setInvitedBy($inviter)
            ->setCompany($this->company)
            ->setStatus($status);

        CarbonImmutable::setTestNow();

        $manager->persist($invitation);
        $manager->flush();

        $id = (string) $invitation->getId();
        $manager->clear();

        return $id;
    }

    private function loginAndRequestDelete(string $id): void
    {
        $user = UserFactory::createOne([
            'companies' => [$this->company],
            'email' => 'admin@example.com',
        ])->_real();

        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->loginUser($user);

        $client->request(Request::METHOD_GET, '/users/invite/' . $id . '/delete');
    }
}
