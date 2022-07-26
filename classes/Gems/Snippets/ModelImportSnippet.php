<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 3:52:53 PM
 */
class ModelImportSnippet extends \MUtil\Snippets\Standard\ModelImportSnippet
{
    /**
     *
     * @var \Gems\AccessLog
     */
    protected $accesslog;

    /**
     * Hook for after save
     *
     * @param \MUtil\Task\TaskBatch $batch that was just executed
     * @param \MUtil\Form\Element\Html $element Tetx element for display of messages
     * @return string a message about what has changed (and used in the form)
     */
    public function afterImport(\MUtil\Task\TaskBatch $batch, \MUtil\Form\Element\Html $element)
    {
        $text = parent::afterImport($batch, $element);

        $data = $this->formData;

        // Remove unuseful data
        unset($data['button_spacer'], $data['current_step'], $data[$this->csrfId]);

        // Add useful data
        $data['localfile']        = basename($this->_session->localfile);
        $data['extension']        = $this->_session->extension;

        $data['failureDirectory'] = '...' . substr($this->importer->getFailureDirectory(), -30);
        $data['longtermFilename'] = basename($this->importer->getLongtermFilename());
        $data['successDirectory'] = '...' . substr($this->importer->getSuccessDirectory(), -30);
        $data['tempDirectory']    = '...' . substr($this->tempDirectory, -30);

        $data['importTranslator'] = get_class($this->importer->getImportTranslator());
        $data['sourceModelClass'] = get_class($this->sourceModel);
        $data['targetModelClass'] = get_class($this->targetModel);

        ksort($data);

        $this->accesslog->logChange($this->request, null, array_filter($data));
    }
}
