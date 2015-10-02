<?php

/**
 * @package     Gibilogic\CrudBundle\Test
 * @author      GiBiLogic <info@gibilogic.com>
 * @authorUrl   http://www.gibilogic.com
 */

namespace Gibilogic\CrudBundle\Test;

use Gibilogic\CrudBundle\Service\Slugger;

/**
 * Class SluggerTest.
 *
 * @see \PHPUnit_Framework_TestCase
 */
class SluggerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Test for dot (.) conversion.
     */
    public function testDot()
    {
        $slugger = new Slugger();

        $this->assertEquals('test-dot', $slugger->slugify('test.dot'));
    }
}
