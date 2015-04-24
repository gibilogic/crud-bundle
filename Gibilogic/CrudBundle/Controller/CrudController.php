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
     *
     * @return string
     */
    abstract protected function getRoutePrefix();

    /**
     * Returns the controller's base route prefix.
     *
     * @abstract
     * 
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

        $entity = $entityService->getEntity($id);
        if (empty($entity)) {
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
            $this->addErrorFlash($this->get('session'), "Ci sono uno o più errori nella form di creazione entità.");
            return array(
                'entity' => $entity,
                'form' => $form->createView(),
            );
        }

        $this->addNoticeFlash($this->get('session'), "L'entità con ID '%d' è stata correttamente salvata.", $entity->getId());
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
            $this->addErrorFlash($this->get('session'), "Ci sono uno o più errori nella form di modifica entità.");
            return array(
                'entity' => $entity,
                'form' => $form->createView(),
                'deleteForm' => $entityService->createDeleteForm($id)->createView()
            );
        }

        $this->addNoticeFlash($this->get('session'), "L'entità con ID '%d' è stata correttamente salvata.", $id);
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
        $entityService = $this->getEntityService();

        $entity = $entityService->getEntity($id);
        if (empty($entity)) {
            return $this->redirectOnNotFound($id);
        }

        if (!$entityService->removeEntity($id)) {
            $this->addErrorFlash($this->get('session'), "Impossibile rimuovere l'entità con ID '%d'.", $id);
            return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_show', array('id' => $id)));
        }

        $this->addNoticeFlash($this->get('session'), "L'entità con ID '%d' è stata correttamente rimossa.", $id);
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
        $this->addWarningFlash($this->get('session'), "L'entità con ID '%d' non esiste.", $id);
        return $this->redirect($this->generateUrl($this->getRoutePrefix() . '_index'));
    }
}
