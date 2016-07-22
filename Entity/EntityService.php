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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\AbstractQuery;

/**
 * EntityService class.
 */
abstract class EntityService
{
    /**
     * @var \Doctrine\ORM\EntityManager $entityManager
     */
    protected $entityManager;

    /**
     * @var integer $elementsPerPage
     */
    protected $elementsPerPage;

    /**
     * Returns the Symfony-styled entity name.
     *
     * @return string
     */
    abstract public function getEntityName();

    /**
     * Returns the entity's session prefix.
     *
     * @return string
     */
    abstract public function getEntityPrefix();

    /**
     * Returns a new instance of the managed entity.
     *
     * @return Object
     */
    abstract public function getNewEntity();

    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManager $entityManager
     * @param integer $elementsPerPage
     */
    public function __construct(EntityManager $entityManager, $elementsPerPage)
    {
        $this->entityManager = $entityManager;
        $this->elementsPerPage = $elementsPerPage;
    }

    /**
     * Returns an instance of the entity.
     *
     * @param integer $id
     * @return mixed
     */
    public function findEntity($id)
    {
        return $this->getRepository()->getEntity($id);
    }

    /**
     * Returns an unhydrated (ie. as associative array) entity.
     *
     * @param integer $id
     * @return mixed
     */
    public function findUnhydratedEntity($id)
    {
        return $this->getRepository()->getEntity($id, AbstractQuery::HYDRATE_ARRAY);
    }

    /**
     * Returns a list of entities.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $filters
     * @param bool $addPagination
     * @return array
     */
    public function findEntities(Request $request, $filters = array(), $addPagination = false)
    {
        $options = $this->getOptions($request, $filters, $addPagination);
        if (!$addPagination) {
            return array(
                'entities' => $this->getRepository()->getEntities($options),
                'options' => $options
            );
        }

        $entities = $this->getRepository()->getPaginatedEntities($options);
        $options['elements'] = $entities->count();
        $options['pages'] = (int)ceil($options['elements'] / $options['elementsPerPage']);

        return array(
            'entities' => $entities,
            'options' => $options
        );
    }

    /**
     * Returns an unhydrated (ie. as associative array) list of entities.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $filters
     * @param bool $addPagination
     * @return array
     */
    public function findUnhydratedEntities(Request $request, $filters = array(), $addPagination = false)
    {
        $options = $this->getOptions($request, $filters, $addPagination, true);
        if (!$addPagination) {
            return array(
                'entities' => $this->getRepository()->getEntities($options, AbstractQuery::HYDRATE_ARRAY),
                'options' => $options
            );
        }

        $entities = $this->getRepository()->getPaginatedEntities($options, AbstractQuery::HYDRATE_ARRAY);
        $options['elements'] = $entities->count();
        $options['pages'] = (int)ceil($options['elements'] / $options['elementsPerPage']);

        return array(
            'entities' => (array)$entities->getIterator(),
            'options' => $options
        );
    }

    /**
     * Saves the entity.
     *
     * @param object $entity
     * @return bool
     */
    public function saveEntity($entity)
    {
        if (!$this->entityManager->contains($entity)) {
            $this->entityManager->persist($entity);
        }

        $this->entityManager->flush();
        return true;
    }

    /**
     * Removes the entity.
     *
     * @param object $entity
     * @return bool
     */
    public function removeEntity($entity)
    {
        $this->entityManager->remove($entity);
        $this->entityManager->flush();
        return true;
    }

    /**
     * Removes a list of entities.
     *
     * @param array $ids
     * @return bool
     */
    public function removeEntities(array $ids)
    {

        foreach ($this->getRepository()->getEntitiesById($ids) as $entity) {
            $this->entityManager->remove($entity);
        }

        $this->entityManager->flush();
        return true;
    }

    /**
     * Returns the current filters for the entity, if any.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param array $overrideFilters
     * @param bool $ignoreSession
     * @return array
     */
    public function getFilters(Request $request, $overrideFilters = array(), $ignoreSession = false)
    {
        if ($ignoreSession) {
            return array_replace(
                $this->getFiltersFromRequest($request),
                $overrideFilters
            );
        }

        $filters = array_replace(
            $this->getFiltersFromSession($request->getSession()),
            $this->getFiltersFromRequest($request),
            $overrideFilters
        );

        $this->saveFilters($request->getSession(), $filters);
        return $filters;
    }

    /**
     * Returns the current sorting options for the entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param bool $ignoreSession
     * @return array
     */
    public function getSorting(Request $request, $ignoreSession = false)
    {
        if ($ignoreSession) {
            return $this->getSortingFromRequest($request);
        }

        $sorting = array_replace(
            $this->getSortingFromSession($request->getSession()),
            $this->getSortingFromRequest($request)
        );

        $this->saveSorting($request->getSession(), $sorting);
        return $sorting;
    }

    /**
     * Returns TRUE if the filter exists for this entity, FALSE otherwise.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $filterName
     * @return boolean
     */
    public function hasFilter(SessionInterface $session, $filterName)
    {
        return $session->has($this->getFilterPrefix() . $filterName);
    }

