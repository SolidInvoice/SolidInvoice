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

namespace SolidInvoice\CoreBundle\Traits\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use LogicException;
use Money\Currency;
use SolidInvoice\ClientBundle\Entity\Client;
use Symfony\Component\Validator\Constraints as Assert;
use function sprintf;

/**
 * Backs {@see \SolidInvoice\CoreBundle\Contracts\HasFrozenCurrencyInterface}. The using
 * class must supply {@see self::getClient()} (both {@see \SolidInvoice\InvoiceBundle\Entity\BaseInvoice}
 * and {@see \SolidInvoice\QuoteBundle\Entity\Quote} already declare it).
 */
trait HasFrozenCurrency
{
    /**
     * The currency the document was issued in, frozen the moment it leaves Draft/New.
     * Null on a draft, or on a row written before this column existed — both fall back
     * to {@see self::getClient()}'s current currency in {@see self::getCurrency()}.
     */
    #[ORM\Column(name: 'currency_code', type: Types::STRING, length: 3, nullable: true)]
    #[Assert\Currency]
    protected ?string $currencyCode = null;

    abstract public function getClient(): ?Client;

    public function getCurrencyCode(): ?string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(?string $currencyCode): self
    {
        $this->currencyCode = $currencyCode;

        return $this;
    }

    /**
     * The frozen currency once the document has left Draft/New, otherwise the client's
     * current currency. Templates must read this instead of `client.currency` directly,
     * so an issued document keeps stating the amount it stated when it was issued.
     */
    public function getCurrency(): Currency
    {
        if ($this->currencyCode !== null) {
            return new Currency($this->currencyCode);
        }

        $client = $this->getClient();

        if (! $client instanceof Client) {
            throw new LogicException(sprintf('%s has no client to resolve a currency from.', static::class));
        }

        return $client->getCurrency();
    }
}
