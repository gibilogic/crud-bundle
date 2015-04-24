<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Controller
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Controller;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Flashable trait.
 */
trait FlashableTrait
{

    /**
     * Adds a "notice" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     * @param mixed $params
     */
    protected function addNoticeFlash(SessionInterface $session, $message, $params = array())
    {
        $this->addUserFlash($session, 'notice', $message, is_array($params) ? $params : array($params));
    }

    /**
     * Adds a "warning" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     * @param mixed $params
     */
    protected function addWarningFlash(SessionInterface $session, $message, $params = array())
    {
        $this->addUserFlash($session, 'warning', $message, is_array($params) ? $params : array($params));
    }

    /**
     * Adds an "error" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     * @param mixed $params
     */
    protected function addErrorFlash(SessionInterface $session, $message, $params = array())
    {
        $this->addUserFlash($session, 'error', $message, is_array($params) ? $params : array($params));
    }

    /**
     * Adds a flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $type
     * @param string $message
     * @param array $params
     */
    protected function addUserFlash(SessionInterface $session, $type, $message, array $params)
    {
        $session->getFlashBag()->add($type, empty($params) ? $message : vsprintf($message, $params));
    }
}
