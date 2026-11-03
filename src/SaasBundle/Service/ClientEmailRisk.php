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

namespace SolidInvoice\SaasBundle\Service;

use DateTimeImmutable;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Clock\ClockInterface;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\QuoteBundle\Entity\Quote;
use function array_sum;
use function implode;
use function preg_match;

/**
 * Scores an invoice or quote email for the callback-phishing pattern: a fake invoice for something
 * the recipient never bought, carrying a "support" number for them to call in a panic.
 *
 * No single signal blocks. Each is something a real business does now and then; it is them
 * turning up together on a brand-new account that is not.
 *
 * ponytail: fixed weights and keyword list, tuned by hand from what gets held. Move them to
 * config, or learn them from release/reject decisions, if hand-tuning stops keeping up.
 *
 * @see \SolidInvoice\SaasBundle\Tests\Service\ClientEmailRiskTest
 */
final readonly class ClientEmailRisk
{
    public const int HOLD_THRESHOLD = 3;

    // An international "+" number, or a North American (813) 380-2097 / 813-380-2097. A bare run of
    // digits is not counted: bank account and registration numbers in the terms look like that.
    private const string PHONE = '/\+\s?\d(?:[\s().-]?\d){8,14}|\(?\b\d{3}\)?[\s.-]\d{3}[\s.-]\d{4}\b/';

    private const string SCAM_TERMS = '/\b(?:support|help\s?desk|helpline|customer care|toll[\s-]?free|refund|geek\s?squad|norton|mcafee|antivirus|paypal|bitcoin|crypto|iphone|24\/7)\b/i';

    private const int NEW_CLIENT_SECONDS = 3600;

    public function __construct(
        private ManagerRegistry $registry,
        private ClockInterface $clock,
    ) {
    }

    /**
     * @return array<string, int> signal => points, only the signals that fired
     */
    public function signals(Invoice | Quote $document, string $subject): array
    {
        $lines = implode("\n", $document->getLineDescriptions());
        $signals = [];

        if (1 === preg_match(self::PHONE, $subject)) {
            $signals['phone_in_subject'] = 2;
        }

        if (1 === preg_match(self::PHONE, $lines)) {
            $signals['phone_in_lines'] = 2;
        }

        if (1 === preg_match(self::PHONE, $document->getNotes() . "\n" . $document->getTerms())) {
            $signals['phone_in_notes_or_terms'] = 1;
        }

        if (1 === preg_match(self::SCAM_TERMS, $subject . "\n" . $lines)) {
            $signals['scam_terms'] = 1;
        }

        $clientCreated = $document->getClient()?->getCreated();

        if ($clientCreated instanceof DateTimeImmutable && $this->clock->now()->getTimestamp() - $clientCreated->getTimestamp() < self::NEW_CLIENT_SECONDS) {
            $signals['new_client'] = 1;
        }

        // Someone sending a real invoice wants to be paid; a callback scam wants a phone call.
        if (0 === $this->registry->getRepository(PaymentMethod::class)->count(['company' => $document->getCompany(), 'enabled' => true])) {
            $signals['no_payment_method'] = 1;
        }

        return $signals;
    }

    /**
     * @param array<string, int> $signals
     */
    public static function isRisky(array $signals): bool
    {
        return array_sum($signals) >= self::HOLD_THRESHOLD;
    }
}
