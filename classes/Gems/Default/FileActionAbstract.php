<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
abstract class Gems_Default_FileActionAbstract extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     *
     * @var \Gems_AccessLog
     */
    public $accesslog;

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
     * The parameters used for the createFolder action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $createFolderParameters = [];

    /**
     * The snippets used for the createFolder action
     *
     * @var mixed String or array of snippets name
     */
    protected $createFolderSnippets = [];

    /**
     * When true also follows symlinks. Only works when recursive is true
     *
     * @var boolean
     */
    protected $followSymlinks = false;
    
    /**
     * The snippets used for the import action
     *
     * @var mixed String or array of snippets name
     */
    protected $importSnippets = 'Import_FileImportSnippet';

    /**
     * The regex mask for filenames, use of / slashes for directory seperator required
     *
     * @var string Regular expression
     */
    protected $mask;

    /**
     * Should the action look recursively through the files
     *
     * @var boolean
     */
    public $recursive = false;

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
     * The parameters used for the upload action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $uploadParameters = [
        'currentDir' => 'getCurrentDir',
        'extensions' => 'getCurrentExtensions',
        'formTitle'  => 'getUploadTitle',
        ];

    /**
     * The snippets used for the upload action
     *
     * @var mixed String or array of snippets name
     */
    protected $uploadSnippets = ['File\\UploadFormSnippet'];

    /**
     * Upload a folder in the current folder
     */
    public function createFolderAction()
    {
        if ($this->createFolderSnippets) {
            $params = $this->_processParameters($this->createFolderParameters);

            $this->addSnippets($this->createFolderSnippets, $params);
        }
    }
    
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
        return $this->loader->getModels()->getFileModel(
            $this->getPath($detailed, $action),
            $detailed,
            $this->getMask($detailed, $action),
            $this->recursive,
            $this->followSymlinks
        );
    }

    /**
     * Confirm has been moved to javascript
     */
    public function deleteAction()
    {
        $request = $this->getRequest();

        $model = $this->getModel();
        $model->applyRequest($request);

        if ($model->getFilter()) {
            $model->delete();
        } else {
            $this->addMessage($this->_('Empty delete request not allowed'));
        }
        $this->_reroute(array($request->getActionKey() => 'index', MUTil_Model::REQUEST_ID => null));
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

    /**
     * @return string Get the directory to upload to
     */
    public function getCurrentDir()
    {
        return $this->getPath(true, 'create');
        return $this->getModel()->getCurrentDir();
    }

    /**
     * @return string Get the directory to upload to
     */
    public function getCurrentExtensions()
    {
        return $this->getModel()->getExtensions();
    }

    /**
     * Helper function to get the title for the edit action.
     *
     * @return $string
     */
    public function getEditTitle()
    {
        return sprintf($this->_('Edit file %s'), \MUtil_Model_Iterator_FolderModelIterator::fromUrlSaveToName($this->_getIdParam()));
    }

    /**
     * Return the mask to use for the relpath of the file, use of / slashes for directory seperator required
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string or null
     */
    public function getMask($detailed, $action)
    {
        return $this->mask;
    }

    /**
     * Returns the on empty texts for the autofilter snippets
     *
     * @static boolean $warned Listen very closely. I shall tell this only once!
     * @return string
     */
    public function getOnEmptyText()
    {
        static $warned;

        $dir = $this->getPath(false, 'index');

        if (! is_dir($dir)) {
            try {
                \MUtil_File::ensureDir($dir);

            } catch (\Zend_Exception $e) {
                $text = $e->getMessage();

                if (! $warned) {
                    $warned = true;
                    $this->addMessage($text);
                }
                return $text;
            }
        }

        return parent::getOnEmptyText();
    }

    /**
     * Return the path of the directory
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return string
     */
    abstract public function getPath($detailed, $action);

    /**
     * Helper function to get the title for the show action.
     *
     * @return $string
     */
    public function getShowTitle()
    {
        return sprintf($this->_('Showing file %s'), \MUtil_Model_Iterator_FolderModelIterator::fromUrlSaveToName($this->_getIdParam()));
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('file', 'files', $count);
    }

    /**
     * Helper function to get the title for the upload action.
     *
     * @return $string
     */
    public function getUploadTitle()
    {
        return sprintf($this->_('Upload file to %s'), $this->getCurrentDir());
    }
    
    /**
     * Import the file
     */
    public function importAction($id = null)
    {
        if (! $id) {
            $id = $this->_getIdParam();
        }
        $model = $this->getModel();

        $data = $model->loadFirst(array('urlpath' => $id));

        if (! ($data && isset($data['fullpath']))) {
            $this->addMessage(sprintf($this->_('File "%s" not found on the server.'), $id));
            return;
        }

        $importLoader = $this->loader->getImportLoader();
        $importer = $importLoader->getFileImporter($data['fullpath']);

        if (! $importer) {
            $this->addMessage(sprintf($this->_('Automatic import not possible for file "%s".'), $data['relpath']));
            return;
        }

        $batch = $importer->getCheckAndImportBatch();

        $title = $this->_('Import the file.');

        $this->_helper->batchRunner($batch, $title, $this->accesslog);

        $this->html->pInfo($this->_('Checks this file for validity and then performs an import.'));

        // \MUtil_Echo::track($data);
    }

    /**
     * Upload a file to the current folder
     */
    public function uploadAction()
    {
        if ($this->uploadSnippets) {
            $params = $this->_processParameters($this->uploadParameters);

            $this->addSnippets($this->uploadSnippets, $params);
        }
    }
}
