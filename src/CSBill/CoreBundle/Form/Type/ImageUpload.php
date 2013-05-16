<?php

namespace CSBill\CoreBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ImageUpload extends AbstractType implements ContainerAwareInterface
{
    protected $container;

    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $session_id = $this->container->get('session')->getId();

        $view->vars['sessionId'] = $this->container->get('security.encryption')->encrypt($session_id);
    }

    public function getParent()
    {
        return 'text';
    }

    public function getName()
    {
        return 'image_upload';
    }
}
