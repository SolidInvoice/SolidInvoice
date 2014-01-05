<?php
/*
 * This file is part of the CSBill package.
 *
 * (c) Pierre du Plessis <info@customscripts.co.za>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace CSBill\InstallBundle\Installer\Step;

use Symfony\Component\Finder\Finder;

use CSBill\InstallBundle\Installer\Step;

class LicenseAgreement extends Step
{
    /**
     * The view to render for this installation step
     *
     * @var string $view;
     */
    public $view = 'CSBillInstallBundle:Install:license_agreement.html.twig';

    /**
     * The title to display when this installation step is active
     *
     * @var string $title
     */
    public $title = 'License Agreement';

    /**
     * The license agreement text
     *
     * @var string $license
     */
    public $license;

    /**
     * Validates that the user accepted the license agreement
     *
     * @param  array   $request
     * @return boolean
     */
    public function validate(array $request)
    {
        if (isset($request['accept']) && $request['accept'] === 'y') {
            return true;
        }

        $this->addError('Please accept the license agreement before installing the application');

        return false;
    }

    /**
     * Not implemented
     *
     * @param array $request
     */
    public function process(array $request)
    {

    }

    /**
     * Reads through all the files in the root directory to find the license file so it can be shown to the user
     *
     * @return void
     */
    public function start()
    {
        $root_dir = dirname($this->get('kernel')->getRootDir());

        $finder = new Finder();
        $finder->files()->in($root_dir)->depth('== 0')->filter(function (\SplFileInfo $file) {
            $extension = pathinfo($file->getFilename(), PATHINFO_EXTENSION);

            if ($extension !== '') {
                return false;
            }
        });

        foreach ($finder as $file) {
            if (strtolower($file->getBasename()) === 'license') {
                $this->license = $file->getContents();
                break;
            }
        }
    }
}
