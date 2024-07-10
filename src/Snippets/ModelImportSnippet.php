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

use Gems\Model\Translator\StraightTranslator;
use Zalt\Html\HtmlElement;
use Zalt\Model\Translator\ModelTranslatorInterface;
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
class ModelImportSnippet extends \Zalt\Snippets\ModelImportSnippetAbstract
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
    public function afterImport(HtmlElement $element)
    {
        $text = parent::afterImport($element);

        $data = $this->formData;

        // Remove unuseful data
        unset($data['button_spacer'], $data['current_step'], $data[$this->csrfName]);

        // Add useful data
        $data['localfile']        = basename($this->session->get('localfile'));
        $data['extension']        = $this->session->get('extension');

//        $data['failureDirectory'] = '...' . substr($this->importer->getFailureDirectory(), -30);
//        $data['longtermFilename'] = basename($this->importer->getLongtermFilename());
//        $data['successDirectory'] = '...' . substr($this->importer->getSuccessDirectory(), -30);
        $data['tempDirectory']    = '...' . substr($this->tempDirectory, -30);

//        $data['importTranslator'] = get_class($this->importer->getCurrentImportTranslator());
        $data['sourceModelClass'] = get_class($this->sourceModel);
        $data['targetModelClass'] = get_class($this->targetModel);

        ksort($data);

        // $this->accesslog->logChange($this->request, null, array_filter($data));
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        $form = new \Gems\Form($options);

        return $form;
    }

    /**
     * @param string $name Optional identifier
     * @return ModelTranslatorInterface
     */
    protected function getImportTranslator(string $name = ''): ModelTranslatorInterface
    {
        $translator = $this->metaModelLoader->createTranslator(StraightTranslator::class);
        $translator->setTargetModel($this->targetModel);
        $translator->setDescription($this->_('Straight translator'));

        return $translator;
    }

    protected function init(): void
    {
        parent::init();

        $this->stepsHeader = $this->_('Data import. Step %d of %d.');
    }
}
