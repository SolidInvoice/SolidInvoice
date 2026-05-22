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

namespace SolidInvoice\CoreBundle\Tests\Action\BillingTemplate;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use SolidInvoice\CoreBundle\Action\BillingTemplate\Index;
use SolidInvoice\CoreBundle\Company\BillingTemplateInitializer;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class IndexTest extends KernelTestCase
{
    use DoctrineTestTrait;
    use MockeryPHPUnitIntegration;

    public function testIndexReturnsGroupedTemplates(): void
    {
        $repository = $this->em->getRepository(BillingTemplate::class);
        \assert($repository instanceof BillingTemplateRepository);

        // We don't need the initializer's real implementation here; the
        // DefaultData seeded the templates when the company was created.
        $initializer = M::mock(BillingTemplateInitializer::class);
        $initializer->shouldReceive('ensureDefaults')->zeroOrMoreTimes();

        $action = new Index($repository, $initializer);
        $data = $action();

        self::assertArrayHasKey('grouped', $data);
        self::assertArrayHasKey(BillingTemplate::TYPE_INVOICE, $data['grouped']);
        self::assertArrayHasKey(BillingTemplate::TYPE_QUOTE, $data['grouped']);
        self::assertArrayHasKey(BillingTemplate::VARIANT_HTML, $data['grouped'][BillingTemplate::TYPE_INVOICE]);
        self::assertArrayHasKey(BillingTemplate::VARIANT_PDF, $data['grouped'][BillingTemplate::TYPE_INVOICE]);
        self::assertArrayHasKey(BillingTemplate::VARIANT_EMAIL, $data['grouped'][BillingTemplate::TYPE_INVOICE]);
    }
}
