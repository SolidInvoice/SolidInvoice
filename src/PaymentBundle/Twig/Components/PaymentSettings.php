<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\PaymentBundle\Twig\Components;

use Doctrine\ORM\EntityManagerInterface;
use Exception;
use SolidInvoice\CoreBundle\Response\FlashResponse;
use SolidInvoice\PaymentBundle\Entity\PaymentMethod;
use SolidInvoice\PaymentBundle\Factory\PaymentFactories;
use SolidInvoice\PaymentBundle\Form\Type\PaymentMethodType;
use SolidInvoice\PaymentBundle\Repository\PaymentMethodRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolsTrait;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use function str_replace;
use function ucwords;

#[AsLiveComponent(name: 'PaymentSettings')]
final class PaymentSettings extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;
    use ComponentToolsTrait;

    public function __construct(
        private readonly PaymentFactories $factories,
        private readonly PaymentMethodRepository $repository
    ) {
    }

    #[LiveProp(writable: true, updateFromParent: true, url: true)]
    public string $method = '';

    /**
     * @throws Exception
     */
    protected function instantiateForm(): FormInterface
    {
        $paymentMethod = $this->repository->findOneBy(['gatewayName' => $this->method]);

        if (! $paymentMethod instanceof PaymentMethod) {
            $paymentMethod = new PaymentMethod();
            $paymentMethod->setGatewayName($this->method);
            $paymentMethod->setFactoryName($this->factories->getFactory($this->method));
            $paymentMethod->setName(ucwords(str_replace('_', ' ', $this->method)));
        }

        return $this->createForm(
            PaymentMethodType::class,
            $paymentMethod,
            [
                'config' => $this->factories->getForm($this->method),
                'internal' => $this->factories->isOffline($this->method),
            ]
        );
    }

    #[LiveAction]
    public function save(EntityManagerInterface $entityManager, RequestStack $requestStack): void
    {
        $this->submitForm();

        /** @var PaymentMethod $paymentMethod */
        $paymentMethod = $this->getForm()->getData();
        $entityManager->persist($paymentMethod);
        $entityManager->flush();

        $session = $requestStack->getSession();
        assert($session instanceof Session);

        $session->getFlashBag()->add(FlashResponse::FLASH_SUCCESS, 'payment.method.updated');

        $this->emitUp('paymentMethodUpdated');
    }
}
