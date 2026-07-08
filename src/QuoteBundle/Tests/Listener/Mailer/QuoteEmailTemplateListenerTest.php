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

namespace SolidInvoice\QuoteBundle\Tests\Listener\Mailer;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery as M;
use PHPUnit\Framework\TestCase;
use SolidInvoice\CoreBundle\Contracts\PaidSubscriptionGateInterface;
use SolidInvoice\CoreBundle\Entity\Company;
use SolidInvoice\CoreBundle\Templates\BillingDocumentType;
use SolidInvoice\CoreBundle\Templates\BillingTemplateRegistry;
use SolidInvoice\CoreBundle\Templates\BillingTemplateResolver;
use SolidInvoice\QuoteBundle\Email\QuoteEmail;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Listener\Mailer\QuoteEmailTemplateListener;
use SolidInvoice\SaasBundle\Feature\Feature;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Feature\FeatureGate;
use SolidWorx\Toggler\ToggleInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Event\MessageEvent;
use function dirname;
use function sys_get_temp_dir;
use function uniqid;

final class QuoteEmailTemplateListenerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private string $quoteDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->quoteDir = sys_get_temp_dir() . '/' . uniqid('quote_email_tpl_', true) . '/quote';
        $this->filesystem->dumpFile($this->quoteDir . '/sleek/email.html.twig', 'sleek');
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove(dirname($this->quoteDir));
    }

    public function testSwapsTheEmailTemplateWhenACustomTemplateIsSelected(): void
    {
        $quote = new Quote();
        $quote->setCompany(new Company());

        $listener = new QuoteEmailTemplateListener($this->createResolver($quote->getCompany(), 'sleek'));

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@SolidInvoiceQuote/Templates/sleek/email.html.twig', $message->getHtmlTemplate());
    }

    public function testKeepsTheDefaultTemplateWhenNoCustomTemplateApplies(): void
    {
        $quote = new Quote();
        $quote->setCompany(new Company());

        $listener = new QuoteEmailTemplateListener($this->createResolver($quote->getCompany(), null));

        $message = new QuoteEmail($quote);
        $listener(new MessageEvent($message, Envelope::create($message->to('test@example.com')->from('from@example.com')), 'smtp'));

        self::assertSame('@SolidInvoiceQuote/Email/quote.html.twig', $message->getHtmlTemplate());
    }

    public function testRunsBeforeTheBodyRenderer(): void
    {
        $events = QuoteEmailTemplateListener::getSubscribedEvents();

        self::assertSame(['__invoke', 100], $events[MessageEvent::class]);
    }

    private function createResolver(Company $company, ?string $slug): BillingTemplateResolver
    {
        $registry = new BillingTemplateRegistry([
            BillingDocumentType::Quote->value => $this->quoteDir,
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
