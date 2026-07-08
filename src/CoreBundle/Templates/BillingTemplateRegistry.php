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

namespace SolidInvoice\CoreBundle\Templates;

use function array_keys;
use function basename;
use function glob;
use function in_array;
use function is_file;
use function sort;
use function sprintf;
use function str_replace;
use function str_starts_with;
use function ucwords;

/**
 * Discovers the billing design templates that ship under each bundle's
 * `Resources/views/Templates/<slug>/` directory. Slugs are derived purely
 * from the filesystem, so dropping in a new template directory (with
 * `pdf.html.twig`, `email.html.twig` and `preview.html.twig` files) makes it
 * available everywhere — settings picker, previews and rendering — without
 * any code changes.
 *
 * Directories starting with an underscore are shared partials, not templates.
 * @see \SolidInvoice\CoreBundle\Tests\Templates\BillingTemplateRegistryTest
 */
final class BillingTemplateRegistry
{
    /**
     * Sentinel slug for the built-in templates that ship with SolidInvoice
     * and are always used on self-hosted installs.
     */
    public const string DEFAULT_SLUG = 'default';

    /**
     * @var list<string>|null
     */
    private ?array $slugs = null;

    /**
     * @param array<string, string> $templateDirectories absolute template directory per BillingDocumentType value
     */
    public function __construct(
        private readonly array $templateDirectories,
    ) {
    }

    /**
     * All discovered template slugs, excluding the default sentinel.
     *
     * @return list<string>
     */
    public function getSlugs(): array
    {
        if (null !== $this->slugs) {
            return $this->slugs;
        }

        $slugs = [];

        foreach ($this->templateDirectories as $directory) {
            foreach (glob($directory . '/*', GLOB_ONLYDIR) ?: [] as $path) {
                $slug = basename($path);

                if (str_starts_with($slug, '_') || self::DEFAULT_SLUG === $slug) {
                    continue;
                }

                $slugs[$slug] = true;
            }
        }

        $slugs = array_keys($slugs);
        sort($slugs);

        return $this->slugs = $slugs;
    }

    public function has(string $slug): bool
    {
        return in_array($slug, $this->getSlugs(), true);
    }

    /**
     * Human-friendly labels for the settings picker, keyed by slug.
     *
     * @return array<string, string>
     */
    public function getChoices(): array
    {
        $choices = [];

        foreach ($this->getSlugs() as $slug) {
            $choices[$slug] = ucwords(str_replace(['-', '_'], ' ', $slug));
        }

        return $choices;
    }

    /**
     * The Twig template for a slug/document/channel combination, or null when
     * that variant does not exist (e.g. a template that only ships an invoice
     * design). Callers fall back to the default template on null.
     */
    public function templatePath(string $slug, BillingDocumentType $documentType, BillingTemplateChannel $channel): ?string
    {
        $directory = $this->templateDirectories[$documentType->value] ?? null;

        if (null === $directory || ! is_file(sprintf('%s/%s/%s', $directory, $slug, $channel->fileName()))) {
            return null;
        }

        return sprintf('%s/Templates/%s/%s', $documentType->twigNamespace(), $slug, $channel->fileName());
    }
}
