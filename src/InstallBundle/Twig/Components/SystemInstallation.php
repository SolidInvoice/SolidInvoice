<?php

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\InstallBundle\Twig\Components;

use SolidInvoice\AppRequirements;
use SolidInvoice\InstallBundle\DTO\Installation;
use SolidInvoice\InstallBundle\Form\Type\InstallationType;
use SolidInvoice\InstallBundle\Step\InstallationStepInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Form\Flow\FormFlowInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use function dirname;
use function get_current_user;
use function getcwd;
use function ini_get;
use function str_replace;

#[AsLiveComponent]
class SystemInstallation extends AbstractController
{
    use ComponentWithFormTrait;
    use DefaultActionTrait;

    /**
     * @param ServiceLocator<InstallationStepInterface> $steps
     */
    public function __construct(
        #[AutowireLocator(services: InstallationStepInterface::DI_TAG, defaultIndexMethod: 'getLabel', defaultPriorityMethod: 'priority')]
        private readonly ServiceLocator $steps,
        #[Autowire(env: 'SOLIDINVOICE_CONFIG_DIR')]
        private readonly string $configDir,
        #[Autowire(param: 'kernel.cache_dir')]
        private readonly string $cacheDir,
        #[Autowire(param: 'kernel.logs_dir')]
        private readonly string $logsDir,
        private readonly AppRequirements $requirements,
    ) {
    }

    protected function instantiateForm(): FormInterface
    {
        $form = $this->createForm(InstallationType::class, new Installation());
        assert($form instanceof FormFlowInterface);
        return $form->getStepForm();
    }

    public function getAppRequirements(): AppRequirements
    {
        return $this->requirements;
    }

    /**
     * @return ServiceLocator<InstallationStepInterface>
     */
    public function getInstallationSteps(): ServiceLocator
    {
        return $this->steps;
    }

    public function iniGet(string $name): false|string
    {
        return ini_get($name);
    }

    public function getCurrentUser(): string
    {
        return get_current_user();
    }

    public function getConfigDir(): string
    {
        return str_replace(dirname(getcwd()), '.', $this->configDir);
    }

    public function getCacheDir(): string
    {
        return str_replace(dirname(getcwd()), '.', $this->cacheDir);
    }

    public function getLogsDir(): string
    {
        return str_replace(dirname(getcwd()), '.', $this->logsDir);
    }
}
