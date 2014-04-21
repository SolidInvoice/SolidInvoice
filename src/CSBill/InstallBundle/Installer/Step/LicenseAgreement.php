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
use Symfony\Component\Finder\Finder;
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
     */
    public function getFormData()
    {
        $license = '';

        $rootDir = dirname($this->get('kernel')->getRootDir());

        $finder = new Finder();
        $finder->files()->in($rootDir)->depth('== 0')->filter(function (\SplFileInfo $file) {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if ($extension !== '') {
                return false;
            }
        });

        foreach ($finder as $file) {
            if (strtolower($file->getBasename()) === 'license') {
                $license = $file->getContents();
                break;
            }
        }

        return array(
            'license_info' => $license
        );
    }
}
