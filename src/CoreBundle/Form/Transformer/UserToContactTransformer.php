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

namespace SolidInvoice\CoreBundle\Form\Transformer;

use Doctrine\Common\Collections\Collection;
use SolidInvoice\ClientBundle\Entity\Contact;
use SolidInvoice\InvoiceBundle\Entity\Invoice;
use SolidInvoice\InvoiceBundle\Entity\InvoiceContact;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoice;
use SolidInvoice\InvoiceBundle\Entity\RecurringInvoiceContact;
use SolidInvoice\QuoteBundle\Entity\Quote;
use SolidInvoice\QuoteBundle\Entity\QuoteContact;
use Symfony\Component\Form\DataTransformerInterface;
use function is_iterable;

/**
 * @template T of InvoiceContact|QuoteContact|RecurringInvoiceContact
 * @implements DataTransformerInterface<array<int, T>, array<int, T>>
 * @see \SolidInvoice\CoreBundle\Tests\Form\Transformer\UserToContactTransformerTest
 */
final class UserToContactTransformer implements DataTransformerInterface
{
    /**
     * @param class-string<T> $class
     */
    public function __construct(
        private readonly object $entity,
        private readonly string $class
    ) {
    }

    /**
     * @param mixed $value
     *
     * @return mixed
     */
    public function transform($value)
    {
        return $value;
    }

    /**
     * @param mixed $value
     * @return array<int, T>|mixed
     */
    public function reverseTransform($value)
    {
        if (is_iterable($value)) {
            /** @var Collection<int, Contact> $value */
            $users = [];

            foreach ($value as $item) {
                $class = $this->class;
                $contact = new $class();
                $contact->setContact($item);
                if ($this->entity instanceof Invoice) {
                    $contact->setInvoice($this->entity);
                }
                if ($this->entity instanceof RecurringInvoice) {
                    $contact->setRecurringInvoice($this->entity);
                }
                if ($this->entity instanceof Quote) {
                    $contact->setQuote($this->entity);
                }

                $users[] = $contact;
            }

            return $users;
        }

        return $value;
    }
}
