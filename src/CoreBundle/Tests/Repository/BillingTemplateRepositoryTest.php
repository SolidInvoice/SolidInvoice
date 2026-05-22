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

namespace SolidInvoice\CoreBundle\Tests\Repository;

use SolidInvoice\CoreBundle\Entity\BillingTemplate;
use SolidInvoice\CoreBundle\Repository\BillingTemplateRepository;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class BillingTemplateRepositoryTest extends KernelTestCase
{
    use DoctrineTestTrait;

    private BillingTemplateRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();

        $repository = $this->em->getRepository(\SolidInvoice\CoreBundle\Entity\BillingTemplate::class);

        if (! $repository instanceof BillingTemplateRepository) {
            self::fail(sprintf(
                'Expected %s, got %s',
                BillingTemplateRepository::class,
                $repository::class,
            ));
        }

        $this->repository = $repository;
    }

    public function testInstallationSeedsSixSystemTemplates(): void
    {
        $templates = $this->repository->findAllForCompany($this->company);

        self::assertCount(6, $templates);

        foreach ($templates as $template) {
            self::assertTrue($template->isSystem());
            self::assertTrue($template->isActive());
            self::assertSame(BillingTemplate::DEFAULT_NAME, $template->getName());
        }
    }

    public function testFindActiveReturnsSeededTemplate(): void
    {
        $active = $this->repository->findActive(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_PDF);

        self::assertNotNull($active);
        self::assertSame(BillingTemplate::TYPE_INVOICE, $active->getType());
        self::assertSame(BillingTemplate::VARIANT_PDF, $active->getVariant());
        self::assertTrue($active->isActive());
    }

    public function testFindAllForVariantOrdersSystemFirst(): void
    {
        $custom = new BillingTemplate();
        $custom->setType(BillingTemplate::TYPE_INVOICE);
        $custom->setVariant(BillingTemplate::VARIANT_HTML);
        $custom->setName('Alternate');
        $custom->setContent('<p>Custom</p>');
        $custom->setActive(false);
        $custom->setSystem(false);
        $custom->setCompany($this->company);
        $this->repository->save($custom);

        $all = $this->repository->findAllForVariant(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_HTML);

        self::assertCount(2, $all);
        self::assertTrue($all[0]->isSystem(), 'system template should come first');
        self::assertSame('Alternate', $all[1]->getName());
    }

    public function testSetActiveSwitchesFlagAtomically(): void
    {
        $custom = new BillingTemplate();
        $custom->setType(BillingTemplate::TYPE_QUOTE);
        $custom->setVariant(BillingTemplate::VARIANT_HTML);
        $custom->setName('Modern');
        $custom->setContent('<p>Modern</p>');
        $custom->setActive(false);
        $custom->setSystem(false);
        $custom->setCompany($this->company);
        $this->repository->save($custom);

        $this->repository->setActive($custom);

        $this->em->clear();

        $active = $this->repository->findActive(BillingTemplate::TYPE_QUOTE, BillingTemplate::VARIANT_HTML);
        self::assertNotNull($active);
        self::assertSame('Modern', $active->getName());

        $all = $this->repository->findAllForVariant(BillingTemplate::TYPE_QUOTE, BillingTemplate::VARIANT_HTML);
        $activeCount = 0;
        foreach ($all as $template) {
            if ($template->isActive()) {
                $activeCount++;
            }
        }
        self::assertSame(1, $activeCount, 'exactly one template per variant must be active');
    }

    public function testDeleteSkipsActiveAndSystemTemplates(): void
    {
        $system = $this->repository->findSystemTemplate(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_HTML);
        self::assertNotNull($system);

        $this->repository->delete($system);

        // Still in DB
        $stillThere = $this->repository->findSystemTemplate(BillingTemplate::TYPE_INVOICE, BillingTemplate::VARIANT_HTML);
        self::assertNotNull($stillThere);
    }

    public function testDeleteRemovesInactiveCustomTemplate(): void
    {
        $custom = new BillingTemplate();
        $custom->setType(BillingTemplate::TYPE_INVOICE);
        $custom->setVariant(BillingTemplate::VARIANT_EMAIL);
        $custom->setName('Removable');
        $custom->setContent('<p>x</p>');
        $custom->setActive(false);
        $custom->setSystem(false);
        $custom->setCompany($this->company);
        $this->repository->save($custom);

        $this->repository->delete($custom);

        $found = $this->repository->findOneBy(['name' => 'Removable']);
        self::assertNull($found);
    }
}
