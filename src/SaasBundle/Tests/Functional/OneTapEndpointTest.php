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

namespace SolidInvoice\SaasBundle\Tests\Functional;

use const FILTER_NULL_ON_FAILURE;
use const FILTER_VALIDATE_BOOLEAN;
use const JSON_THROW_ON_ERROR;
use const PHP_URL_PATH;
use const PHP_URL_QUERY;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectRepository;
use Override;
use PHPUnit\Framework\Attributes\After;
use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\InstallBundle\Test\EnsureApplicationInstalled;
use SolidInvoice\SaasBundle\Security\OneTap\GoogleIdTokenVerifier;
use SolidInvoice\SaasBundle\Security\OneTap\IdTokenVerifierInterface;
use SolidInvoice\SaasBundle\Security\OneTap\InvalidIdTokenException;
use SolidInvoice\SaasBundle\Security\OneTap\OneTapToken;
use SolidInvoice\SaasBundle\Tests\SaasTestKernel;
use SolidInvoice\UserBundle\Entity\User;
use SolidInvoice\UserBundle\OAuth\GoogleIdentity;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Request;
use Throwable;
use Zenstruck\Foundry\Test\Factories;
use function filter_var;
use function is_bool;
use function json_decode;
use function json_encode;
use function parse_url;

#[Group('functional')]
final class OneTapEndpointTest extends WebTestCase
{
    use EnsureApplicationInstalled;
    use Factories;

    /**
     * @var list<string>
     */
    private array $envOverrides = [];

    #[After]
    public function resetEnvOverrides(): void
    {
        foreach ($this->envOverrides as $name) {
            unset($_SERVER[$name], $_ENV[$name]);
        }

        $this->envOverrides = [];
    }

    public function testNonceEndpointReturnsANonce(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_GET, '/onetap/nonce');

        self::assertResponseIsSuccessful();

