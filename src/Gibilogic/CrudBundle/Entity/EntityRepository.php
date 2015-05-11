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
        return new Paginator($this->addPagination(
                $this->getQueryBuilder($options), $options['elementsPerPage'], $options['page']
        ));
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
    protected function getQueryBuilder($options = array())
    {
        $queryBuilder = $this->getBaseQueryBuilder();

        if (!empty($options['filters'])) {
            $this->addFilters($queryBuilder, $options['filters']);
        }

        if (!empty($options['sorting'])) {
            $this->addSorting($queryBuilder, $options['sorting']);
        }

        return $queryBuilder;
    }

    /**
     * Adds filters to the QueryBuilder instance.
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param array $filters
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addFilters(QueryBuilder $queryBuilder, $filters = array())
    {
        foreach ($filters as $field => $value) {
            $methodName = sprintf('add%sFilter', ucfirst($field));
            if (method_exists($this, $methodName)) {
                $this->$methodName($queryBuilder, $value);
                continue;
            }

            $field = $this->addEntityAlias($field);
            if (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($field, implode(',', $value)));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($field, $value));
            }
        }

        return $queryBuilder;
    }

    /**
     * Adds sorting to the QueryBuilder instance.
     * 
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param array $options
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addSorting(QueryBuilder $queryBuilder, $options)
    {
        $sanitizedSortField = $this->sanitizeSortField($options);
        $sanitizedSordOrder = $this->sanitizeSortOrder($options);

        if (!empty($sanitizedSortField)) {
            return $queryBuilder->orderBy(
                    $this->addEntityAlias($sanitizedSortField), $sanitizedSordOrder
            );
        }

        foreach ($this->getDefaultSorting() as $field => $sortOrder) {
            $queryBuilder->addOrderBy(
                $this->addEntityAlias($field), $sortOrder
            );
        }

        return $queryBuilder;
    }

    /**
     * Adds pagination to the QueryBuilder instance.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param integer $elementsPerPage
     * @param integer $page
     * @return \Doctrine\ORM\QueryBuilder
     * @throws \InvalidArgumentException
     */
    protected function addPagination(QueryBuilder $queryBuilder, $elementsPerPage, $page = 1)
    {
        if (!is_numeric($page)) {
            throw new \InvalidArgumentException(sprintf("The page number must be an integer number, '%s' given.", gettype($page)), 500);
        }

        if (!is_numeric($elementsPerPage)) {
            throw new \InvalidArgumentException(sprintf("The number of elements per page must be an integer number, '%s' given.", gettype($elementsPerPage)), 500);
        }

        return $queryBuilder
                ->setMaxResults($elementsPerPage)
                ->setFirstResult($elementsPerPage * ((int) $page - 1));
    }

    /**
     * Sanitizes the sorting field.
     * 
     * @param array $options
     * @return string|null
     */
    private function sanitizeSortField($options)
    {
        $field = !empty($options['field']) ? $options['field'] : null;
        if (empty($field)) {
            return null;
        }

        if (!in_array($field, $this->getSortableFields())) {
            return null;
        }

        return $field;
    }

    /**
     * Sanitizes the sorting order.
     * 
     * @param array $options
     * @return string
     */
    private function sanitizeSortOrder($options)
    {
        $order = !empty($options['order']) ? strtolower($options['order']) : null;
        if (empty($order)) {
            return 'asc';
        }

        if ($order != 'asc' && $order != 'desc') {
            return 'asc';
        }

        return $order;
    }

    /**
     * Adds the entity alias to the field (if not already present).
     *
     * @param string $field
     * @return string
     */
    private function addEntityAlias($field)
    {
        if (strpos($field, '.') !== false) {
            return $field;
        }

        return sprintf('e.%s', $field);
    }
}
