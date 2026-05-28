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

namespace SolidInvoice\CoreBundle\Tests\Company;

use SolidInvoice\CoreBundle\Company\BillingTemplateInitializer;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use function assert;
use function count;

final class BillingTemplateInitializerTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private BillingTemplateInitializer $initializer;

    private BillingTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->em->getRepository(BillingTemplate::class);
        assert($repository instanceof BillingTemplateRepository);
        $this->repository = $repository;

        $companySelector = self::getContainer()->get(CompanySelector::class);
        assert($companySelector instanceof CompanySelector);

        $this->initializer = new BillingTemplateInitializer(
            $this->registry,
            $this->repository,
            $companySelector,
        );
    }

    public function testEnsureDefaultsIsIdempotent(): void
    {
        $before = count($this->repository->findAll());

        $this->initializer->ensureDefaults();
        $this->em->clear();

        self::assertCount($before, $this->repository->findAll());
    }

    public function testEnsureDefaultsBackfillsMissingSystemTemplate(): void
    {
        $system = $this->repository->findSystemTemplate(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_HTML);
        self::assertNotNull($system);

        $this->em->remove($system);
        $this->em->flush();
        $this->em->clear();

        $this->initializer->ensureDefaults();
        $this->em->clear();

        $restored = $this->repository->findSystemTemplate(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_HTML);
        self::assertNotNull($restored);
        self::assertTrue($restored->isSystem());
        self::assertTrue($restored->isActive(), 'Newly seeded system template becomes active when no other active row exists');
    }
}
