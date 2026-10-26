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

namespace SolidInvoice\CoreBundle\Tests\Form\Type;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Form\Type\CompanyCountryType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

final class CompanyCountryTypeTest extends TestCase
{
    /**
     * @return iterable<string, array{string|null, string|null, string|null}>
     */
    public static function prefillProvider(): iterable
    {
        yield 'detected country' => ['za', null, 'ZA'];
        yield 'unknown to Cloudflare' => ['XX', null, null];
        yield 'Tor exit node' => ['T1', null, null];
        yield 'no header' => [null, null, null];
        yield 'existing value wins' => ['ZA', 'DE', 'DE'];
    }

    #[DataProvider('prefillProvider')]
    public function testPrefillsTheCountryFromTheCloudflareHeader(?string $header, ?string $data, ?string $expected): void
    {
        $request = new Request();

        if ($header !== null) {
            $request->headers->set('CF-IPCountry', $header);
        }

        $requestStack = new RequestStack([$request]);

        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new CompanyCountryType($requestStack)], []))
            ->getFormFactory()
            ->create(CompanyCountryType::class, $data);

        self::assertSame($expected, $form->getData());
    }

    public function testLabelsAreATablerFlagFollowedByTheEscapedName(): void
    {
        $form = Forms::createFormFactoryBuilder()
            ->addExtension(new PreloadedExtension([new CompanyCountryType(new RequestStack())], []))
            ->getFormFactory()
            ->create(CompanyCountryType::class);

        $labels = [];
        foreach ($form->createView()->vars['choices'] as $choice) {
            $labels[$choice->value] = $choice->label;
        }

        self::assertSame('<span class="flag flag-xs flag-country-ba me-2"></span>Bosnia &amp; Herzegovina', $labels['BA']);
    }
}
