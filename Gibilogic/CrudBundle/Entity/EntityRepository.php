<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Entity
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

/**
 * EntityRepository class.
 * 
 * @see \Doctrine\ORM\EntityRepository
 */
class EntityRepository extends BaseRepository
{

    /**
     * Returns all the entities.
     *
     * @params array $options
     * @return array
     */
    public function getEntities($options = array())
    {
        return $this->getQueryBuilder($options)->getQuery()->execute();
    }

    /**
     * Returns a paginated list of entities.
     *
     * @params array $options
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getPaginatedEntities($options = array())
    {
        return new Paginator($this->addPagination($this->getQueryBuilder($options), $options['page']));
    }

    /**
     * Returns the default sorting for the entity.
     *
     * @return array
     */
    protected function getDefaultSorting()
    {
        return array('id' => 'asc');
    }

    /**
     * Returns a list of sortable fields.
     *
     * @return array
     */
    protected function getSortableFields()
    {
        return array('id');
    }

    /**
     * Returns a basic QueryBuilder for the current entity.
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getBaseQueryBuilder()
    {
        return $this->createQueryBuilder('e');
    }

    /**
     * Returns a complete QueryBuilder instance for the current entity.
     *
     * @param array $options
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder($options)
    {
        $queryBuilder = $this->getBaseQueryBuilder();

        if (!empty($options['filters']))
        {
            $this->addFilters($queryBuilder, $options['filters']);
        }
        if (!empty($options['searchText']))
        {
            $this->addSearchTextFilter($queryBuilder, $options['searchText']);
        }

        $sortField = !empty($options['sortField']) ? $options['sortField'] : null;
        $sortOrder = !empty($options['sortOrder']) ? $options['sortOrder'] : null;
        $this->addSorting($queryBuilder, $sortField, $sortOrder);

        return $queryBuilder;
    }

    /**
     * Adds filters to the QueryBuilder instance.
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param array $filters
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addFilters(QueryBuilder $queryBuilder, $filters = array())
    {
        foreach ($filters as $field => $value)
        {
            $field = $this->addEntityAlias($field);

            if (is_array($value))
            {
                $queryBuilder->andWhere($queryBuilder->expr()->in($field, implode(',', $value)));
                continue;
            }

            $queryBuilder->andWhere($queryBuilder->expr()->eq($field, $value));
        }

        return $queryBuilder;
    }

    /**
     * Adds a text search filter to the QueryBuilder instance.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $searchText
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addSearchTextFilter(QueryBuilder $queryBuilder, $searchText)
    {
        if (empty($searchText))
        {
            return $queryBuilder;
        }

        return $queryBuilder;
    }

    /**
     * Adds sorting to the QueryBuilder instance.
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $sortField
     * @param string $sortOrder
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addSorting(QueryBuilder $queryBuilder, $sortField = null, $sortOrder = null)
    {
        $sanitizedSortField = $this->sanitizeSortField($sortField);
        $sanitizedSordOrder = $this->sanitizeSortOrder($sortOrder);

        if (!empty($sanitizedSortField))
        {
            return $queryBuilder->orderBy(
                    $this->addEntityAlias($sortField), $sanitizedSordOrder
            );
        }

        foreach ($this->getDefaultSorting() as $field => $sortOrder)
        {
            $queryBuilder->addOrderBy(
                $this->addEntityAlias($field), $sanitizedSordOrder
            );
        }

        return $queryBuilder;
    }

    /**
     * Adds pagination to the QueryBuilder instance.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param mixed $page
     *
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addPagination(QueryBuilder $queryBuilder, $page = null, $elementsPerPage = 30)
    {
        if (empty($page) || !is_numeric($page) || $page < 1)
        {
            $page = 1;
        }

        return $queryBuilder
                ->setMaxResults($elementsPerPage)
                ->setFirstResult($elementsPerPage * ($page - 1));
    }

    /**
     * Sanitizes the sorting field.
     * 
     * @param string $sortField
     *
     * @return string|null
     */
    private function sanitizeSortField($sortField)
    {
        if (empty($sortField))
        {
            return null;
        }

        if (!in_array($sortField, $this->getSortableFields()))
        {
            return null;
        }

        return $sortField;
    }

    /**
     * Sanitizes the sorting order.
     * 
     * @param string $order
     *
     * @return string
     */
    private function sanitizeSortOrder($order)
    {
        if (empty($order))
        {
            return 'asc';
        }

        $order = strtolower($order);
        if ($order != 'asc' && $order != 'desc')
        {
            return 'asc';
        }

        return $order;
    }

    /**
     * Adds the entity alias to the field (if not already present).
     *
     * @param string $field
     *
     * @return string
     */
    private function addEntityAlias($field)
    {
        if (strpos($field, '.') !== false)
        {
            return $field;
        }

        return sprintf('e.%s', $field);
    }
}
