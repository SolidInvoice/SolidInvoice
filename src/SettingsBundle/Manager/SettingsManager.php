<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\SettingsBundle\Manager;

use CSBill\SettingsBundle\Collection\ConfigCollection;
use CSBill\SettingsBundle\Exception\InvalidSettingException;
use CSBill\SettingsBundle\Loader\SettingsLoaderInterface;
use CSBill\SettingsBundle\Model\Setting;
use Symfony\Component\PropertyAccess\PropertyAccess;

/**
 * Class SettingsManager.
 */
class SettingsManager implements ManagerInterface
{
    /**
     * @var \Symfony\Component\PropertyAccess\PropertyAccessorInterface
     */
    protected $accessor;

    /**
     * @var array
     */
    protected $settings;

    /**
     * @var array
     */
    protected $sections;

    /**
     * @var ConfigCollection
     */
    protected $collection;

    /**
     * @var bool
     */
    protected $initialized;

    /**
     * @var array
     */
    protected $loaders = [];

    const LEFT_TOKEN = '[';
    const RIGHT_TOKEN = ']';

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->initialized = false;
        $this->settings = [];
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * Initializes the loaders and load the default settings.
     */
    protected function initialize()
    {
        if (false === $this->initialized) {
            $this->collection = new ConfigCollection();

            foreach ($this->loaders as $loader) {
                /* @var SettingsLoaderInterface $loader */
                $this->collection->startSection(get_class($loader));

                $settings = $loader->getSettings();

                $this->collection->add($settings);

                $this->settings = array_merge_recursive($this->settings, $settings);

                $this->collection->endSection();
            }

            $this->initialized = true;
        }
    }

    /**
     * @param SettingsLoaderInterface $loader
     *
     * @return SettingsLoaderInterface|void
     */
    public function addSettingsLoader(SettingsLoaderInterface $loader)
    {
        $this->loaders[get_class($loader)] = $loader;
    }

    /**
     * @param string|null $setting
     *
     * @return mixed|string
     *
     * @throws \CSBill\SettingsBundle\Exception\InvalidSettingException
     */
    public function get(string $setting = null)
    {
        $this->initialize();

        if (empty($setting)) {
            return $this->getSettings();
        }

        if (false !== strpos($setting, '.')) {
            $split = array_filter(explode('.', $setting));

            if (!count($split) > 1) {
                throw new InvalidSettingException($setting);
            }

            unset($setting);

            $setting = '';

            foreach ($split as $value) {
                if (0 !== strpos($value, self::LEFT_TOKEN)) {
                    $setting .= self::LEFT_TOKEN;
                }

                $setting .= $value;

                if (strrpos($value, self::RIGHT_TOKEN) !== strlen($value) - 1) {
                    $setting .= self::RIGHT_TOKEN;
                }
            }
        }

        if (0 !== strpos($setting, self::LEFT_TOKEN)) {
            $setting = self::LEFT_TOKEN.$setting;
        }

        if (strrpos($setting, self::RIGHT_TOKEN) !== strlen($setting) - 1) {
            $setting .= self::RIGHT_TOKEN;
        }

        $entity = $this->accessor->getValue($this->settings, $setting);

        if ($entity instanceof Setting) {
            return $entity->getValue();
        } else {
            return $entity;
        }
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        $this->initialize();

        return $this->settings;
    }

    /**
     * Recursively set settings from an array.
     *
     * @param array $settings
     *
     * @return mixed|void
     */
    public function set(array $settings = [])
    {
        $this->initialize();

        if (!empty($settings)) {
            foreach ($this->collection->getSections() as $collectionSection) {
                $this->collection->startSection($collectionSection);

                $collectionSettings = [];

                foreach ($settings as $key => $value) {
                    $config = $this->collection->getSettings();

                    if (isset($config[$key])) {
                        $collectionSettings[$key] = $this->setData($config[$key], $value);
                    }
                }

                /** @var SettingsLoaderInterface $loader */
                $loader = $this->loaders[$collectionSection];
                $loader->saveSettings($collectionSettings);

                $this->collection->endSection();
            }
        }
    }

    /**
     * @param array $config
     * @param array $settings
     *
     * @return array
     */
    protected function setData(array $config, array $settings): array
    {
        $settingsArray = [];

        foreach ($config as $section => $setting) {
            foreach ($settings as $key => $value) {
                if (is_array($value) && is_array($setting)) {
                    $settingsArray[$key] = $this->setData($setting, $value);
                } else {
                    if ($section === $key) {
                        /* @var \CSBill\SettingsBundle\Model\Setting $setting */
                        $setting->setValue($value);
                        $settingsArray[$key] = $setting;
                    }
                }
            }
        }

        return $settingsArray;
    }
}
