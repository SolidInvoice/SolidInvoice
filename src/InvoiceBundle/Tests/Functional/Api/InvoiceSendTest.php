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

namespace SolidInvoice\InvoiceBundle\Tests\Functional\Api;

use PHPUnit\Framework\Attributes\Group;
use SolidInvoice\ApiBundle\Test\ApiTestCase;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\ClientBundle\Test\Factory\ClientFactory;
use SolidInvoice\ClientBundle\Test\Factory\ContactFactory;
use SolidInvoice\CoreBundle\Company\CompanySelector;
use SolidInvoice\CoreBundle\Enum\SendChannel;
use SolidInvoice\CoreBundle\Event\DocumentSentEvent;
use SolidInvoice\CoreBundle\Test\Factory\CompanyFactory;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Email\ManualInvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\Line;
use SolidInvoice\InvoiceBundle\Enum\InvoiceStatus;
use SolidInvoice\InvoiceBundle\Test\Factory\InvoiceFactory;
use SolidWorx\Platform\PlatformBundle\Model\UserInterface;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function array_map;

#[Group('functional')]
final class InvoiceSendTest extends ApiTestCase
{
    /**
     * @var list<DocumentSentEvent>
     */
    private array $events = [];

    protected function getResourceClass(): string
    {
        return Invoice::class;
    }

    public function testSendFromDraftEmailsTheContactsAndMovesToPending(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Draft);
        $contact = $invoice->getUsers()->first();
        self::assertInstanceOf(Contact::class, $contact);
        $this->captureEvents();

        $data = $this->send($invoice)->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('pending', $data['status']);
        self::assertSame([$contact->getEmail()], $data['sentTo']);
        self::assertSame($this->getIriFromResource($invoice), $data['@id']);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(InvoiceEmail::class, $email);
        self::assertSame([$contact->getEmail()], $this->addresses($email->getTo()));
        self::assertSame([$this->user->getEmail()], $this->addresses($email->getReplyTo()));
        self::assertSame([], $this->addresses($email->getBcc()));
        self::assertCount(1, $email->getAttachments());

