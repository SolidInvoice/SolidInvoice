<?php
/**
 * This file is part of CSBill package.
 *
 * (c) 2013-2014 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InstallBundle\Installer\Step;

use CSBill\InstallBundle\Form\Step\LicenseAgreementForm;
use CSBill\InstallBundle\Installer\AbstractFormStep;

class LicenseAgreement extends AbstractFormStep
{
    /**
     * @return LicenseAgreementForm
     */
    public function getForm()
    {
        return new LicenseAgreementForm();
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getFormData()
    {
        $rootDir = dirname($this->get('kernel')->getRootDir());

        $licenseFile = $rootDir . DIRECTORY_SEPARATOR . 'LICENSE';

        if (!file_exists($licenseFile)) {
            throw new \Exception('LICENSE file is missing');
        }

        return array(
            'license_info' => file_get_contents($licenseFile)
        );
    }
}
