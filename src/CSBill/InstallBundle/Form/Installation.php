<?php

namespace CSBill\InstallBundle\Form;

use Craue\FormFlowBundle\Form\FormFlow;
use Craue\FormFlowBundle\Form\FormFlowInterface;

use CSBill\InstallBundle\Form\Step\LicenseAgreementForm;
use Symfony\Component\Form\FormInterface;

class Installation extends FormFlow
{
    public function getName() {
        return 'installation';
    }

    public function saveCurrentStepData(FormInterface $form) {
        $stepData = $this->retrieveStepData();

        var_dump($stepData, $this->getRequest()->request->get($form->getName(), array()));
        exit;

        $stepData[$this->getCurrentStepNumber()] = $this->getRequest()->request->get($form->getName(), array());

        $this->saveStepData($stepData);
    }

    protected function loadStepsConfig() {
        return array(
            array(
                'label' => 'license_agreement',
                'type' => new LicenseAgreementForm(),
            ),
            /*array(
                'label' => 'system_check',
                'type' => new CreateVehicleStep2Form(),
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                        return $estimatedCurrentStepNumber > 1 && !$flow->getFormData()->canHaveEngine();
                    },
            ),
            array(
                'label' => 'database',
                'type' => new CreateVehicleStep2Form(),
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                        return $estimatedCurrentStepNumber > 1 && !$flow->getFormData()->canHaveEngine();
                    },
            ),
            array(
                'label' => 'system_information',
                'type' => new CreateVehicleStep2Form(),
                'skip' => function($estimatedCurrentStepNumber, FormFlowInterface $flow) {
                        return $estimatedCurrentStepNumber > 1 && !$flow->getFormData()->canHaveEngine();
                    },
            ),*/
            array(
                'label' => 'confirmation',
            ),
        );
    }
} 