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
 * @package    MUtil
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @version    $Id: Importer.php$
 */

/**
 * Utility object for importing data from one model to another
 *
 * @package    MUtil
 * @subpackage Model
 * @copyright  Copyright (c) 2013 Erasmus MC
 * @license    New BSD License
 * @since      Class available since MUtil version 1.3
 */
class MUtil_Model_Importer extends MUtil_Translate_TranslateableAbstract
{
    /**
     * The final directory for when the data could not be imported.
     *
     * If empty the file is thrown away after a failure.
     *
     * @var string
     */
    public $failureDirectory;

    /**
     * The translator to use
     *
     * @var MUtil_Model_ModelTranslatorInterface
     */
    protected $importTranslator;

    /**
     * Optional, an array of one or more translators
     *
     * @var array of name => MUtil_Model_ModelTranslatorInterface objects
     */
    protected $importTranslators;

    /**
     * The filename minus the extension for long term storage.
     *
     * If empty the file is not renamed and may overwrite an existing file.
     *
     * @var string
     */
    protected $longtermFilename;

    /**
     * Registry source
     *
     * @var MUtil_Registry_SourceInterface
     */
    protected $registrySource;

    /**
     * Model to read import
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $sourceModel;

    /**
     * The final directory when the data was successfully imported.
     *
     * If empty the file is thrown away after the import.
     *
     * @var string
     */
    public $successDirectory;

    /**
     * Model to save import into
     *
     * Required, can be set by passing a model to $this->model
     *
     * @var MUtil_Model_ModelAbstract
     */
    protected $targetModel;

    /**
     * Clear the final directory for when the data could not be imported.
     *
     * The file is thrown away after a failure.
     *
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function clearFailureDirectory()
    {
        return $this->setFailureDirectory();
    }

    /**
     * Clears the filename for long term storage. The file is not renamed and
     * may overwrite an existing file.
     *
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function clearLongtermFilename()
    {
        return $this->setLongtermFilename();
    }

    /**
     * Clear the directory for when the data was successfully imported.
     *
     * The the file is thrown away after the import.
     *
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function clearSuccessDirectory($directory = null)
    {
        return $this->setSuccessDirectory();
    }

    /**
     *
     * @param MUtil_Task_TaskBatch $batch Optional batch with different source etc..
     * @return MUtil_Task_TaskBatch
     */
    public function getCheckImportBatch(MUtil_Task_TaskBatch $batch = null)
    {
        if (null === $batch) {
            $batch = new MUtil_Task_TaskBatch(__CLASS__ . '_check_' . $this->sourceModel->getName());
            $this->registrySource->applySource($batch);
            $batch->setSource($this->registrySource);
        }

        // $batch->autoStart = true;
        $batch->addTask('Import_ImportCheckTask');
        $batch->addTask('CheckCounterTask', 'import_errors', $this->_('Found %2$d import error(s). Import aborted.'));

        $targetModel = $this->getTargetModel();
        $batch->setVariable('targetModel', $targetModel);

        $importTranslator = $this->getImportTranslator();
        $importTranslator->setTargetModel($targetModel);
        $importTranslator->startImport();
        $batch->setVariable('modelTranslator', $importTranslator);

        $iter = $this->getSourceModel()->loadIterator();

        if (($iter instanceof Iterator) && ($iter instanceof Serializable)) {
            $batch->setSessionVariable('iterator', $iter);
        } else {
            $batch->setVariable('iterator', $iter);

            if ($batch->isPull()) {
                // Cannot pull when iterator is not serializable
                $batch->setMethodPush();
            }
        }

        return $batch;
    }

    /**
     * Get the final directory for when the data could not be imported.
     *
     * If empty the file is thrown away after the failure.
     *
     * @return string String or null when there is no failure storage
     */
    public function getFailureDirectory()
    {
        return $this->failureDirectory;
    }

    /**
     * Get the current translator, if set
     *
     * @return MUtil_Model_ModelTranslatorInterface or null
     */
    protected function getImportTranslator()
    {
        return $this->importTranslator;
    }

    /**
     * Get the filename minus the extension for long term storage.
     *
     * If empty the file is not renamed and may overwrite an existing file.
     *
     * @return string String or null when there is no renaming
     */
    public function getLongtermFilename()
    {
        return $this->longtermFilename;
    }

    /**
     * Get the data source for items created this importer (if any)
     *
     * @return MUtil_Registry_SourceInterface
     */
    public function getRegistrySource()
    {
        return $this->registrySource;
    }

    /**
     * Get the source model that provides the import data
     *
     * @return MUtil_Model_ModelAbstract
     */
    public function getSourceModel()
    {
        return $this->sourceModel;
    }

