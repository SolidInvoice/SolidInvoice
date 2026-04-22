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

namespace SolidInvoice\McpBundle\OAuth;

use League\OAuth2\Server\CryptKey;
use League\OAuth2\Server\CryptKeyInterface;
use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Manages RSA signing keys used for OAuth2 access-token JWTs.
 *
 * Keys live in var/oauth/ and are generated via bin/console mcp:keys:generate.
 */
final class KeyManager
{
    public function __construct(
        private readonly string $projectDir,
        private readonly string $encryptionKey,
    ) {
    }

    public function getPrivateKeyPath(): string
    {
        return $this->getKeyDir() . '/private.key';
    }

    public function getPublicKeyPath(): string
    {
        return $this->getKeyDir() . '/public.key';
    }

    public function getKeyDir(): string
    {
        return $this->projectDir . '/var/oauth';
    }

    public function hasKeys(): bool
    {
        return file_exists($this->getPrivateKeyPath()) && file_exists($this->getPublicKeyPath());
    }

    public function getPrivateKey(): CryptKeyInterface
    {
        $this->assertKeysExist();

        return new CryptKey($this->getPrivateKeyPath(), null, false);
    }

    public function getPublicKey(): CryptKeyInterface
    {
        $this->assertKeysExist();

        return new CryptKey($this->getPublicKeyPath(), null, false);
    }

    public function getEncryptionKey(): string
    {
        return $this->encryptionKey;
    }

    /**
     * Returns true if keys were generated, false if they already existed.
     */
    public function generate(bool $force = false): bool
    {
        $filesystem = new Filesystem();
        $filesystem->mkdir($this->getKeyDir(), 0700);

        if ($this->hasKeys() && ! $force) {
            return false;
        }

        $config = [
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ];

        $resource = openssl_pkey_new($config);

        if ($resource === false) {
            throw new RuntimeException('Failed to generate RSA key pair: ' . (openssl_error_string() ?: 'unknown error'));
        }

        if (! openssl_pkey_export($resource, $privateKeyPem)) {
            throw new RuntimeException('Failed to export private key: ' . (openssl_error_string() ?: 'unknown error'));
        }

        $details = openssl_pkey_get_details($resource);

        if ($details === false || ! isset($details['key'])) {
            throw new RuntimeException('Failed to read public key details.');
        }

        $publicKeyPem = $details['key'];

        file_put_contents($this->getPrivateKeyPath(), $privateKeyPem);
        chmod($this->getPrivateKeyPath(), 0600);

        file_put_contents($this->getPublicKeyPath(), $publicKeyPem);
        chmod($this->getPublicKeyPath(), 0644);

        return true;
    }

    private function assertKeysExist(): void
    {
        if (! $this->hasKeys()) {
            throw new RuntimeException(sprintf(
                'OAuth signing keys are missing. Run "bin/console mcp:keys:generate" to create them (expected at %s).',
                $this->getKeyDir(),
            ));
        }
    }
}
