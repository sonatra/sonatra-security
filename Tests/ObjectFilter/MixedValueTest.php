<?php

/*
 * This file is part of the Fxp package.
 *
 * (c) François Pluchino <francois.pluchino@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Fxp\Component\Security\Tests\ObjectFilter;

use Fxp\Component\Security\ObjectFilter\MixedValue;
use PHPUnit\Framework\TestCase;

/**
 * @author François Pluchino <francois.pluchino@gmail.com>
 */
class MixedValueTest extends TestCase
{
    public function getValues()
    {
        return array(
            array('string', null),
            array(42, null),
            array(42.5, null),
            array(true, null),
            array(false, null),
            array(null, null),
            array(new \stdClass(), null),
            array(array('42'), array()),
        );
    }

    /**
     * @dataProvider getValues
     *
     * @param mixed $value    The value
     * @param mixed $expected The expected value
     */
    public function test($value, $expected)
    {
        $mv = new MixedValue();

        $this->assertTrue($mv->supports($value));
        $this->assertSame($expected, $mv->getValue($value));
    }
}
