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
use SolidInvoice\InvoiceBundle\Entity\BaseInvoice;
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
 */
final class UserToContactTransformer implements DataTransformerInterface
{
    /**
     * @var Quote|BaseInvoice|object
     */
    private object $entity;

    /**
     * @var class-string<T>
     */
    private string $class;

    /**
     * @param Quote|BaseInvoice|object $entity
     * @param class-string<T> $class
     */
    public function __construct(object $entity, string $class)
    {
        $this->entity = $entity;
        $this->class = $class;
    }

    public function transform($value)
    {
        return $value;
    }

    public function reverseTransform($value)
    {
        if (is_iterable($value)) {
            /** @var Collection<int, Contact> $value */
            $users = [];

            foreach ($value as $item) {
                $contact = new ($this->class)();
                $contact->setContact($item);

                switch (true) {
                    case $this->entity instanceof Invoice:
                        $contact->setInvoice($this->entity);
                        break;
                    case $this->entity instanceof RecurringInvoice:
                        $contact->setRecurringInvoice($this->entity);
                        break;
                    case $this->entity instanceof Quote:
                        $contact->setQuote($this->entity);
                        break;
                }

                $users[] = $contact;
            }

            return $users;
        }

        return $value;
    }
}
