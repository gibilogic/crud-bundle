<?php

/*
 * This file is part of the GiBilogic CrudBundle package.
 *
 * (c) GiBilogic Srl <info@gibilogic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gibilogic\CrudBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Gibilogic\CrudBundle\Model\EntityRepositoryOptionsResolver;
use Gibilogic\CrudBundle\Model\PaginatedEntityRepositoryOptionsResolver;

/**
 * EntityRepository class.
 *
 * @see \Doctrine\ORM\EntityRepository
 */
class EntityRepository extends BaseRepository
{
    /**
     * Returns an entity by its ID.
     *
     * @param mixed $id
     * @param integer $hydrationMode
     * @return mixed
     *
     * @deprecated Deprecated since 2.0.4; to be removed in 3.0.0. Use the basic `find()` method instead
     */
    public function getEntity($id, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getQueryBuilder(['id' => $id])
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult($hydrationMode);
    }

    /**
     * @param array $filters
     * @param integer $hydrationMode
     * @return mixed
     */
    public function getEntityBy(array $filters = [], $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getQueryBuilder($filters)
            ->getQuery()
            ->setMaxResults(1)
            ->getOneOrNullResult($hydrationMode);
    }

    /**
     * Returns a list of entities extracted by their IDs.
     *
     * @param array $ids
     * @param integer $hydrationMode
     * @return array
     */
    public function getEntitiesById(array $ids, $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        return $this->getQueryBuilder(['id' => $ids])
            ->getQuery()
            ->execute(null, $hydrationMode);
    }

    /**
     * Returns a list of all the entities.
     *
     * @param array $options
     * @param integer $hydrationMode
     * @return array
     */
    public function getEntities(array $options = [], $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $options = EntityRepositoryOptionsResolver::createAndResolve($options);
        return $this->getQueryBuilder($options['filters'], $options['sorting'])
            ->getQuery()
            ->execute(null, $hydrationMode);
    }

    /**
     * Returns a paginated list of entities.
     *
     * @param array $options
     * @param integer $hydrationMode
     * @return \Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getPaginatedEntities(array $options = [], $hydrationMode = AbstractQuery::HYDRATE_OBJECT)
    {
        $options = PaginatedEntityRepositoryOptionsResolver::createAndResolve($options);
        $queryBuilder = $this->addPagination(
            $this->getQueryBuilder($options['filters'], $options['sorting']),
            $options['elementsPerPage'],
            $options['page']
        );

        return new Paginator($queryBuilder->getQuery()->setHydrationMode($hydrationMode));
    }

    /**
     * Returns a complete QueryBuilder instance for the current entity.
     *
     * @param array $filters
     * @param array $sorting
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder(array $filters = [], array $sorting = [])
    {
        $queryBuilder = $this->getBaseQueryBuilder();
        if (!empty($filters)) {
            $this->addFilters($queryBuilder, $filters);
        }

        if (empty($sorting)) {
            $this->addSorting($queryBuilder, $this->getDefaultSorting());
        } else {
            $this->addSorting($queryBuilder, $sorting);
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
                // This field has a custom filtering method
                $this->$methodName($queryBuilder, $value);
                continue;
            }

            if (null === $value || '' === $value || (is_array($value) && 0 == count($value))) {
                // Skip: value is blank
                continue;
            }

            $field = $this->addEntityAlias($field);
            if (is_array($value)) {
                $queryBuilder->andWhere($queryBuilder->expr()->in($field, $value));
            } else {
                $queryBuilder->andWhere($queryBuilder->expr()->eq($field, $queryBuilder->expr()->literal($value)));
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
    protected function addSorting(QueryBuilder $queryBuilder, array $sorting = [])
    {
        foreach ($sorting as $field => $sortOrder) {
            if (!$this->isFieldSortable($field)) {
                // Skip: invalid field
                continue;
            }
            if (!$this->isSortOrderValid($sortOrder)) {
                // Skip: invalid sort order
                continue;
            }

            $methodName = sprintf('add%sSorting', ucfirst($field));
            if (method_exists($this, $methodName)) {
                $this->$methodName($queryBuilder, $sortOrder);
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
     */
    protected function addPagination(QueryBuilder $queryBuilder, $elementsPerPage, $page = 1)
    {
        $elementsPerPage = (int)$elementsPerPage;
        $page = (int)$page;

        return $queryBuilder
            ->setMaxResults($elementsPerPage)
            ->setFirstResult($elementsPerPage * ($page - 1));
    }

    /**
     * Returns TRUE if the join string is already present inside the query builder, FALSE otherwise.
     *
     * @param \Doctrine\ORM\QueryBuilder $queryBuilder
     * @param string $joinString
     * @return boolean
     */
    protected function hasJoin(QueryBuilder $queryBuilder, $joinString)
    {
        /* @var \Doctrine\ORM\Query\Expr\Join $joinExpression */
        foreach ($queryBuilder->getDQLPart('join') as $joinsList) {
            foreach ($joinsList as $joinExpression) {
                if ($joinExpression->getJoin() == $joinString) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Returns a list of sortable fields.
     *
     * @return array
     */
    protected function getSortableFields()
    {
        return ['id' => true];
    }

    /**
     * Returns the default sorting for the entity.
     *
     * @return array
     */
    protected function getDefaultSorting()
    {
        return ['id' => 'asc'];
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
     * Returns TRUE if the field is sortable, FALSE otherwise.
     *
     * @param string $field
     * @return boolean
     */
    private function isFieldSortable($field)
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
        return in_array(strtolower($sortOrder), ['asc', 'desc']);
    }

    /**
     * Adds the entity alias to the field (if not already present).
     *
     * @param string $field
     * @return string
     */
    private function addEntityAlias($field)
    {
        return false !== strpos($field, '.') ? $field : sprintf('e.%s', $field);
    }
}
