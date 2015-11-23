<?php

/**
 * Copyright (c) 2015, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL MAGNAFACTA BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @version    $Id: LogFileAction.php 2430 2015-02-18 15:26:24Z matijsdejong $
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 23-nov-2015 12:13:12
 */
class Gems_Default_LogFileAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterParameters = array(
        'extraSort'   => array('changed' => SORT_DESC),
        );

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected $autofilterSnippets = 'FolderModelTableSnippet';

    /**
     * Should the action look recursively through the files
     *
     * @var boolean
     */
    public $recursive = true;

    /**
     * The parameters used for the show action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $showParameters = array('addOnclickEdit' => false);

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil_Model_ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \MUtil_Model_FolderModel(
                GEMS_ROOT_DIR . '/var/logs',
                null,
                $this->recursive
                );

        if ($this->recursive) {
            $model->set('relpath',  'label', $this->_('File (local)'),
                    'maxlength', 255,
                    'size', 40,
                    'validators', array('File_Path', 'File_IsRelativePath')
                    );
            $model->set('filename', 'elementClass', 'Exhibitor');
        }
        if ($detailed || (! $this->recursive)) {
            $model->set('filename',  'label', $this->_('Filename'), 'size', 30, 'maxlength', 255);
        }
        if ($detailed) {
            $model->set('path',      'label', $this->_('Path'), 'elementClass', 'Exhibitor');
            $model->set('fullpath',  'label', $this->_('Full name'), 'elementClass', 'Exhibitor');
            $model->set('extension', 'label', $this->_('Type'), 'elementClass', 'Exhibitor');
            $model->set('content',   'label', $this->_('Content'),
                    'formatFunction', array(\MUtil_Html::create(), 'pre'),
                    'elementClass', 'TextArea');
        }
        $model->set('size',      'label', $this->_('Size'),
                'formatFunction', array('MUtil_File', 'getByteSized'),
                'elementClass', 'Exhibitor');
        $model->set('changed',   'label', $this->_('Changed on'),
                'dateFormat', $this->util->getTranslated()->dateTimeFormatString,
                'elementClass', 'Exhibitor');

        return $model;
    }

    /*
    public function getLogDirs()
    {
        static $logDirs = false;

        if (! $logDirs) {
            $logDirs[GEMS_ROOT_DIR . '/var/logs'] = $this->_('Local log files');

            $log = ini_get('error_log');
        }
    } // */
}
