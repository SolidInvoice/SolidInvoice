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

namespace SolidInvoice\QuoteBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Line;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Enum\QuoteStatus;
use SolidInvoice\QuoteBundle\Test\Factory\QuoteFactory;
use Symfony\Component\Mime\Address;
use function array_map;

#[Group('functional')]
final class QuoteSendTest extends ApiTestCase
{
    protected function getResourceClass(): string
    {
        return Quote::class;
    }

    public function testSendFromDraftEmailsOnceAndMovesToPending(): void
    {
        $quote = $this->createQuote(QuoteStatus::Draft);
        $contact = $quote->getUsers()->first();
        self::assertInstanceOf(Contact::class, $contact);

        $data = self::$client->request('POST', $this->getIriFromResource($quote) . '/send', ['json' => [
            'cc' => ['accounts@example.com'],
            'subject' => 'Your quote',
            'message' => 'Thanks for having us today.',
            'attachPdf' => false,
        ]])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('pending', $data['status']);
        self::assertSame([$contact->getEmail(), 'accounts@example.com'], $data['sentTo']);

        // The `send` transition's workflow listener sends the email; the processor must not send a second.
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(QuoteEmail::class, $email);
        self::assertSame([$contact->getEmail()], $this->addresses($email->getTo()));
        self::assertSame(['accounts@example.com'], $this->addresses($email->getCc()));
        self::assertSame([$this->user->getEmail()], $this->addresses($email->getReplyTo()));
        self::assertSame('Your quote', $email->getSubject());
        self::assertSame([], $email->getAttachments());
        self::assertStringContainsString('Thanks for having us today.', (string) $email->getHtmlBody());
    }

    public function testResendFromPending(): void
    {
        $quote = $this->createQuote(QuoteStatus::Pending);

        $data = self::$client->request('POST', $this->getIriFromResource($quote) . '/send', ['json' => ['copyToMe' => true]])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('pending', $data['status']);
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(QuoteEmail::class, $email);
        self::assertSame([$this->user->getEmail()], $this->addresses($email->getBcc()));
        self::assertCount(1, $email->getAttachments());
    }

    public function testSendOnAcceptedIsAConflict(): void
    {
        self::$client->request('POST', $this->getIriFromResource($this->createQuote(QuoteStatus::Accepted)) . '/send', ['json' => []]);

        self::assertResponseStatusCodeSame(409);
        self::assertEmailCount(0);
    }

    public function testQuoteOfAnotherCompanyIsNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $client = ClientFactory::createOne(['company' => $otherCompany]);
        $foreign = QuoteFactory::createOne(['client' => $client, 'company' => $otherCompany, 'status' => QuoteStatus::Draft, 'users' => ContactFactory::createMany(1, ['client' => $client])]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        self::$client->request('POST', $this->getIriFromResource($foreign) . '/send', ['json' => []]);

        self::assertResponseStatusCodeSame(404);
        self::assertEmailCount(0);
    }

    private function createQuote(QuoteStatus $status): Quote
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        return QuoteFactory::createOne([
            'client' => $client,
            'status' => $status,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
        ]);
    }

    /**
     * @param list<Address> $addresses
     *
     * @return list<string>
     */
    private function addresses(array $addresses): array
    {
        return array_map(static fn (Address $address): string => $address->getAddress(), $addresses);
    }
}
