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

namespace SolidInvoice\CoreBundle\Tests\Functional;

use BabDev\PagerfantaBundle\Cursor\SignedCursorEncoder;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function getenv;
use function putenv;

/**
 * SOLIDINVOICE_APP_SECRET is not set until the installer writes it, and it is never set while
 * a Docker image is built. Services that get kernel.secret, such as Pagerfanta's signed cursor
 * encoder (built while cache:clear warms Twig), must still be constructible in that state.
 */
final class AppSecretFallbackTest extends KernelTestCase
{
    private const string ENV_NAME = 'SOLIDINVOICE_APP_SECRET';

    /**
     * @var array{server: mixed, env: mixed, getenv: string|false}
     */
    private array $original;

    protected function setUp(): void
    {
        parent::setUp();

        $this->original = [
            'server' => $_SERVER[self::ENV_NAME] ?? null,
            'env' => $_ENV[self::ENV_NAME] ?? null,
            'getenv' => getenv(self::ENV_NAME),
        ];
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->restore('server', $this->original['server']);
        $this->restore('env', $this->original['env']);
        putenv(false === $this->original['getenv'] ? self::ENV_NAME : self::ENV_NAME . '=' . $this->original['getenv']);
    }

    public function testCursorEncoderIsBuiltWhenAppSecretIsEmpty(): void
    {
        $this->setAppSecret('');

        self::bootKernel();
        $container = self::getContainer();

        $secret = $container->getParameter('kernel.secret');
        self::assertIsString($secret);
        self::assertNotSame('', $secret);

        self::assertInstanceOf(SignedCursorEncoder::class, $container->get('test.pagerfanta.cursor_encoder.signed'));
    }

    public function testConfiguredAppSecretIsUsed(): void
    {
        $this->setAppSecret('configured-app-secret');

        self::bootKernel();

        self::assertSame('configured-app-secret', self::getContainer()->getParameter('kernel.secret'));
    }

    private function setAppSecret(string $value): void
    {
        $_SERVER[self::ENV_NAME] = $value;
        $_ENV[self::ENV_NAME] = $value;
        putenv(self::ENV_NAME . '=' . $value);
    }

    private function restore(string $bag, mixed $value): void
    {
        if ('server' === $bag) {
            if (null === $value) {
                unset($_SERVER[self::ENV_NAME]);
            } else {
                $_SERVER[self::ENV_NAME] = $value;
            }

            return;
        }

        if (null === $value) {
            unset($_ENV[self::ENV_NAME]);
        } else {
            $_ENV[self::ENV_NAME] = $value;
        }
    }
}
