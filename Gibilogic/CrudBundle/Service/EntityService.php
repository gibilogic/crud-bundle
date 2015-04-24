<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Service
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Service;

use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;

/**
 * EntityService class.
 * 
 * @abstract
 */
abstract class EntityService
{

    /**
     * @var \Doctrine\ORM\EntityManager $em
     */
    private $em;

    /**
     * @var Gibilogic\CrudBundle\Entity\EntityRepository $repo
     */
    private $repo;

    /**
     * Constructor.
     *
     * @param \Doctrine\ORM\EntityManager $em
     */
    public function __construct(EntityManager $em)
    {
        $this->em = $em;
        $this->repo = $this->em->getRepository($this->getEntityName());
    }

    /**
     * Returns the entity repository.
     * 
     * @return \Gibilogic\CrudBundle\Entity\EntityRepository
     */
    public function getRepository()
    {
        return $this->repo;
    }

    /**
     * Returns an instance of the entity, NULL on error.
     * 
     * @param integer $id
     * @return mixed
     */
    public function getEntity($id)
    {
        return $this->repo->find($id);
    }

    /**
     * Persists and creates a new entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $entity
     * @param mixed $form
     * @return boolean
     */
    public function createEntity(Request $request, $entity, $form)
    {
        $form->handleRequest($request);
        if (!$form->isValid())
        {
            return false;
        }

        try
        {
            $this->em->persist($entity);
            $this->em->flush();
        }
        catch (\Exception $ex)
        {
            return false;
        }

        return true;
    }

    /**
     * Updates an existing entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param mixed $entity
     * @param mixed $form
     */
    public function updateEntity(Request $request, $entity, $form)
    {
        $form->handleRequest($request);
        if (!$form->isValid())
        {
            return false;
        }

        try
        {
            $this->em->flush();
        }
        catch (\Exception $ex)
        {
            return false;
        }

        return true;
    }

    /**
     * Removes the specified entity.
     *
     * @param integer $id
     * @return boolean
     */
    public function removeEntity($id)
    {
        try
        {
            $entity = $this->getEntity($id);
            $this->em->remove($entity);
            $this->em->flush();
        }
        catch (\Exception $ex)
        {
            return false;
        }

        return true;
    }

    /**
     * Returns a form to create/edit an entity.
     *
     * @param mixed $entity
     * @param array $options
     * @return \Symfony\Component\Form\Form
     */
    public function createEntityForm($entity = null, $options = array())
    {
        return $this->container->get('form.factory')->create(
            $this->getNewEntityType(),
            empty($entity) ? $this->getNewEntity() : $entity,
            $options
        );
    }

    /**
     * Creates a form to delete an entity by id.
     *
     * @param integer $id
     * @return Symfony\Component\Form\Form
     */
    public function createDeleteForm($id)
    {
        return $this->container->get('form.factory')->createBuilder('form', array('id' => $id), array('csrf_protection' => false))
                ->add('id', 'hidden')
                ->setMethod('DELETE')
                ->getForm();
    }

    /**
     * Returns the current filters for the entity, if any.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $prefix
     * @param array $overrideFilters
     * @return array
     */
    public function getFilters(Request $request, $prefix, $overrideFilters = array())
    {
        $prefix .= '_filter_';
        $filters = array();

        // Extracts filters from session
        foreach ($request->getSession()->all() as $key => $value)
        {
            if (strpos($key, $prefix) === 0)
            {
                $filters[str_replace($prefix, '', $key)] = $value;
            }
        }

        // Extract filters from POST request
        foreach ($request->request->all() as $key => $value)
        {
            if (strpos($key, $prefix) === 0)
            {
                $filters[str_replace($prefix, '', $key)] = $value;
            }
        }

        if (empty($overrideFilters))
        {
            return $filters;
        }

        return array_merge($filters, $overrideFilters);
    }

    /**
     * Saves the current filters for the entity, if any.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $prefix
     */
    public function saveFilters(Request $request, $prefix)
    {
        $prefix .= '_filter_';
        $session = $request->getSession();

        foreach ($request->request->all() as $key => $value)
        {
            if (strpos($key, $prefix) === 0)
            {
                $session->set($key, $value);
            }
        }
    }

    /**
     * Resets all the filters for the entity, if any.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $prefix
     */
    public function resetFilters(Request $request, $prefix)
    {
        $prefix .= '_filter_';
        $session = $request->getSession();

        foreach ($request->getSession()->all() as $key => $value)
        {
            if (strpos($key, $prefix) === 0)
            {
                $session->remove($key);
            }
        }
    }

    /**
     * Returns the current sorting options for the entity.
     * 
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function getSorting(Request $request, $prefix)
    {
        return array(
            'field' => $this->getSortField($request, $prefix),
            'order' => $this->getSortOrder($request, $prefix)
        );
    }

    /**
     * Returns the current sort field for the entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $prefix
     * @return string|null
     */
    protected function getSortField(Request $request, $prefix)
    {
        $sortField = $request->query->get('sort', null);
        if (!empty($sortField))
        {
            $request->getSession()->set($prefix . '_sort_field', $sortField);
            return $sortField;
        }

        return $request->getSession()->get($prefix . '_sort_field', null);
    }

    /**
     * Returns the current sort order for the entity.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param string $prefix
     * @return string|null
     */
    protected function getSortOrder(Request $request, $prefix)
    {
        $sortOrder = $request->query->get('order', null);
        if (!empty($sortOrder))
        {
            $request->getSession()->set($prefix . '_sort_order', $sortOrder);
            return $sortOrder;
        }

        return $request->getSession()->get($prefix . '_sort_order', null);
    }

    /**
     * Returns the Symfony-styled entity name.
     *
     * @return Object
     */
    abstract protected function getEntityName();

    /**
     * Returns a new instance of the managed entity.
     *
     * @return Object
     */
    abstract protected function getNewEntity();

    /**
     * Returns a new form instance of the managed entity.
     *
     * @return \Symfony\Component\Form\AbstractType
     */
    abstract protected function getNewEntityType();
}
