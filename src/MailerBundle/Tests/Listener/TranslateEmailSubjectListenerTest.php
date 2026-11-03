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

namespace SolidInvoice\MailerBundle\Tests\Listener;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use SolidInvoice\InvoiceBundle\Email\InvoiceReminderEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\ReminderType;
use SolidInvoice\MailerBundle\Listener\TranslateEmailSubjectListener;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Message;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Component\Translation\Translator;

#[CoversClass(TranslateEmailSubjectListener::class)]
final class TranslateEmailSubjectListenerTest extends TestCase
{
    /**
     * @return iterable<string, array{ReminderType, string}>
     */
    public static function reminderSubjects(): iterable
    {
        yield 'pre-due' => [ReminderType::PreDue, 'Upcoming Payment Due: Invoice INV-001'];
        yield '1 day overdue' => [ReminderType::Overdue1, 'Payment Reminder: Invoice INV-001'];
        yield '7 days overdue' => [ReminderType::Overdue7, 'Payment Overdue: Invoice INV-001'];
        yield '14 days overdue' => [ReminderType::Overdue14, 'URGENT: Invoice INV-001 - Immediate Action Required'];
    }

    #[DataProvider('reminderSubjects')]
    public function testItTranslatesTheSubjectKeyWithTheEmailsParameters(ReminderType $type, string $expected): void
    {
        $invoice = new Invoice();
        $invoice->setInvoiceId('INV-001');

        $email = new InvoiceReminderEmail($invoice, $type);

        $this->dispatch($email);

        self::assertSame($expected, $email->getSubject());
    }

    public function testItTranslatesAKeyWithoutParameters(): void
    {
        $email = new Email()->subject('trial_downgraded.subject');

        $this->dispatch($email);

        self::assertSame("Your trial has ended, and you're on the Free plan", $email->getSubject());
    }

    public function testASubjectThatIsNotAKeyIsLeftAlone(): void
    {
        $email = new Email()->subject('New Invoice - #INV-001 (50% off)');

        $this->dispatch($email);

        self::assertSame('New Invoice - #INV-001 (50% off)', $email->getSubject());
    }

    public function testItIgnoresMessagesWithoutASubject(): void
    {
        $email = new Email();
        $message = new Message();

        $this->dispatch($email);
        $this->dispatch($message);

        self::assertNull($email->getSubject());
    }

    private function dispatch(Message $message): void
    {
        $translator = new Translator('en');
        $translator->addLoader('yaml', new YamlFileLoader());
        $translator->addResource('yaml', __DIR__ . '/../../../../translations/email.en.yml', 'en', 'email');

        $envelope = new Envelope(new Address('sender@example.com'), [new Address('client@example.com')]);

        new TranslateEmailSubjectListener($translator)(new MessageEvent($message, $envelope, 'smtp'));
    }
}
