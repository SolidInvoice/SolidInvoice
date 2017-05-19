<?php

declare(strict_types=1);

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2017 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\CoreBundle\Behat;

use Behat\Mink\Element\NodeElement;

class CoreContext extends DefaultContext
{
    /**
     * @When /^(?:|I )fill in select2 input "(?P<field>(?:[^"]|\\")*)" with "(?P<value>(?:[^"]|\\")*)"$/
     */
    public function iFillInSelectInputWithAndSelect($field, $value)
    {
        $page = $this->getSession()->getPage();

        $element = $page->findField($field);

        $select2 = $element->getParent()->find('css', '.select2-container');

        if (!$select2) {
            throw new \Exception(sprintf('Field "%s" not found', $field));
        }

        $select2->press();

        /* @var NodeElement[] $chosenResults */
        $chosenResults = $page->findAll('css', '.select2-results li');

        foreach ($chosenResults as $result) {
            if ($result->getText() == $value) {
                $result->click();
                break;
            }
        }
    }
}
