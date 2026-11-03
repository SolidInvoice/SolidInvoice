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

namespace SolidInvoice\InvoiceBundle\Listener;

use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use SolidInvoice\InvoiceBundle\Manager\ReceiptManager;
use SolidInvoice\PaymentBundle\Config\ConfigProvider;
use SolidInvoice\PaymentBundle\Event\PaymentCompleteEvent;
use SolidInvoice\PaymentBundle\Event\PaymentEvents;
use SolidInvoice\SettingsBundle\SystemConfig;
use SolidWorx\Platform\PlatformBundle\Security\EmailVerification\EmailVerificationGateInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Throwable;

/**
 * Sends the receipt for every completed payment, however it arrived: the payment form, an online
 * gateway, the API or MCP.
 *
 * @see \SolidInvoice\InvoiceBundle\Tests\Functional\PaymentReceiptTest
 */
#[AsEventListener(event: PaymentEvents::PAYMENT_COMPLETE, priority: -10)]
final readonly class SendReceiptListener
{
    public function __construct(
        private ReceiptManager $receiptManager,
        private SystemConfig $config,
        private EmailVerificationGateInterface $emailVerificationGate,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(PaymentCompleteEvent $event): void
    {
        $payment = $event->getPayment();

        if (! $payment->hasReceipt() || $this->emailVerificationGate->isGated()) {
            return;
        }

        // A gateway's return page can be hit twice; only the first completion sends automatically.
        $send = $payment->getSendReceipt()
            ?? (! $payment->getReceiptSentAt() instanceof DateTimeImmutable && $this->config->get(ConfigProvider::RECEIPT_AUTO_SEND, $payment->getCompany()) === '1');

        if (! $send) {
            return;
        }

        try {
            $this->receiptManager->send($payment);
        } catch (Throwable $e) {
            // The payment is already recorded; a receipt that fails to go out must not undo that.
            $this->logger->error('Failed to send payment receipt', ['payment_id' => (string) $payment->getId(), 'exception' => $e]);
        }
    }
}
