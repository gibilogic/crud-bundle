<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Controller
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * CrudController class.
 *
 * @abstract
 */
abstract class CrudController extends Controller
{
    use FlashableTrait;

    /**
     * Returns the controller's base route prefix.
     *
     * @abstract
     * @return string
     */
    abstract protected function getRoutePrefix();

    /**
     * Returns the controller's base route prefix.
     *
     * @abstract
     * @return \Gibilogic\CrudBundle\Service\EntityService
     */
    abstract protected function getEntityService();

    /**
     * Index action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function executeIndexAction(Request $request)
    {
        return $this->getEntityService()->getEntities($request, $this->getRoutePrefix());
    }

    /**
     * Paginated index action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return array
     */
    public function executeIndexPaginatedAction(Request $request)
    {
        return $this->getEntityService()->getEntities($request, $this->getRoutePrefix(), true);
    }

    /**
     * Show action.
     * 
     * @param integer $id
     * @return mixed
     */
    public function executeShowAction($id)
    {
        $entityService = $this->getEntityService();

        try {
            $entity = $entityService->getEntity($id);
        } catch (\Exception $ex) {
            return $this->redirectOnNotFound($id);
        }

        return array(
            'entity' => $entity,
            'deleteForm' => $entityService->createDeleteForm($id)->createView()
        );
    }

    /**
     * New action.
     *
     * @return array
     */
    public function executeNewAction()
    {
        $entityService = $this->getEntityService();
        $entity = $entityService->getNewEntity();

        return array(
            'entity' => $entity,
            'form' => $entityService->createEntityForm($entity, array('method' => 'POST'))->createView(),
        );
    }

    /**
     * Create action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @return mixed
     */
    public function executeCreateAction(Request $request)
    {
        $entityService = $this->getEntityService();

        $entity = $entityService->getNewEntity();
        $form = $entityService->createEntityForm($entity, array('method' => 'POST'));

        if (!$entityService->createEntity($request, $entity, $form)) {
            $this->addErrorFlash($this->get('session'), $this->getFormErrorMessage());
            return array(
                'entity' => $entity,
                'form' => $form->createView(),
            );
        }

        $this->addNoticeFlash($this->get('session'), $this->getEntitySavedMessage($entity));
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_index'));
    }

    /**
     * Edit action.
     *
     * @param integer $id
     * @return mixed
     */
    public function executeEditAction($id)
    {
        $entityService = $this->getEntityService();

        $entity = $entityService->getEntity($id);
        if ($entity === null) {
            return $this->redirectOnNotFound($id);
        }

        return array(
            'entity' => $entity,
            'form' => $entityService->createEntityForm($entity, array('method' => 'PUT'))->createView(),
            'deleteForm' => $entityService->createDeleteForm($id)->createView()
        );
    }

    /**
     * Update action.
     *
     * @param \Symfony\Component\HttpFoundation\Request $request
     * @param integer $id
     * @return mixed
     */
    public function executeUpdateAction(Request $request, $id)
    {
        $entityService = $this->getEntityService();

        $entity = $entityService->getEntity($id);
        if ($entity === null) {
            return $this->redirectOnNotFound($id);
        }

        $form = $entityService->createEntityForm($entity, array('method' => 'PUT'));
        if (!$entityService->updateEntity($request, $entity, $form)) {
            $this->addErrorFlash($this->get('session'), $this->getFormErrorMessage());
            return array(
                'entity' => $entity,
                'form' => $form->createView(),
                'deleteForm' => $entityService->createDeleteForm($id)->createView()
            );
        }

        $this->addNoticeFlash($this->get('session'), $this->getEntitySavedMessage($entity));
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_show', array('id' => $id)));
    }

    /**
     * Delete action.
     *
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function executeDeleteAction($id)
    {
        $entity = $this->getEntityService()->removeEntity($id);
        if ($entity === false) {
            $this->addErrorFlash($this->get('session'), $this->getDeleteErrorMessage($id));
            return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_show', array('id' => $id)));
        }

        $this->addNoticeFlash($this->get('session'), $this->getEntityDeletedMessage($entity));
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_index'));
    }

    /**
     * Returns a RedirectResponse after a not found entity.
     * 
     * @param integer $id
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    protected function redirectOnNotFound($id)
    {
        $this->addWarningFlash($this->get('session'), $this->getNotFoundErrorMessage($id));
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_index'));
    }

    /**
     * Returns the "entity succesfully saved" message.
     *
     * @param object $entity
     * @return string
     */
    protected function getEntitySavedMessage($entity)
    {
        return sprintf("The entity with ID '%d' has been saved.", $entity->getId());
    }

    /**
     * Returns the "entity succesfully deleted" message.
     * 
     * @param object $entity
     * @return string
     */
    protected function getEntityDeletedMessage($entity)
    {
        return sprintf("The entity with ID '%d' has been deleted.", $entity->getId());
    }

    /**
     * Returns the "form has errors" message.
     *
     * @return string
     */
    protected function getFormErrorMessage()
    {
        return "There are one or more errors inside of the entity's form.";
    }

    /**
     * Returns the "unable to delete entity" message.
     * 
     * @param integer $id
     * @return string
     */
    protected function getDeleteErrorMessage($id)
    {
        return sprintf("Unable to delete the entity with ID '%d'.", $id);
    }

    /**
     * Returns the "entity not found" message.
     * 
     * @param integer $id
     * @return string
     */
    protected function getNotFoundErrorMessage($id)
    {
        return sprintf("The entity with ID '%d' does not exist.", $id);
    }
}
