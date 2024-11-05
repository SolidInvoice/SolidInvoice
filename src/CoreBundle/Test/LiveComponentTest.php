<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Test;

use const PASSWORD_DEFAULT;
use PHPUnit\Framework\MockObject\MockObject;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\UserBundle\Entity\User;
use Spatie\Snapshots\MatchesSnapshots;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockFileSessionStorage;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\UX\LiveComponent\Test\InteractsWithLiveComponents;
use function current;
use function password_hash;

abstract class LiveComponentTest extends KernelTestCase
{
    use InteractsWithLiveComponents;
    use EnsureApplicationInstalled;
    use MatchesSnapshots;

    protected KernelBrowser $client;

    protected MockObject&CsrfTokenManagerInterface $csrfTokenManager;

    protected function setUp(): void
    {
        $_SERVER['secret'] = $_ENV['secret'] = '$ecretf0rt3st';

        parent::setUp();

        $this->ensureSessionIsSet();

        $this->csrfTokenManager = $this->createMock(CsrfTokenManagerInterface::class);

        self::getContainer()
            ->set('security.csrf.token_manager', $this->csrfTokenManager);

        $this->client = self::getContainer()->get('test.client');
        $this->client->disableReboot();

        $this->csrfTokenManager
            ->method('isTokenValid')
            ->willReturn(true);
    }

    protected function ensureSessionIsSet(): void
    {
        $request = new Request();
        $session = new Session(new MockFileSessionStorage());
        $session->start();

        $request->setSession($session);
        self::getContainer()->get('request_stack')->push($request);
    }

    protected function getUser(): User
    {
        $registry = self::getContainer()->get('doctrine');

        $userRepository = $registry->getRepository(User::class);
        $companyRepository = $registry->getRepository(Company::class);

        /** @var User[] $users */
        $users = $userRepository->findAll();

        /** @var Company[] $companies */
        $companies = $companyRepository->findAll();

        if ([] === $users) {
            $user = new User();
            $user->setEmail('test@example.com')
                ->setEnabled(true)
                ->setPassword(password_hash('Password1', PASSWORD_DEFAULT));

            foreach ($companies as $company) {
                $user->addCompany($company);
            }

            $registry->getManager()->persist($user);
            $registry->getManager()->flush();
            $users = [$user];
        }

        return current($users);
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->client->getKernel()->shutdown();
        $this->client->getKernel()->boot();
    }

    protected function replaceUuid(string $content): string
    {
        $content = preg_replace('#[0-9A-Fa-f]{8}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{4}-[0-9A-Fa-f]{12}#', '91656880-2d93-11ef-933f-5a2cf21a5680', $content);
        return preg_replace('#[0-9A-f]{26}#', '01JBYEQCR7DJ2YW4EXP6FYJZCR', $content);
    }
}
