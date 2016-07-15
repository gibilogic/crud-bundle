<?php

/**
 * @package     Gibilogic\CrudBundle
 * @subpackage  Utility
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Utility;

use Symfony\Component\HttpFoundation\Session\Session;

/**
 * Flashable trait.
 */
trait FlashableTrait
{
    /**
     * Adds a "notice" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param string $message
     */
    protected function addNoticeFlash(Session $session, $message)
    {
        $this->addUserFlash($session, 'notice', $message);
    }

    /**
     * Adds a "warning" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param string $message
     */
    protected function addWarningFlash(Session $session, $message)
    {
        $this->addUserFlash($session, 'warning', $message);
    }

    /**
     * Adds an "error" flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param string $message
     */
    protected function addErrorFlash(Session $session, $message)
    {
        $this->addUserFlash($session, 'error', $message);
    }

    /**
     * Adds a flash message to the current user's session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\Session $session
     * @param string $type
     * @param string $message
     */
    protected function addUserFlash(Session $session, $type, $message)
    {
        $session->getFlashBag()->add($type, $message);
    }
}
