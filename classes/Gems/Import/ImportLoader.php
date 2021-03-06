<?php

/**
 *
 * @package    Gems
 * @subpackage Import
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: ImportLoader.php$
 */

/**
 * The import loader is used to gather all the GemsTracker specific knowledge
 * for importing model data.
 *
 * @package    Gems
 * @subpackage Import
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.2
 */
class Gems_Import_ImportLoader extends \Gems_Loader_TargetLoaderAbstract
{
    /**
     *
     * @var string
     */
    protected $_orgCode;

    /**
     * Allows sub classes of \Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Import';

    // protected $importMatches = array();

    /**
     *
     * @var \Gems_User_Organization
     */
    protected $currentOrganization;

    /**
     *
     * @var \Gems_User_User
     */
    protected $currentUser;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * @var \Zend_Translate
     */
    protected $translate;

    /**
     * Function to load survey specific import translators,
     * as opposed to the generic answer translators loaded
     * using $this->getTranslators('answers')
     *
     * @param \Gems_Tracker_Survey $survey
     * @param string $filename Optional, name of file to import
     * @return array name => translator
     */
    public function getAnswerImporters(\Gems_Tracker_Survey $survey, $filename = null)
    {
        return array();
    }

    /**
     * The model to use with a controller
     *
     * @param string $controller Name of controller (or other id)
     * @return \MUtil_Model_ModelAbstract or null when not found
     */
    protected function getControllerTargetModel($controller)
    {
        switch ($controller) {
            case 'respondent':
                $model = $this->loader->getModels()->getRespondentModel(true);
                $model->applyEditSettings();
                return $model;

            case 'calendar':
                $model = $this->loader->getModels()->createAppointmentModel();
                $this->applySource($model);
                $model->applyEditSettings();
                return $model;

            default:
                return null;
        }
    }

    /**
     * Name of the default import translator
     *
     * @param string $controller Name of controller (or other id)
     * @param string $filename Optional, name of file to import
     * @return string
     */
    public function getDefaultTranslator($controller, $filename = null)
    {
        return 'default';
    }

    /**
     * The directory where the file should be stored when the import failed
     *
     * @param string $controller Name of controller (or other id)
     * @return string
     */
    public function getFailureDirectory($controller = null)
    {
        return rtrim(GEMS_ROOT_DIR . '/var/import_failed/' . $controller, '/');
    }

    /**
     *
     * @param string $filename Name of file to import
     * @return \Gems_Import_Importer or null
     */
    public function getFileImporter($filename)
    {
        $controller   = $this->getFilenameController($filename);
        $defaultTrans = $this->getDefaultTranslator($controller, $filename);
        $targetModel  = $this->getControllerTargetModel($controller);
        $translators  = $this->getTranslators($controller, $filename);

        if (! ($controller && $targetModel && isset($translators[$defaultTrans]))) {
            return null;
        }

        $importer = $this->getImporter($controller, $targetModel);
        $importer->setImportTranslator($translators[$defaultTrans]);
        $importer->setSourceFile($filename);

        return $importer;
    }

    /**
     * The regex mask for source filenames, use of / slashes for directory seperator required
     *
     * @return string
     */
    public function getFileImportMask()
    {
        return '#^.*[.](txt|xml|csv)$#';
    }

    /**
     * Get the directory to use as the root for automatic import
     *
     * @return string
     */
    public function getFileImportRoot()
    {
        return $this->project->getFileImportRoot();
    }

    /**
     * Get the controller that should be linked to the filename
     *
     * @param string $filename Name of file to import
     * @return string or false if none found.
     */
    public function getFilenameController($filename)
    {
        $filename = strtolower(basename($filename));
        if (preg_match('/^respondent/', $filename)) {
            return 'respondent';
        }
        if (preg_match('/^appointment/', $filename)) {
            return 'calendar';
        }

        return false;
    }

