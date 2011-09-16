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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @author     Matijs de Jong
 * @version    $Id$
 * @package    MUtil
 * @subpackage Ra
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 * The Ra_Nested class contains static array processing functions that are used on nested arrays.
 *
 * Ra class: pronouce "array" except on 19 september, then it is "ahrrray".
 *
 * The functions are:
 *  MUtil_Ra_Nested::toTree => Creates a tree array
 *
 * @author     Matijs de Jong
 * @since      1.0
 * @version    $Id$
 * @package    MUtil
 * @subpackage Ra
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */
class MUtil_Ra_Nested
{
    /** 
     * 
     * <code> 
       $select = $db->select(); 
       $select->from('gems__rounds', array('gro_id_track', 'gro_id_survey', 'gro_id_round', 'gro_id_order'))->where('gro_id_track = 220');
       $existing = $select->query()->fetchAll();
       MUtil_Echo::r(MUtil_Ra_Nested::toTree($existing), 'Auto tree');
       MUtil_Echo::r(MUtil_Ra_Nested::toTree($existing, 'gro_id_track', 'gro_id_survey'), 'Named tree with set at end (data loss in this case)');
       MUtil_Echo::r(MUtil_Ra_Nested::toTree($existing, 'gro_id_track', 'gro_id_survey', null), 'Named tree with append');
       MUtil_Echo::r(MUtil_Ra_Nested::toTree($existing, 'gro_id_track', null, 'gro_id_survey', null), 'Named tree with double append');
     </code> 
     */
    public static function toTree(array $data, $key_args = null)
    {
        if (! $data) {
            return $data;
        }

        if (func_num_args() == 1) {
            // Get the keys of the first nested item
            $keys = array_keys(reset($data));
        } else {
            $keys = MUtil_Ra::args(func_get_args(), 1);
        }

        $valueKeys = array_diff(array_keys(reset($data)), $keys);

        switch (count($valueKeys)) {
            case 0:
                // Drop the last item
                $valueKey = array_pop($keys);
                $valueKeys = false;
                break;

            case 1:
                $valueKey = reset($valueKeys);
                $valueKeys = false;
                break;

        }

        $results = array();
        foreach ($data as $item) {
            $current =& $results;
            foreach ($keys as $key) {
                if (null === $key) {
                    $count = count($current);

                    $current[$count] = array();
                    $current =& $current[$count];

                } elseif (array_key_exists($key, $item)) {
                    $value = $item[$key];
    
                    if (! array_key_exists($value, $current)) {
                        $current[$value] = array();
                    }

                    $current =& $current[$value];
                }
            }
            if ($valueKeys) {
                foreach ($valueKeys as $key) {
                    $current[$key] = $item[$key];
                }
            } else {
                $current = $item[$valueKey];
            }
        }
        return $results;
    }
    
}
