<?php

namespace CSBill\CoreBundle\Manager;

use Symfony\Component\PropertyAccess\PropertyAccess;
use Doctrine\Common\Persistence\ManagerRegistry;

class SettingsManager {

    protected $accessor;

    /**
     * Constructor
     *
     * @param EntityManager $em
     */
    public function __construct(ManagerRegistry $doctrine)
    {
        $em = $doctrine->getManager();

        $this->settings = $em->getRepository('CSBillCoreBundle:Setting')->getAllSettings();

        $this->accessor = PropertyAccess::getPropertyAccessor();
    }

    /**
     * Returns a setting value
     *
     * @param string $setting
     * @throws \Exception
     * @return mixed
     */
    public function get($setting)
    {
        if(strpos($setting, '.') !== false) {
            $split = array_filter(explode('.', $setting));

            if(!count($split) > 1) {
                throw new \Exception(sprintf('Invalid settings option: %s', $setting));
            }

            unset($setting);

            $setting = '';

            foreach($split as $value) {

                if(strpos($value, '[') !== 0) {
                    $setting .= '[';
                }

                $setting .= $value;

                if(strrpos($value, ']') !== strlen($value) - 1) {
                    $setting .= ']';
                }
            }
        }

        if(strpos($setting, '[') !== 0) {
            $setting = '[' . $setting;
        }

        if(strrpos($setting, ']') !== strlen($setting) - 1) {
             $setting .= ']';
        }

        return $this->accessor->getValue($this->settings, $setting);
    }
}