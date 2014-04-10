<?php

/**
 * Copyright (c) 2013, Erasmus MC
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
class Gems_Import_ImportLoader extends Gems_Loader_TargetLoaderAbstract
{
    /**
     * Allows sub classes of Gems_Loader_LoaderAbstract to specify the subdirectory where to look for.
     *
     * @var string $cascade An optional subdirectory where this subclass always loads from.
     */
    protected $cascade = 'Import';

    // protected $importMatches = array();

    /**
     *
     * @var Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * @var Zend_Translate
     */
    protected $translate;

    /**
     * The model to use with a controller
     *
     * @param string $controller Name of controller (or other id)
     * @return MUtil_Model_ModelAbstract or null when not found
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
                $model->applyEditSettings(null);
                return $model;

            default:
                return null;
        }
    }

    /**
     * Name of the default import translator
     *
     * @param string $controller Name of controller (or other id)
     * @return string
     */
    public function getDefaultTranslator($controller)
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
        $defaultTrans = $this->getDefaultTranslator($controller);
        $targetModel  = $this->getControllerTargetModel($controller);
        $translators  = $this->getTranslators($controller);

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
        return '#^.*[.](txt|xml)$#';
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
     * @param MUtil_Model_ModelAbstract $targetModel
     * @return \Gems_Import_Importer
     */
    public function getImporter($controller, MUtil_Model_ModelAbstract $targetModel = null)
    {
        $importer = $this->_loadClass('Importer', true);

        if ($importer instanceof Gems_Import_Importer) {
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
     * The file name to use for final storage, minus the extension
     *
     * @param string $controller Name of controller (or other id)
     * @return string
     */
    public function getLongtermFileName($controller)
    {
        $user    = $this->loader->getCurrentUser();
        $orgCode = $user->getCurrentOrganization()->getCode();
        $orgId   = $orgCode ? $orgCode : MUtil_File::cleanupName($user->getCurrentOrganization()->getName());
        $date    = new MUtil_Date();

        $name[]  = $controller;
        $name[]  = $date->toString('YYYY-MM-ddTHH-mm-ss');
        $name[]  = preg_replace('/[^a-zA-Z0-9_]/', '', $user->getLoginName());
        $name[]  = $orgId;

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
     * @return Zend_Translate_Adapter
     */
    protected function getTranslateAdapter()
    {
        if ($this->translate instanceof Zend_Translate)
        {
            return $this->translate->getAdapter();
        }

        if (! $this->translate instanceof Zend_Translate_Adapter) {
            $this->translate = new MUtil_Translate_Adapter_Potemkin();
        }

        return $this->translate;
    }

    /**
     * Get the possible translators for the import snippet.
     *
     * @param string $controller Name of controller (or other id)
     * @return array of MUtil_Model_ModelTranslatorInterface objects
     */
    public function getTranslators($controller)
    {
        $translator = $this->getTranslateAdapter();

        switch ($controller) {
            case 'respondent':
                $trs = new Gems_Model_Translator_RespondentTranslator($translator->_('Direct import'));
                break;

            case 'calendar':
                $trs = new Gems_Model_Translator_AppointmentTranslator($translator->_('Direct import'));
                break;

            default:
                $trs = new Gems_Model_Translator_StraightTranslator($translator->_('Direct import'));
                break;
        }
        $this->applySource($trs);

        return array('default' => $trs);
    }
}
