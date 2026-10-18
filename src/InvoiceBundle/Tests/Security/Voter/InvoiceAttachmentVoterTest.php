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

namespace SolidInvoice\InvoiceBundle\Tests\Security\Voter;

use Doctrine\Persistence\ManagerRegistry;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\TestWith;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceAttachment;
use SolidInvoice\InvoiceBundle\Security\Voter\InvoiceAttachmentVoter;
use SolidInvoice\UserBundle\Entity\User;
use stdClass;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Uid\Ulid;

#[CoversClass(InvoiceAttachmentVoter::class)]
final class InvoiceAttachmentVoterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    #[TestWith([InvoiceAttachmentVoter::ATTACHMENT_VIEW])]
    #[TestWith([InvoiceAttachmentVoter::ATTACHMENT_UPLOAD])]
    #[TestWith([InvoiceAttachmentVoter::ATTACHMENT_DELETE])]
    public function testGrantsAccessWhenCompanyMatches(string $attribute): void
    {
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = $this->tokenForUser(new Ulid());
        $attachment = $this->attachment($companyId);

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_GRANTED,
            $voter->vote($token, $attachment, [$attribute]),
        );
    }

    /**
     * Unlike ExportJobVoter, access is not restricted to whoever uploaded the file —
     * any authorised user in the company may act on it.
     */
    public function testGrantsAccessForAUserWhoDidNotUploadTheFile(): void
    {
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = $this->tokenForUser(new Ulid());
        $attachment = $this->attachment($companyId);

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_GRANTED,
            $voter->vote($token, $attachment, [InvoiceAttachmentVoter::ATTACHMENT_VIEW]),
        );
    }

    public function testDeniesWhenAttachmentCompanyIsDifferentFromActive(): void
    {
        $voter = $this->makeVoter(activeCompany: new Ulid());
        $token = $this->tokenForUser(new Ulid());
        $attachment = $this->attachment(new Ulid());

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_DENIED,
            $voter->vote($token, $attachment, [InvoiceAttachmentVoter::ATTACHMENT_VIEW]),
        );
    }

    public function testDeniesWhenNoCompanyIsActive(): void
    {
        $voter = $this->makeVoter(activeCompany: null);
        $token = $this->tokenForUser(new Ulid());
        $attachment = $this->attachment(new Ulid());

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_DENIED,
            $voter->vote($token, $attachment, [InvoiceAttachmentVoter::ATTACHMENT_VIEW]),
        );
    }

    public function testDeniesForANonUserToken(): void
    {
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn(null);
        $attachment = $this->attachment($companyId);

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_DENIED,
            $voter->vote($token, $attachment, [InvoiceAttachmentVoter::ATTACHMENT_VIEW]),
        );
    }

    public function testAbstainsOnUnsupportedAttribute(): void
    {
        $companyId = new Ulid();

        $voter = $this->makeVoter($companyId);
        $token = $this->tokenForUser(new Ulid());
        $attachment = $this->attachment($companyId);

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_ABSTAIN,
            $voter->vote($token, $attachment, ['SOMETHING_ELSE']),
        );
    }

    public function testAbstainsOnNonInvoiceAttachmentSubject(): void
    {
        $voter = $this->makeVoter(new Ulid());
        $token = $this->tokenForUser(new Ulid());

        self::assertSame(
            InvoiceAttachmentVoter::ACCESS_ABSTAIN,
            $voter->vote($token, new stdClass(), [InvoiceAttachmentVoter::ATTACHMENT_VIEW]),
        );
    }

    private function makeVoter(?Ulid $activeCompany): InvoiceAttachmentVoter
    {
        // CompanySelector is final, so we construct a real instance with a dummy
        // registry (only touched by switchCompany/reset which the voter never calls)
        // and force the active company via reflection.
        $selector = new CompanySelector(M::mock(ManagerRegistry::class));

        $companyIdProp = new ReflectionProperty(CompanySelector::class, 'companyId');
        $companyIdProp->setValue($selector, $activeCompany);

        return new InvoiceAttachmentVoter($selector);
    }

    private function tokenForUser(Ulid $userId): TokenInterface
    {
        $user = M::mock(User::class);
        $user->shouldReceive('getId')->andReturn($userId);

        $token = M::mock(TokenInterface::class);
        $token->shouldReceive('getUser')->andReturn($user);

        return $token;
    }

    private function attachment(Ulid $company): InvoiceAttachment
    {
        $companyEntity = M::mock(Company::class);
        $companyEntity->shouldReceive('getId')->andReturn($company);

        $attachment = new InvoiceAttachment(new Invoice(), 'receipt.pdf', 'application/pdf', 1024, 'storage-key');
        $attachment->setCompany($companyEntity);

        return $attachment;
    }
}
