<?php
/**
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2016 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\DataGridBundle\Filter;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\Request;

class SearchFilter implements FilterInterface
{
    /**
     * @var array
     */
    private $searchFields;

    /**
     * SearchFilter constructor.
     *
     * @param array $searchFields
     */
    public function __construct(array $searchFields)
    {
	$this->searchFields = $searchFields;
    }

    /**
     * {@inheritdoc}
     */
    public function filter(Request $request, QueryBuilder $queryBuilder)
    {
	if ($request->query->has('q')) {
	    $alias = $queryBuilder->getRootAliases()[0];

	    $expr = $queryBuilder->expr();

	    $fields = array_map(
		function ($field) use ($alias) {
		    if (false !== strpos($field, '.')) {
			list($alias, $field) = explode('.', $field);
		    }

		    return sprintf('%s.%s LIKE :q', $alias, $field);
		},
		$this->searchFields
	    );

	    $queryBuilder->orWhere(call_user_func_array([$expr, 'orX'], $fields));
	    $queryBuilder->setParameter('q', '%'.$request->query->get('q').'%');
	}
    }
}