        self::assertCount(1, $this->events);
        self::assertSame(SendChannel::Email, $this->events[0]->channel);
        self::assertSame([$contact->getEmail()], $this->events[0]->recipients);
    }

    public function testSendWithOptions(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::New);
        $other = ContactFactory::createOne(['client' => $invoice->getClient(), 'email' => 'other@example.com']);

        $data = $this->send($invoice, [
            'to' => [$this->getIriFromResource($other)],
            'cc' => ['accounts@example.com'],
            'subject' => 'Your invoice',
            'message' => "<b>Thanks</b> for having us\ntoday.",
            'attachPdf' => false,
            'copyToMe' => true,
        ])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame(['other@example.com', 'accounts@example.com'], $data['sentTo']);
        // On the first send the named recipients become the invoice's contacts.
        self::assertSame([$this->getIriFromResource($other)], $data['users']);

        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(InvoiceEmail::class, $email);
        self::assertSame(['other@example.com'], $this->addresses($email->getTo()));
        self::assertSame(['accounts@example.com'], $this->addresses($email->getCc()));
        self::assertSame([$this->user->getEmail()], $this->addresses($email->getBcc()));
        self::assertSame('Your invoice', $email->getSubject());
        self::assertSame([], $email->getAttachments());
        self::assertStringContainsString('&lt;b&gt;Thanks&lt;/b&gt; for having us<br>', (string) $email->getHtmlBody());
        self::assertStringNotContainsString('<b>Thanks</b>', (string) $email->getHtmlBody());
    }

    public function testResendOnPendingEmailsAgainWithoutReplacingTheContacts(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Pending);
        $original = $invoice->getUsers()->first();
        self::assertInstanceOf(Contact::class, $original);
        $other = ContactFactory::createOne(['client' => $invoice->getClient()]);

        $data = $this->send($invoice, ['to' => [$this->getIriFromResource($other)]])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('pending', $data['status']);
        self::assertSame([$this->getIriFromResource($original)], $data['users']);
        self::assertEmailCount(1);
        $email = self::getMailerMessage();
        self::assertInstanceOf(InvoiceEmail::class, $email);
        self::assertSame([$other->getEmail()], $this->addresses($email->getTo()));
    }

    public function testSendOnPaidIsAConflict(): void
    {
        $this->send($this->createInvoice(InvoiceStatus::Paid));

        self::assertResponseStatusCodeSame(409);
        self::assertEmailCount(0);
    }

    public function testSendWithoutRecipientsIsRejected(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Draft, ['users' => []]);

        $data = $this->send($invoice)->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('no_recipients', $data['violations'][0]['code']);
        self::assertEmailCount(0);
        self::assertSame(InvoiceStatus::Draft, $invoice->getStatus());
    }

    public function testSendToAContactOfAnotherClientIsRejected(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Draft);
        $stranger = ContactFactory::createOne(['client' => ClientFactory::createOne()]);

        $data = $this->send($invoice, ['to' => [$this->getIriFromResource($stranger)]])->toArray(false);

        self::assertResponseStatusCodeSame(422);
        self::assertSame('unknown_contact', $data['violations'][0]['code']);
        self::assertEmailCount(0);
    }

    public function testInvalidCcIsRejected(): void
    {
        $this->send($this->createInvoice(InvoiceStatus::Draft), ['cc' => ['not-an-email']]);

        self::assertResponseStatusCodeSame(422);
        self::assertEmailCount(0);
    }

    public function testUnverifiedUserCannotSend(): void
    {
        $this->gateApiCalls();

        $this->send($this->createInvoice(InvoiceStatus::Draft));

        self::assertResponseStatusCodeSame(403);
        self::assertEmailCount(0);
    }

    public function testUnverifiedUserCanSendToThemselvesOnly(): void
    {
        $this->gateApiCalls();
        $invoice = $this->createInvoice(InvoiceStatus::Draft);
        $self = ContactFactory::createOne(['client' => $invoice->getClient(), 'email' => $this->user->getEmail()]);

        $data = $this->send($invoice, ['to' => [$this->getIriFromResource($self)]])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame([$this->user->getEmail()], $data['sentTo']);
        self::assertEmailCount(1);
    }

    public function testDocumentsOfAnotherCompanyAreNotFound(): void
    {
        $otherCompany = CompanyFactory::new()->create();
        self::getContainer()->get(CompanySelector::class)->switchCompany($otherCompany->getId());
        $client = ClientFactory::createOne(['company' => $otherCompany]);
        $foreign = InvoiceFactory::createOne(['client' => $client, 'company' => $otherCompany, 'status' => InvoiceStatus::Pending, 'users' => ContactFactory::createMany(1, ['client' => $client])]);
        self::getContainer()->get(CompanySelector::class)->switchCompany($this->company->getId());

        foreach (['send', 'reminder', 'mark-sent'] as $action) {
            self::$client->request('POST', $this->getIriFromResource($foreign) . '/' . $action, ['json' => []]);

            self::assertResponseStatusCodeSame(404);
        }

        self::assertEmailCount(0);
    }

    public function testReminderOnPendingInvoiceIsLimitedToOnePerHour(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Pending);
        $contact = $invoice->getUsers()->first();
        self::assertInstanceOf(Contact::class, $contact);

        $data = self::$client->request('POST', $this->getIriFromResource($invoice) . '/reminder', ['json' => []])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame([$contact->getEmail()], $data['sentTo']);
        self::assertEmailCount(1);
        self::assertInstanceOf(ManualInvoiceReminderEmail::class, self::getMailerMessage());

        $response = self::$client->request('POST', $this->getIriFromResource($invoice) . '/reminder', ['json' => []]);

        self::assertResponseStatusCodeSame(429);
        self::assertGreaterThan(0, (int) ($response->getHeaders(false)['retry-after'][0] ?? 0));
        self::assertEmailCount(0);
    }

    public function testReminderOnDraftIsAConflict(): void
    {
        self::$client->request('POST', $this->getIriFromResource($this->createInvoice(InvoiceStatus::Draft)) . '/reminder', ['json' => []]);

        self::assertResponseStatusCodeSame(409);
        self::assertEmailCount(0);
    }

    public function testMarkSentMovesToPendingWithoutEmail(): void
    {
        $invoice = $this->createInvoice(InvoiceStatus::Draft);
        $this->captureEvents();

        $data = self::$client->request('POST', $this->getIriFromResource($invoice) . '/mark-sent', ['json' => ['channel' => 'whatsapp']])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('pending', $data['status']);
        self::assertArrayNotHasKey('sentTo', $data);
        self::assertEmailCount(0);
        self::assertCount(1, $this->events);
        self::assertSame(SendChannel::Whatsapp, $this->events[0]->channel);
        self::assertSame([], $this->events[0]->recipients);
    }

    public function testMarkSentOnPendingKeepsTheStatus(): void
    {
        $data = self::$client->request('POST', $this->getIriFromResource($this->createInvoice(InvoiceStatus::Overdue)) . '/mark-sent', ['json' => []])->toArray();

        self::assertResponseStatusCodeSame(200);
        self::assertSame('overdue', $data['status']);
    }

    public function testMarkSentRejectsEmailChannelAndPaidInvoices(): void
    {
        self::$client->request('POST', $this->getIriFromResource($this->createInvoice(InvoiceStatus::Draft)) . '/mark-sent', ['json' => ['channel' => 'email']]);
        self::assertResponseStatusCodeSame(422);

        self::$client->request('POST', $this->getIriFromResource($this->createInvoice(InvoiceStatus::Paid)) . '/mark-sent', ['json' => []]);
        self::assertResponseStatusCodeSame(409);
    }

    /**
     * @param array<string, mixed> $body
     */
    private function send(Invoice $invoice, array $body = []): ResponseInterface
    {
        // The body is optional: no body at all is a plain send.
        return self::$client->request('POST', $this->getIriFromResource($invoice) . '/send', [] === $body
            ? ['headers' => ['accept' => 'application/ld+json', 'content-type' => 'application/ld+json']]
            : ['json' => $body, 'headers' => ['accept' => 'application/ld+json']]);
    }

    /**
     * @param array<string, mixed> $attributes
     */
    private function createInvoice(InvoiceStatus $status, array $attributes = []): Invoice
    {
        $client = ClientFactory::createOne(['currencyCode' => 'USD']);

        return InvoiceFactory::createOne([
            'client' => $client,
            'status' => $status,
            'users' => ContactFactory::createMany(1, ['client' => $client]),
            'lines' => [new Line()->setName('Test Item')->setQty(1)->setPrice(10000)],
            ...$attributes,
        ]);
    }

    private function captureEvents(): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $dispatcher->addListener(DocumentSentEvent::class, function (DocumentSentEvent $event): void {
            $this->events[] = $event;
        });
    }

    /**
     * The API firewall already refuses an unverified user, through the same gate. Gate only the
     * calls that do not name a user, which is how the processors ask, to reach the send itself.
     */
    private function gateApiCalls(): void
    {
        $gate = $this->createStub(EmailVerificationGateInterface::class);
        $gate->method('isGated')->willReturnCallback(static fn (?UserInterface $user = null): bool => ! $user instanceof UserInterface);
        $gate->method('reason')->willReturn('Please verify your email address.');

        self::getContainer()->set(EmailVerificationGateInterface::class, $gate);
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
