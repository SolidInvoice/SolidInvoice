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

namespace SolidInvoice\InvoiceBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\InvoiceBundle\Email\InvoiceEmail;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Listener\Mailer\InvoiceEmailTemplateListener;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use Symfony\Component\Mime\Email;
use function dirname;
use function sys_get_temp_dir;
use function uniqid;

final class InvoiceEmailTemplateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $invoiceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->invoiceDir = sys_get_temp_dir() . '/' . uniqid('invoice_email_tpl_', true) . '/invoice';
        $this->filesystem->dumpFile($this->invoiceDir . '/sleek/email.html.twig', 'sleek');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(dirname($this->invoiceDir));
    }

    public function testSwapsTheEmailTemplateWhenACustomTemplateIsSelected(): void
    {
        $invoice = new Invoice();
        $invoice->setCompany(new Company());

        $listener = new InvoiceEmailTemplateListener($this->createResolver($invoice->getCompany(), 'sleek'));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@SolidInvoiceInvoice/Templates/sleek/email.html.twig', $message->getHtmlTemplate());
    }

    public function testKeepsTheDefaultTemplateWhenNoCustomTemplateApplies(): void
    {
        $invoice = new Invoice();
        $invoice->setCompany(new Company());

        $listener = new InvoiceEmailTemplateListener($this->createResolver($invoice->getCompany(), null));

        $message = new InvoiceEmail($invoice);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@SolidInvoiceInvoice/Email/invoice.html.twig', $message->getHtmlTemplate());
    }

    public function testIgnoresOtherMessages(): void
    {
        $listener = new InvoiceEmailTemplateListener($this->createResolver(new Company(), 'sleek'));

        $message = new Email()->to('test@example.com')->from('from@example.com')->text('hi');
        $listener(new MessageEvent($message, Envelope::create($message), 'smtp'));

        $this->expectNotToPerformAssertions();
    }

    public function testRunsBeforeTheBodyRenderer(): void
    {
        $events = InvoiceEmailTemplateListener::getSubscribedEvents();

        self::assertSame(['__invoke', 100], $events[MessageEvent::class]);
    }

    private function createResolver(Company $company, ?string $slug): BillingTemplateResolver
    {
        $registry = new BillingTemplateRegistry([
            BillingDocumentType::Invoice->value => $this->invoiceDir,
        ]);

        $toggle = M::mock(ToggleInterface::class);
        $toggle->allows('isActive')->with('saas_enabled')->andReturnTrue();

        $systemConfig = M::mock(SystemConfig::class);
        $systemConfig->allows('get')
            ->with(BillingTemplateResolver::TEMPLATE_SETTING_KEY, $company)
            ->andReturn($slug);

        $subscriptionGate = M::mock(PaidSubscriptionGateInterface::class);
        $subscriptionGate->allows('isActive')->with($company)->andReturnTrue();

        $featureGate = M::mock(FeatureGate::class);
        $featureGate->allows('isEnabled')
            ->with(Feature::CustomTemplates->value, $company)
            ->andReturnTrue();

        return new BillingTemplateResolver($registry, $systemConfig, $featureGate, $subscriptionGate, $toggle);
    }
}