    /**
     * The final directory when the data was successfully imported.
     *
     * If empty the file is thrown away after the import.
     *
     * @return string String or null when there is no long term storage
     */
    public function getSuccessDirectory()
    {
        return $this->successDirectory;
    }

    /**
     * Get the target model for the imported data
     *
     * @return MUtil_Model_ModelAbstract
     */
    public function getTargetModel()
    {
        return $this->targetModel;
    }

    /**
     * The final directory for when the data could not be imported.
     *
     * If empty the file is thrown away after the failure.
     *
     * $param string $directory String or null when there is no failure storage
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setFailureDirectory($directory = null)
    {
        $this->failureDirectory = $directory;
        return $this;
    }

    /**
     * Set the current translator
     *
     * @param MUtil_Model_ModelTranslatorInterface|string $translator String must be one of $this->importtranslators
     * @return \MUtil_Model_Importer (continuation pattern)
     * @throws MUtil_Model_ModelTranslateException for string translators that do not exist
     */
    public function setImportTranslator($translator)
    {
        if (! $translator instanceof MUtil_Model_ModelTranslatorInterface) {
            // Lookup in importTranslators
            $transName = $translator;
            if (! isset($this->importTranslators[$transName])) {
                throw new MUtil_Model_ModelTranslateException(sprintf(
                        $this->_("Unknown translator. Should be one of: %s"),
                        implode($this->_(', '), array_keys($this->importTranslators))
                    ));
            }
            $translator = $this->importTranslators[$transName];

            // Extra check to be sure
            if (! $translator instanceof MUtil_Model_ModelTranslatorInterface) {
                throw new MUtil_Model_ModelTranslateException(sprintf(
                        $this->_('Programming error: Translator %s does not result in a translator model.'),
                        $transName
                        ));
            }
        }
        $this->importTranslator = $translator;
        return $this;
    }

    /**
     * Set the data source for items created this importer
     *
     * @param MUtil_Registry_SourceInterface $source
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setRegistrySource(MUtil_Registry_SourceInterface $source)
    {
        $this->registrySource = $source;
        return $this;
    }

    /**
     * The filename minus the extension for long term storage.
     *
     * If empty the file is not renamed and may overwrite an existing file.
     *
     * $param string $filenameWithoutExtension String or null when the file is not renamed
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setLongtermFilename($filenameWithoutExtension = null)
    {
        $this->longtermFilename = $filenameWithoutExtension;
        return $this;
    }

    /**
     * Set the source model using a filename
     *
     * @param string $filename
     * @param string $extension Optional extension if the extension of the file should not be used
     * @return \MUtil_Model_Importer (continuation pattern)
     * @throws MUtil_Model_ModelTranslateException for files with an unsupported extension or that fail to load
     */
    public function setSourceFile($filename, $extension = null)
    {
        if (null === $filename) {
            throw new MUtil_Model_ModelTranslateException($this->_("No filename specified to import"));
        }

        if (null === $extension) {
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
        }

        if (!file_exists($filename)) {
            throw new MUtil_Model_ModelTranslateException(sprintf(
                    $this->_("File '%s' does not exist. Import not possible."),
                    $filename
                    ));
        }

        switch (strtolower($extension)) {
            case 'txt':
                $model = new MUtil_Model_TabbedTextModel($filename);
                break;

            case 'xml':
                $model = new MUtil_Model_XmlModel($filename);
                break;

            default:
                throw new MUtil_Model_ModelTranslateException(sprintf(
                        $this->_("Unsupported file extension: %s. Import not possible."),
                        $extension
                        ));
        }

        $this->setSourceModel($model);

        return $this;
    }

    /**
     * Set the source model that provides the import data
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setSourceModel(MUtil_Model_ModelAbstract $model)
    {
        $this->sourceModel = $model;
        return $this;
    }

    /**
     * Set the source model using a string as content, the first line contains the
     * header for the field order in the rest of the data.
     *
     * @param string $content
     * @param string $fieldSplit
     * @param string $lineSplit
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setSourceText($content, $fieldSplit = "\t", $lineSplit = "\n")
    {
        $model = new MUtil_Model_NestedArrayModel('manual input', $content, $fieldSplit, $lineSplit);
        $this->setSourceModel($model);

        return $this;
    }

    /**
     * The final directory when the data was successfully imported.
     *
     * If empty the file is thrown away after the import.
     *
     * $param string $directory String or null when there is no long term storage
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setSuccessDirectory($directory = null)
    {
        $this->successDirectory = $directory;
        return $this;
    }

    /**
     * Set the target model for the imported data
     *
     * @param MUtil_Model_ModelAbstract $model
     * @return \MUtil_Model_Importer (continuation pattern)
     */
    public function setTargetModel(MUtil_Model_ModelAbstract $model)
    {
        $this->targetModel = $model;
        return $this;
    }
}
