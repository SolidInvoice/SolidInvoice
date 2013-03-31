<?php

namespace CSBill\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\MoneyType as BaseType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\FormInterface;

class Money extends BaseType {

    protected $currency;

    public function __construct($currency)
    {
        $this->currency = $currency;
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        parent::setDefaultOptions($resolver);
        $resolver->setDefaults(array(
                'currency'  => $this->currency->getCurrency()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        list($class, $pattern) = $this->getMoneyPattern($options['currency']);
        $view->vars['money_pattern'] = $pattern;
        $view->vars['money_addon_class'] = $class;
    }

    /**
     * Returns the pattern for this locale
     *
     * The pattern contains the placeholder "{{ widget }}" where the HTML tag should
     * be inserted
     */
    protected function getMoneyPattern($currency)
    {
        if (!$currency) {
            return '{{ widget }}';
        }

        $locale = $this->currency->getLocale();

        if (!isset(self::$patterns[$locale])) {
            self::$patterns[$locale] = array();
        }

        $addonClass = "";

        if (!isset(self::$patterns[$locale][$currency])) {
            $pattern = $this->currency->format(123);

            // the spacings between currency symbol and number are ignored, because
            // a single space leads to better readability in combination with input
            // fields

            // the regex also considers non-break spaces (0xC2 or 0xA0 in UTF-8)

            preg_match('/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u', $pattern, $matches);

            if (!empty($matches[1])) {
                self::$patterns[$locale][$currency]['pattern'] = '<div class="add-on">'.$matches[1].'</div> {{ widget }}';
                self::$patterns[$locale][$currency]['class'] = "prepend";
            } elseif (!empty($matches[2])) {
                self::$patterns[$locale][$currency]['pattern'] = '{{ widget }} <div class="add-on">'.$matches[2].'</div>';
                self::$patterns[$locale][$currency]['class'] = "append";
            } else {
                self::$patterns[$locale][$currency]['pattern'] = '{{ widget }}';
                self::$patterns[$locale][$currency]['class'] = "";
            }
        }

        return array(self::$patterns[$locale][$currency]['class'], self::$patterns[$locale][$currency]['pattern']);
    }
}