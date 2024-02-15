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

use MUtil\Task\TaskBatch;
use Zalt\Html\HtmlElement;
use Zalt\Snippets\Zend\ZendFormSnippetTrait;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2016 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 Mar 21, 2016 3:52:53 PM
 */
class ModelImportSnippet extends \Zalt\Snippets\Standard\ModelImportSnippet
{
    use ZendFormSnippetTrait;

    /**
     *
     * @var \Gems\Audit\AuditLog
     */
    protected $accesslog;

    /**
     * @inheritdoc
     */
    public function afterImport(TaskBatch $batch, HtmlElement $element)
    {
        $text = parent::afterImport($batch, $element);

        $data = $this->formData;

        // Remove unuseful data
        unset($data['button_spacer'], $data['current_step'], $data[$this->csrfName]);

        // Add useful data
        $data['localfile']        = basename($this->session->get('localfile'));
        $data['extension']        = $this->session->get('extension');

        $data['failureDirectory'] = '...' . substr($this->importer->getFailureDirectory(), -30);
        $data['longtermFilename'] = basename($this->importer->getLongtermFilename());
        $data['successDirectory'] = '...' . substr($this->importer->getSuccessDirectory(), -30);
        $data['tempDirectory']    = '...' . substr($this->tempDirectory, -30);

        $data['importTranslator'] = get_class($this->importer->getImportTranslator());
        $data['sourceModelClass'] = get_class($this->sourceModel);
        $data['targetModelClass'] = get_class($this->targetModel);

        ksort($data);

        // $this->accesslog->logChange($this->request, null, array_filter($data));
    }
}
