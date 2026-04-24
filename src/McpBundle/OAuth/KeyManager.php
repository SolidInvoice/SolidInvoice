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
 * Manages the RSA signing keys used for OAuth2 access-token JWTs.
 *
 * Keys are persisted under SOLIDINVOICE_CONFIG_DIR/oauth/ so they survive
 * redeployments alongside the rest of the app config. `bin/console
 * mcp:keys:generate` writes them; FrankenPHP's launcher calls that command on
 * first boot so deployments don't need a manual setup step.
 */
final class KeyManager
{
    public function __construct(
        private readonly string $configDir,
        private readonly ?string $encryptionKey,
        private readonly Filesystem $filesystem = new Filesystem(),
    ) {
        if ($this->encryptionKey === null || $this->encryptionKey === '') {
            throw new RuntimeException('SOLIDINVOICE_APP_SECRET must be configured before using MCP (used as the OAuth auth-code encryption key).');
        }
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
        return rtrim($this->configDir, '/') . '/oauth';
    }

    public function hasKeys(): bool
    {
        return $this->filesystem->exists($this->getPrivateKeyPath())
            && $this->filesystem->exists($this->getPublicKeyPath());
    }

    public function getPrivateKey(): CryptKeyInterface
    {
        $this->assertKeysExist();

        // The private key is generated with 0600 permissions (see generate()),
        // so League's permission check gives us defence-in-depth against
        // operator mistakes that widen access to OAuth signing material.
        return new CryptKey($this->getPrivateKeyPath());
    }

    public function getPublicKey(): CryptKeyInterface
    {
        $this->assertKeysExist();

        return new CryptKey($this->getPublicKeyPath(), null, false);
    }

    public function getEncryptionKey(): string
    {
        return (string) $this->encryptionKey;
    }

    /**
     * Returns true if keys were generated, false if they already existed.
     *
     * Uses OpenSSL's CSPRNG for the key material — no need to mix in the app
     * secret (OPENSSL_KEYTYPE_RSA already draws from /dev/urandom). The app
     * secret is only used as the auth-code encryption key over in ServerFactory.
     */
    public function generate(bool $force = false): bool
    {
        $this->filesystem->mkdir($this->getKeyDir(), 0700);

        if ($this->hasKeys() && ! $force) {
            return false;
        }

        $resource = openssl_pkey_new([
            'digest_alg' => 'sha256',
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

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

        $this->filesystem->dumpFile($this->getPrivateKeyPath(), $privateKeyPem);
        $this->filesystem->chmod($this->getPrivateKeyPath(), 0600);

        $this->filesystem->dumpFile($this->getPublicKeyPath(), $details['key']);
        $this->filesystem->chmod($this->getPublicKeyPath(), 0644);

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