        $payload = $this->decode($client);
        self::assertArrayHasKey('nonce', $payload);
        self::assertNotSame('', $payload['nonce']);
        self::assertSame(300, $payload['ttl']);
    }

    public function testVerifyEndpointAllowsTheMarketingOriginViaCors(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_OPTIONS, '/onetap/verify', server: [
            'HTTP_ORIGIN' => 'https://marketing.example.test',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ]);

        self::assertSame(
            'https://marketing.example.test',
            $client->getResponse()->headers->get('Access-Control-Allow-Origin'),
        );
    }

    public function testVerifyLinksAnExistingUserAndReturnsALoginLink(): void
    {
        $client = $this->bootClient();

        $this->persistUser('existing@example.com');

        $nonce = $this->issueNonce($client);
        $this->useVerifier(new OneTapToken(
            new GoogleIdentity('google-id-1', 'existing@example.com', true, 'Ada', 'Lovelace'),
            $nonce,
        ));

        $this->postCredential($client);

        self::assertResponseIsSuccessful();

        $payload = $this->decode($client);
        self::assertStringContainsString('/onetap/login-check', (string) $payload['loginLink']);
        self::assertFalse($payload['newUser']);

        $stored = $this->userRepository()->findOneBy(['email' => 'existing@example.com']);
        self::assertInstanceOf(User::class, $stored);
        self::assertSame('google-id-1', $stored->getGoogleId());
    }

    public function testVerifyCreatesANewUserWhenRegistrationIsEnabled(): void
    {
        $this->setEnv('SOLIDINVOICE_ALLOW_REGISTRATION', '1');
        $client = $this->bootClient();

        $nonce = $this->issueNonce($client);
        $this->useVerifier(new OneTapToken(
            new GoogleIdentity('google-id-2', 'new-user@example.com', true, 'Grace', 'Hopper'),
            $nonce,
        ));

        $this->postCredential($client);

        self::assertResponseIsSuccessful();

        $payload = $this->decode($client);
        self::assertTrue($payload['newUser']);

        $stored = $this->userRepository()->findOneBy(['email' => 'new-user@example.com']);
        self::assertInstanceOf(User::class, $stored);
        self::assertSame('google-id-2', $stored->getGoogleId());
        self::assertTrue($stored->isEnabled());
        self::assertTrue($stored->isVerified());
    }

    public function testVerifyRejectsAReusedNonce(): void
    {
        $this->setEnv('SOLIDINVOICE_ALLOW_REGISTRATION', '1');
        $client = $this->bootClient();

        $nonce = $this->issueNonce($client);
        $this->useVerifier(new OneTapToken(
            new GoogleIdentity('google-id-3', 'reuse@example.com', true, null, null),
            $nonce,
        ));

        $this->postCredential($client);
        self::assertResponseIsSuccessful();

        // Same nonce, second attempt: the nonce was burned on first use.
        $this->postCredential($client);
        self::assertSame(403, $client->getResponse()->getStatusCode());
    }

    public function testVerifyRejectsAnInvalidToken(): void
    {
        $client = $this->bootClient();

        $this->useVerifier(new InvalidIdTokenException('bad token'));

        $this->postCredential($client);

        self::assertSame(401, $client->getResponse()->getStatusCode());
    }

    public function testVerifyRejectsAMissingCredential(): void
    {
        $client = $this->bootClient();

        $client->request(Request::METHOD_POST, '/onetap/verify', content: '{}');

        self::assertSame(400, $client->getResponse()->getStatusCode());
    }

    public function testLoginLinkAuthenticatesTheUser(): void
    {
        $this->setEnv('SOLIDINVOICE_ALLOW_REGISTRATION', '1');
        $client = $this->bootClient();

        $nonce = $this->issueNonce($client);
        $this->useVerifier(new OneTapToken(
            new GoogleIdentity('google-id-4', 'link@example.com', true, 'Alan', 'Turing'),
            $nonce,
        ));

        $this->postCredential($client);
        self::assertResponseIsSuccessful();

        $loginLink = $this->decode($client)['loginLink'];
        $target = (string) parse_url((string) $loginLink, PHP_URL_PATH) . '?' . (string) parse_url((string) $loginLink, PHP_URL_QUERY);

        $client->request(Request::METHOD_GET, $target);

        // A successful login-link authentication redirects the now-authenticated
        // user into the app (the company selector, or onboarding for a brand-new
        // SaaS account). An invalid link would instead bounce back to the login
        // page, so landing on an authenticated destination proves the link
        // established a session.
        self::assertResponseRedirects();
        $location = (string) $client->getResponse()->headers->get('Location');
        self::assertMatchesRegularExpression('#/(select-company|onboarding)#', $location, $location);
        self::assertStringNotContainsString('/login', $location);
    }

    private function bootClient(): KernelBrowser
    {
        self::ensureKernelShutdown();
        $client = self::createClient();
        $client->disableReboot();

        return $client;
    }

    private function issueNonce(KernelBrowser $client): string
    {
        $client->request(Request::METHOD_GET, '/onetap/nonce');
        self::assertResponseIsSuccessful();

        return (string) $this->decode($client)['nonce'];
    }

    private function persistUser(string $email): void
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');
        $manager = $registry->getManager();

        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setVerified(true);
        $user->setPassword('unused-password-hash');

        $manager->persist($user);
        $manager->flush();
    }

    private function useVerifier(OneTapToken | Throwable $result): void
    {
        $verifier = $this->createMock(IdTokenVerifierInterface::class);

        if ($result instanceof Throwable) {
            $verifier->method('verify')->willThrowException($result);
        } else {
            $verifier->method('verify')->willReturn($result);
        }

        // Autowiring resolves IdTokenVerifierInterface to the concrete
        // GoogleIdTokenVerifier reference, so overriding the concrete service is
        // what the action actually receives.
        self::getContainer()->set(GoogleIdTokenVerifier::class, $verifier);
    }

    private function postCredential(KernelBrowser $client): void
    {
        $client->request(
            Request::METHOD_POST,
            '/onetap/verify',
            content: (string) json_encode(['credential' => 'signed-jwt']),
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(KernelBrowser $client): array
    {
        /** @var array<string, mixed> $data */
        $data = json_decode((string) $client->getResponse()->getContent(), true, flags: JSON_THROW_ON_ERROR);

        return $data;
    }

    /**
     * @return ObjectRepository<User>
     */
    private function userRepository(): ObjectRepository
    {
        /** @var ManagerRegistry $registry */
        $registry = self::getContainer()->get('doctrine');

        return $registry->getRepository(User::class);
    }

    private function setEnv(string $name, string $value): void
    {
        $_SERVER[$name] = $_ENV[$name] = $value;
        $this->envOverrides[] = $name;
    }

    /**
     * @param array<string, mixed> $options
     */
    #[Override]
    protected static function createKernel(array $options = []): SaasTestKernel
    {
        $env = $options['environment'] ?? $_ENV['SOLIDINVOICE_ENV'] ?? $_SERVER['SOLIDINVOICE_ENV'] ?? 'test';
        $debugRaw = $options['debug'] ?? $_ENV['SOLIDINVOICE_DEBUG'] ?? $_SERVER['SOLIDINVOICE_DEBUG'] ?? true;
        $debug = is_bool($debugRaw)
            ? $debugRaw
            : filter_var((string) $debugRaw, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? true;

        return new SaasTestKernel($env, $debug);
    }
}
