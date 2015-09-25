<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Entity
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\AbstractQuery;
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
     * @param mixed $id
     * @param integer $hydrationMode
     * @return mixed
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getEntity($id, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getQueryBuilder(array('id' => $id))->getQuery()->getOneOrNullResult($hydrationMode);
    }

    /**
     * Returns all the entities.
     *
     * @param array $options
     * @param integer $hydrationMode
     * @return array
     */
    public function getEntities($options = array(), $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $filters = isset($options['filters']) ? $options['filters'] : null;
        $sorting = isset($options['sorting']) ? $options['sorting'] : null;

        return $this->getQueryBuilder($filters, $sorting)->getQuery()->execute(null, $hydrationMode);
    }

    /**
     * Returns a paginated list of entities.
     *
     * @param array $options
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     *
     * @throws \InvalidArgumentException
     */
    public function getPaginatedEntities($options = array())
    {
        if (!isset($options['elementsPerPage'])) {
            throw new \InvalidArgumentException('You must specify the number of elements per page.', 500);
        }
        if (!isset($options['page'])) {
            throw new \InvalidArgumentException('You must specify the page number.', 500);
        }

        $filters = isset($options['filters']) ? $options['filters'] : null;
        $sorting = isset($options['sorting']) ? $options['sorting'] : null;

        return new Paginator($this->addPagination(
            $this->getQueryBuilder($filters, $sorting),
            $options['elementsPerPage'],
            $options['page']
        ));
    }

    /**
     * Returns a list of sortable fields.
     *
     * @return array
     */
    protected function getSortableFields()
    {
        return array('id' => true);
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
     * @param array $filters
     * @param array $sorting
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder($filters = array(), $sorting = array())
    {
        $queryBuilder = $this->addSorting($this->getBaseQueryBuilder(), $sorting);
        if (!empty($filters)) {
            $this->addFilters($queryBuilder, $filters);
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
     * Adds sorting to the specified query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param array $sorting
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function addSorting(QueryBuilder $queryBuilder, $sorting = array())
    {
        if (empty($sorting)) {
            $sorting = $this->getDefaultSorting();
        }

        foreach ($sorting as $field => $sortOrder) {
            if (!$this->isFieldValid($field)) {
                // Skip: invalid field
                continue;
            }
            if (!$this->isSortOrderValid($sortOrder)) {
                // Skip: invalid sort order
                continue;
            }

            $queryBuilder->addOrderBy($this->addEntityAlias($field), strtolower($sortOrder));
        }

        return $queryBuilder;
    }

    /**
     * Adds pagination to the specified query builder.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param integer $elementsPerPage
     * @param integer $page
     * @return \Doctrine\ORM\QueryBuilder
     *
     * @throws \InvalidArgumentException
     */
    protected function addPagination(QueryBuilder $queryBuilder, $elementsPerPage, $page = 1)
    {
        if (!is_numeric($elementsPerPage)) {
            throw new \InvalidArgumentException(sprintf("The number of elements per page must be an integer number, '%s' given.", gettype($elementsPerPage)), 500);
        }
        if (!is_numeric($page)) {
            throw new \InvalidArgumentException(sprintf("The page number must be an integer number, '%s' given.", gettype($page)), 500);
        }

        return $queryBuilder
            ->setMaxResults($elementsPerPage)
            ->setFirstResult($elementsPerPage * ((int)$page - 1));
    }

    /**
     * Returns TRUE if the field is sortable, FALSE otherwise.
     *
     * @param string $field
     * @return boolean
     */
    private function isFieldValid($field)
    {
        return array_key_exists($field, $this->getSortableFields());
    }

    /**
     * Returns TRUE if the sort order is valid, FALSE otherwise.
     *
     * @param string $sortOrder
     * @return boolean
     */
    private function isSortOrderValid($sortOrder)
    {
        return in_array(strtolower($sortOrder), array('asc', 'desc'));
    }

    /**
     * Adds the entity alias to the field (if not already present).
     *
     * @param string $field
     * @return string
     */
    private function addEntityAlias($field)
    {
        return strpos($field, '.') !== false ? $field : sprintf('e.%s', $field);
    }
}
