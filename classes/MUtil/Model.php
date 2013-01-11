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
 */

/**
 * @author Matijs de Jong
 * @since 1.0
 * @version 1.1
 * @package MUtil
 * @subpackage Model
 */
class MUtil_Model
{
    /**
     * Indentifier for form (meta) assemblers and (field) processors
     */
    const FORM = 'form';

    /**
     * Indentifier for assemblers meta key
     */
    const META_ASSEMBLERS = 'assemblers';

    /**
     * In order to keep the url's short and to hide any field names from
     * the user, model identifies key values by using 'id' for a single
     * key value and id1, id2, etc... for multiple keys.
     */
    const REQUEST_ID = 'id';

    /**
     * Helper constant for first key value in multi value key.
     */
    const REQUEST_ID1 = 'id1';

    /**
     * Helper constant for second key value in multi value key.
     */
    const REQUEST_ID2 = 'id2';

    /**
     * Helper constant for third key value in multi value key.
     */
    const REQUEST_ID3 = 'id3';

    /**
     * Helper constant for forth key value in multi value key.
     */
    const REQUEST_ID4 = 'id4';

    /**
     * Default parameter name for sorting ascending.
     */
    const SORT_ASC_PARAM  = 'asort';

    /**
     * Default parameter name for sorting descending.
     */
    const SORT_DESC_PARAM = 'dsort';

    /**
     * Default parameter name for wildcard text search.
     */
    const TEXT_FILTER = 'search';

    const TYPE_NOVALUE = 0;
    const TYPE_STRING = 1;
    const TYPE_NUMERIC = 2;
    const TYPE_DATE = 3;
    const TYPE_DATETIME = 4;
    const TYPE_TIME = 5;

    /**
     *
     * @var MUtil_Loader_PluginLoader
     */
    private static $_assemblerLoader;

    /**
     * Static variable for debuggging purposes. Toggles the echoing of e.g. of sql
     * select statements, using MUtil_Echo.
     *
     * Implemention classes can use this variable to determine whether to display
     * extra debugging information or not. Please be considerate in what you display:
     * be as succint as possible.
     *
     * Use:
     *     MUtil_Model::$verbose = true;
     * to enable.
     *
     * @var boolean $verbose If true echo retrieval statements.
     */
    public static $verbose = false;

    /**
     * Returns the plugin loader for assemblers
     *
     * @return MUtil_Loader_PluginLoader
     */
    public static function getAssemblerLoader()
    {
        if (! self::$_assemblerLoader) {
            $loader = new MUtil_Loader_PluginLoader();

            $loader->addPrefixPath('MUtil_Model_Assembler', __DIR__ . '/Model/Assembler')
                    ->addFallBackPath();
            // maybe add interface def to plugin loader: MUtil_Model_AssemblerInterface

            self::$_assemblerLoader = $loader;
        }

        return self::$_assemblerLoader;
    }

    /**
     * Sets the plugin loader for assemblers
     *
     * @param MUtil_Loader_PluginLoader $loader
     */
    public static function setAssemblerLoader(MUtil_Loader_PluginLoader $loader)
    {
        self::$_assemblerLoader = $loader;
    }
}
