<?php

/*
 * This file is part of the GiBilogic CrudBundle package.
 *
 * (c) GiBilogic Srl <info@gibilogic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gibilogic\CrudBundle\Model;

/**
 * Simple OptionsResolver for the paginated entity repository options
 * (filtering and sorting).
 *
 * @author Matteo Guindani https://github.com/Ingannatore
 */
class PaginatedEntityRepositoryOptionsResolver extends SimpleOptionsResolver
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        return $this
            ->setDefaults([
                'filters' => [],
                'sorting' => [],
                'page' => 1,
            ])
            ->setRequired([
                'elementsPerPage',
            ])
            ->setAllowedTypes('elementsPerPage', 'numeric')
            ->setAllowedTypes('filters', 'array')
            ->setAllowedTypes('sorting', 'array')
            ->setAllowedTypes('page', 'numeric');
    }
}
