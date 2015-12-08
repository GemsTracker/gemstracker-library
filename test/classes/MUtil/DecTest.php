<?php


/**
 * Copyright (c) 2011, Erasmus MC
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *    * Redistributions of source code must retain the above copyright
 *      notice, this list of conditions and the following disclaimer.
 *    * Redistributions in binary form must reproduce the above copyright
 *      notice, this list of conditions and the following disclaimer in the
 *      documentation and/or other materials provided with the distribution.
 *    * Neither the name of Erasmus MC nor the
 *      names of its contributors may be used to endorse or promote products
 *      derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL <COPYRIGHT HOLDER> BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    MUtil
 * @subpackage Dec
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

require_once 'PHPUnit/Framework/TestCase.php';

/**
 * Unit test for class MUtil_String
 *
 * @package    MUtil
 * @subpackage Dec
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.7
 */
class MUtil_DecTest extends PHPUnit_Framework_TestCase
{
    /**
     * Dataprovider
     *
     * @return array
     */
    public function forCeiling()
    {
        return array(
            array(10.49825, 1, 10.5),
            array(10.99825, 1, 11.0),
            array(10.09825, 1, 10.1),
            array(10.09825, 2, 10.10),
            array(10.09825, 3, 10.099),
            array(79.99*100, 0, 7999),
            array(79.99*100, -1, 8000),
            array((10.02-10)*100, 1, 2.0),
            );
    }

    /**
     * Dataprovider
     *
     * @return array
     */
    public function forFloor()
    {
        return array(
            array(10.49825, 1, 10.4),
            array(10.99825, 1, 10.9),
            array(10.09825, 1, 10.0),
            array(10.09825, 2, 10.09),
            array(10.09825, 3, 10.098),
            array(79.99*100, 0, 7999),
            array(79.99*100, -1, 7990),
            array((10.02-10)*100, 1, 2.0),
            );
    }

    /**
     *
     * @dataProvider forCeiling
     * @param float $float
     * @param int $precision
     * @param float $output
     */
    public function testCeil($float, $precision, $output)
    {
        $this->assertEquals($output, MUtil_Dec::ceil($float, $precision));
    }

    /**
     *
     * @dataProvider forFloor
     * @param float $float
     * @param int $precision
     * @param float $output
     */
    public function testFloor($float, $precision, $output)
    {
        $this->assertEquals($output, MUtil_Dec::floor($float, $precision));
    }
}
