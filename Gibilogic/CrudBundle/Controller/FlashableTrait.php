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
     */
    protected function addNoticeFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'notice', $message);
    }

    /**
     * Adds a "warning" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     */
    protected function addWarningFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'warning', $message);
    }

    /**
     * Adds an "error" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     */
    protected function addErrorFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'error', $message);
    }

    /**
     * Adds a flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $type
     * @param string $message
     */
    protected function addUserFlash(SessionInterface $session, $type, $message)
    {
        $session->getFlashBag()->add($type, $message);
    }
}
