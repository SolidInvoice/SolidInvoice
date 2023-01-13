<?php
declare(strict_types=1);

namespace SolidInvoice\CoreBundle\Form\Type;

use SolidInvoice\MoneyBundle\Form\Type\CurrencyType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

final class CompanyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name')
            ->add('currency', CurrencyType::class);
    }
}
