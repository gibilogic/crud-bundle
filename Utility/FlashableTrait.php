<?php

/*
 * This file is part of the GiBilogic CrudBundle package.
 *
 * (c) GiBilogic Srl <info@gibilogic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gibilogic\CrudBundle\Utility;

use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Common methods for adding flash messages to the user's session.
 *
 * @author Matteo Guindani https://github.com/Ingannatore
 * @see http://symfony.com/doc/current/book/controller.html#flash-messages
 */
trait FlashableTrait
{
    /**
     * Adds a "notice" flash message to the given session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     */
    protected function addNoticeFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'notice', $message);
    }

    /**
     * Adds a "warning" flash message to the given session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     */
    protected function addWarningFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'warning', $message);
    }

    /**
     * Adds an "error" flash message to the given session.
     *
     * @param \Symfony\Component\HttpFoundation\Session\SessionInterface $session
     * @param string $message
     */
    protected function addErrorFlash(SessionInterface $session, $message)
    {
        $this->addUserFlash($session, 'error', $message);
    }

    /**
     * Adds a flash message to the given session.
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
