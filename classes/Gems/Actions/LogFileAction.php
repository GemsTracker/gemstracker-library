<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 23-nov-2015 12:13:12
 */
class LogFileAction extends \Gems\Controller\ModelSnippetActionAbstract
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
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel($detailed, $action)
    {
        $model = new \MUtil\Model\FolderModel(
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
                    'formatFunction', array(\MUtil\Html::create(), 'pre'),
                    'elementClass', 'TextArea');
        }
        $model->set('size',      'label', $this->_('Size'),
                'formatFunction', array('\\MUtil\\File', 'getByteSized'),
                'elementClass', 'Exhibitor');
        $model->set('changed',   'label', $this->_('Changed on'),
                'dateFormat', $this->util->getTranslated()->dateTimeFormatString,
                'elementClass', 'Exhibitor');

        return $model;
    }

    /**
     * Confirm has been moved to javascript
     */
    public function deleteAction()
    {
        $model = $this->getModel();
        $model->applyRequest($this->getRequest());

        $model->delete();
        $this->_reroute(array($this->getRequest()->getActionKey() => 'index', MUTil_Model::REQUEST_ID => null));
    }

    /**
     * Action for downloading the file
     */
    public function downloadAction()
    {
        $model = $this->getModel();
        $model->applyRequest($this->getRequest());

        $fileData = $model->loadFirst();

        header('Content-Type: application/x-download');
        header('Content-Length: '.$fileData['size']);
        header('Content-Disposition: inline; filename="'.$fileData['filename'].'"');
        header('Pragma: public');

        echo $fileData['content'];
        exit();
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

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('log file', 'log files', $count);
    }
}