    /**
     * Adds a single filter to the session; set the `overwrite` flag to `false`
     * if you want to preserve an already existing value.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $filterName
     * @param mixed $filterValue
     * @param boolean $overwrite
     */
    public function addFilter(SessionInterface $session, $filterName, $filterValue, $overwrite = true)
    {
        if (!$overwrite && $this->hasFilter($session, $filterName)) {
            return;
        }

        $session->set($this->getFilterPrefix() . $filterName, $filterValue);
    }

    /**
     * Removes filters and sorting options from the session for this entity.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    public function removeFiltersAndSorting(SessionInterface $session)
    {
        $this->removeFiltersFromSession($session);
        $this->removeSortingFromSession($session);
    }

    /**
     * Saves the filters inside the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param array $filters
     */
    protected function saveFilters(SessionInterface $session, array $filters)
    {
        $this->saveValues($filters, $this->getFilterPrefix(), $session);
    }

    /**
     * Saves the sorting options (sort field and sort order) inside the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param array $sorting
     */
    protected function saveSorting(SessionInterface $session, array $sorting)
    {
        $this->saveValues($sorting, $this->getSortingPrefix(), $session);
    }

    /**
     * Removes all the filters from the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    protected function removeFiltersFromSession(SessionInterface $session)
    {
        $this->removeValues($this->getFilterPrefix(), $session);
    }

    /**
     * Removes the sorting options from the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    protected function removeSortingFromSession(SessionInterface $session)
    {
        $this->removeValues($this->getSortingPrefix(), $session);
    }

    /**
     * Returns the filters from the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    protected function getFiltersFromRequest(Request $request)
    {
        return $this->extractValues($this->getValuesFromRequest($request), $this->getFilterPrefix());
    }

    /**
     * Returns the filters from the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @return array
     */
    protected function getFiltersFromSession(SessionInterface $session)
    {
        return $this->extractValues($session->all(), $this->getFilterPrefix());
    }

    /**
     * Returns the sorting options from the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    protected function getSortingFromRequest(Request $request)
    {
        return $this->extractValues($this->getValuesFromRequest($request), $this->getSortingPrefix());
    }

    /**
     * Returns the sorting options from the session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @return array
     */
    protected function getSortingFromSession(SessionInterface $session)
    {
        return $this->extractValues($session->all(), $this->getSortingPrefix());
    }

    /**
     * Returns the entity repository.
     *
     * @return \Gibilogic\CrudBundle\Entity\EntityRepository
     */
    protected function getRepository()
    {
        return $this->entityManager->getRepository($this->getEntityName());
    }

    /**
     * @param Request $request
     * @param array $filters
     * @param bool $addPagination
     * @param bool $ignoreSession
     * @return array
     */
    private function getOptions(Request $request, $filters, $addPagination, $ignoreSession = false)
    {
        $options = array(
            'filters' => $this->getFilters($request, $filters, $ignoreSession),
            'sorting' => $this->getSorting($request, $ignoreSession)
        );

        if ($addPagination) {
            $options['page'] = $this->getPage($request);
            $options['elementsPerPage'] = $this->elementsPerPage;
        }

        return $options;
    }

    /**
     * Returns the values of the options from the request.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    private function getValuesFromRequest(Request $request)
    {
        $values = array_merge($request->query->all(), $request->request->all());
        $contentValues = json_decode($request->getContent(), true);
        if (is_array($contentValues)) {
            $values = array_merge($values, $contentValues);
        }

        return $values;
    }

    /**
     * Returns all the key-value pairs from the array whose key has the specified prefix.
     *
     * @param array $values
     * @param string $prefix
     * @param bool $removePrefixFromKeys
     * @return array
     */
    private function extractValues(array $values, $prefix, $removePrefixFromKeys = true)
    {
        if (empty($values)) {
            return array();
        }

        $validKeys = array_filter(array_keys($values), function($name) use ($prefix) {
            return (0 === strpos($name, $prefix));
        });

        $results = array_intersect_key($values, array_flip($validKeys));
        if (!$removePrefixFromKeys) {
            return $results;
        }

        return array_combine(
            array_map(function($key) use ($prefix) {
                return str_replace($prefix, '', $key);
            }, array_keys($results)), $results
        );
    }

    /**
     * Saves into the session all the key-value pairs from the array, adding to their keys the specified prefix.
     *
     * @param array $values
     * @param string $prefix
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    private function saveValues(array $values, $prefix, SessionInterface $session)
    {
        foreach ($values as $name => $value) {
            $session->set($prefix . $name, $value);
        }
    }

    /**
     * Removes from the session all the key-value pairs whose key has the specified prefix.
     *
     * @param string $prefix
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     */
    private function removeValues($prefix, SessionInterface $session)
    {
        foreach ($session->all() as $key => $value) {
            if (0 === strpos($key, $prefix)) {
                $session->remove($key);
            }
        }
    }

    /**
     * Returns the current page number.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return integer
     */
    private function getPage(Request $request)
    {
        $page = $request->query->get('page', 1);
        if (!is_numeric($page) || $page < 1) {
            $page = 1;
        }

        return (int)$page;
    }

    /**
     * Returns the prefix used for managing the filters.
     *
     * @return string
     */
    private function getFilterPrefix()
    {
        return sprintf('%s_filter_', $this->getEntityPrefix());
    }

    /**
     * Returns the prefix used for managing the sorting options.
     *
     * @return string
     */
    private function getSortingPrefix()
    {
        return sprintf('%s_sorting_', $this->getEntityPrefix());
    }
}
