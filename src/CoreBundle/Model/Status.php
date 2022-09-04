<?php

declare(strict_types=1);

/*
 * This file is part of SolidInvoice project.
 *
 * (c) Pierre du Plessis <open-source@solidworx.co>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace SolidInvoice\CoreBundle\Model;

/**
 * This class converts a status into a label for use with twitter bootstrap.
 *
 * E.G it will convert 'active' into 'success', so it can be used with then class 'badge-success'
 *
 * @author Pierre du Plessis
 */
abstract class Status
{
    /**
     * Contains a list of all the statuses and their corresponding labels.
     *
     * @var array
     */
    protected $statusLabels = [];

    /**
     * Converts a status into a label.
     */
    public function getStatusLabel(string $status): string
    {
        if (isset($this->statusLabels[$status])) {
            return $this->statusLabels[$status];
        }

        return 'inverse';
    }

    /**
     * Returns the HTML to display the status.
     */
    public function getHtml(string $status): string
    {
        $status = str_replace(['-', '_'], ' ', strtolower($status));

        return '<label class="badge badge-' . $this->getStatusLabel($status) . '">' . ucwords($status) . '</label>';
    }

    /**
     * Return an array of all the available statuses.
     */
    public function getStatusList(): array
    {
        return array_keys($this->statusLabels);
    }
}
