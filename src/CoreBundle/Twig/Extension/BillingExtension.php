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

namespace SolidInvoice\CoreBundle\Twig\Extension;

use Brick\Math\BigNumber;
use Override;
use SolidInvoice\CoreBundle\Entity\LineInterface;
use SolidInvoice\CoreBundle\Enum\UnitCode;
use SolidInvoice\CoreBundle\Form\FieldRenderer;
use SolidInvoice\MoneyBundle\Calculator;
use Symfony\Component\Form\FormView;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @see \SolidInvoice\CoreBundle\Tests\Twig\Extension\BillingExtensionTest
 */
class BillingExtension extends AbstractExtension
{
    public function __construct(
        private readonly FieldRenderer $fieldRenderer,
        private readonly Calculator $calculator,
        private readonly TranslatorInterface $translator
    ) {
    }

    /**
     * @return TwigFunction[]
     */
    #[Override]
    public function getFunctions(): array
    {
        return [
            new TwigFunction('billing_fields', fn (FormView $form) => $this->fieldRenderer->render($form, 'children[lines].vars[prototype]'), ['is_safe' => ['html']]),
            new TwigFunction('discount', fn ($entity): BigNumber => $this->calculator->calculateDiscount($entity)),
            new TwigFunction('quantity', $this->quantity(...)),
        ];
    }

    /**
     * A line's quantity with its unit of measure after it — "12 hours", "40 kg".
     *
     * {@see UnitCode::UNIT} renders as the bare number, which is what keeps the unit off the
     * invoices of everyone who never sets one instead of printing "2 units" on all of them.
     * Every template shows the quantity through here, so a unit needs no column of its own and
     * no template has to decide whether to suppress it.
     */
    public function quantity(LineInterface $line): string
    {
        if ($line->getUnitCode() === UnitCode::UNIT) {
            return (string) $line->getQty();
        }

        return $line->getQty() . ' ' . $line->getUnitCode()->trans($this->translator);
    }
}