    /**
     *
     * @param string $controller Name of controller (or other id)
     * @param \MUtil_Model_ModelAbstract $targetModel
     * @return \Gems_Import_Importer
     */
    public function getImporter($controller, \MUtil_Model_ModelAbstract $targetModel = null)
    {
        $importer = $this->_loadClass('Importer', true);

        if ($importer instanceof \Gems_Import_Importer) {
            $importer->setRegistrySource($this);

            $importer->setFailureDirectory($this->getFailureDirectory($controller));
            $importer->setLongtermFilename($this->getLongtermFileName($controller));
            $importer->setSuccessDirectory($this->getSuccessDirectory($controller));

            if (null !== $targetModel) {
                $importer->setTargetModel($targetModel);
            }
        }

        return $importer;
    }

    /**
     * Get the organization name/code used by the long term filename
     *
     * @return string
     */
    public function getOrganizationCode()
    {
        if (! $this->_orgCode) {
            $this->_orgCode = $this->currentOrganization->getCode();
            if (! $this->_orgCode) {
                $this->_orgCode = \MUtil_File::cleanupName($this->currentOrganization->getName());
            }

        }

        return $this->_orgCode;
    }

    /**
     * The file name to use for final storage, minus the extension
     *
     * @param string $controller Name of controller (or other id)
     * @param mixed $dateValue Optional date item to use in filename, timestamp, or DateObject or MUtil_Date
     * @return string
     */
    public function getLongtermFileName($controller, $dateValue = null)
    {
        if ($dateValue instanceof \Zend_Date) {
            $date = $dateValue;
        } else {
            $date = new \MUtil_Date($dateValue);
        }

        $name[] = $controller;
        $name[] = $date->toString('YYYY-MM-ddTHH-mm-ss');
        $name[] = preg_replace('/[^a-zA-Z0-9_]/', '', $this->currentUser->getLoginName());
        $name[] = $this->getOrganizationCode();

        return implode('.', array_filter($name));
    }

    /**
     * The directory where the file should be stored when the import succeeded
     *
     * @param string $controller Name of controller (or other id)
     * @return string
     */
    public function getSuccessDirectory($controller = null)
    {
        return rtrim(GEMS_ROOT_DIR . '/var/imported/' . $controller, '/');
    }

    /**
     * The directory to use for temporary storage
     *
     * @return string
     */
    public function getTempDirectory()
    {
        return GEMS_ROOT_DIR . '/var/importing';
    }

    /**
     * Returns a translate adaptor
     *
     * @return \Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof \Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof \Zend_Translate_Adapter) {
            $this->translate = new \MUtil_Translate_Adapter_Potemkin();
        }

        return $this->translate;
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @param string $controller Name of controller (or other id)
     * @param string $filename Optional, name of file to import
     * @return \MUtil_Model_ModelTranslatorInterface[]
     */
    public function getTranslators($controller, $filename = null)
    {
        $translator = $this->getTranslateAdapter();

        switch ($controller) {
            case 'answers':
                $output['default'] = new \Gems_Model_Translator_TokenAnswerTranslator(
                        $translator->_('Link by token id')
                        );
                $output['resp']    = new \Gems_Model_Translator_RespondentAnswerTranslator(
                        $translator->_('Link by patient id')
                        );
                $output['date']    = new \Gems_Model_Translator_DateAnswerTranslator(
                        $translator->_('Link by patient id and completion date')
                        );
                break;

            case 'calendar':
                $output['default'] = new \Gems_Model_Translator_AppointmentTranslator($translator->_('Direct import'));
                break;

            case 'respondent':
                $output['default'] = new \Gems_Model_Translator_RespondentTranslator($translator->_('Direct import'));
                break;

            case 'staff':
                $output['default'] = new \Gems_Model_Translator_StaffTranslator($translator->_('Direct import'));
                break;

            default:
                $output['default'] = new \Gems_Model_Translator_StraightTranslator($translator->_('Direct import'));
                break;
        }

        foreach ($output as $trs) {
            $this->applySource($trs);
        }

        return $output;
    }

    /**
     * Set the organization name/code used by the long term filename
     *
     * @param string $code
     * @return \Gems_Import_ImportLoader (continuation pattern)
     */
    public function setOrganizationCode($code)
    {
        $this->_orgCode = $code;
        return $this;
    }
}
