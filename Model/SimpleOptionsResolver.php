<?php

/*
 * This file is part of the GiBilogic CrudBundle package.
 *
 * (c) GiBilogic Srl <info@gibilogic.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Gibilogic\CrudBundle\Model;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * A wrapper for the OptionsResolver class to ease the creation and management
 * of options and/or parameters lists.
 *
 * @author Matteo Guindani https://github.com/Ingannatore
 * @see \Symfony\Component\OptionsResolver\OptionsResolver
 */
abstract class SimpleOptionsResolver extends OptionsResolver
{
    /**
     * Returns a new instance of the class.
     *
     * @return SimpleOptionsResolver
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Creates a new instance of the class and resolves the given options.
     *
     * @param array $options
     * @return array
     */
    public static function createAndResolve(array $options = [])
    {
        return static::create()->resolve($options);
    }

    /**
     * SimpleOptionsResolver constructor.
     */
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Initializes the configuration of the OptionsResolver.
     *
     * @return SimpleOptionsResolver
     */
    abstract protected function configure();
}
