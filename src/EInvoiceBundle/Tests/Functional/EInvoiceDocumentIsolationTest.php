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

namespace SolidInvoice\EInvoiceBundle\Tests\Functional;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\CoreBundle\Test\Traits\DoctrineTestTrait;
use SolidInvoice\EInvoiceBundle\Entity\EInvoiceDocument;
use SolidInvoice\EInvoiceBundle\Repository\EInvoiceDocumentRepository;
use SolidInvoice\EInvoiceBundle\Test\Factory\EInvoiceDocumentFactory;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * A document created under one company must be invisible under another — through the
 * repository and through a plain DQL query — exactly like every other tenant-scoped entity.
 * Modelled on {@see \SolidInvoice\CoreBundle\Tests\Functional\AccountIsolationTest}.
 *
 * Enforced entirely by {@see \SolidInvoice\CoreBundle\Doctrine\Filter\CompanyFilter}: the
 * repository carries no manual `company` predicate.
 */
#[Group('functional')]
final class EInvoiceDocumentIsolationTest extends WebTestCase
{
    use DoctrineTestTrait;

    public function testDocumentsAreIsolatedPerCompany(): void
    {
        $filters = $this->em->getFilters();
        $wasEnabled = $filters->isEnabled('company');

        if ($wasEnabled) {
            $filters->disable('company');
        }

        $alphaCompany = CompanyFactory::createOne(['name' => 'alpha Inc']);
        $betaCompany = CompanyFactory::createOne(['name' => 'beta Inc']);

        $alphaInvoice = InvoiceFactory::createOne(['company' => $alphaCompany]);
        $betaInvoice = InvoiceFactory::createOne(['company' => $betaCompany]);

        $alphaDocument = EInvoiceDocumentFactory::createOne([
            'company' => $alphaCompany,
            'invoice' => $alphaInvoice,
        ]);
        $betaDocument = EInvoiceDocumentFactory::createOne([
            'company' => $betaCompany,
            'invoice' => $betaInvoice,
        ]);

        if ($wasEnabled) {
            $filters->enable('company');
        }

        $this->em->clear();

        $selector = self::getContainer()->get(CompanySelector::class);
        self::assertInstanceOf(CompanySelector::class, $selector);

        $selector->switchCompany($alphaCompany->getId());

        /** @var EInvoiceDocumentRepository $repository */
        $repository = $this->em->getRepository(EInvoiceDocument::class);

        $visible = $repository->findAll();
        self::assertCount(1, $visible, 'Alpha must only see its own e-invoice document.');
        self::assertTrue($visible[0]->getId()->equals($alphaDocument->getId()));

        self::assertNull(
            $repository->findOneBy(['id' => $betaDocument->getId()]),
            "Alpha must not be able to load Beta's document by id through the repository.",
        );

        $dqlResult = $this->em->createQuery('SELECT d FROM ' . EInvoiceDocument::class . ' d WHERE d.id = :id')
            ->setParameter('id', $betaDocument->getId())
            ->getResult();

        self::assertSame([], $dqlResult, "Alpha must not be able to load Beta's document through a plain DQL query.");

        $this->em->clear();
        $selector->switchCompany($betaCompany->getId());

        $visible = $repository->findAll();
        self::assertCount(1, $visible, 'Beta must only see its own e-invoice document.');
        self::assertTrue($visible[0]->getId()->equals($betaDocument->getId()));
    }
}
