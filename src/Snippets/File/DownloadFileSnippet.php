<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 */

namespace Gems\Snippets\File;

use Gems\Exception;
use Gems\Form;
use Gems\Html;
use Gems\Snippets\FormSnippetAbstract;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\SnippetsLoader\SnippetException;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 2.x
 */
class DownloadFileSnippet extends FormSnippetAbstract
{
    use DataReaderGenericModelTrait;

    protected array $subjects = ['log file', 'log files'];

    public function getHtmlOutput()
    {
        $filename = $this->requestInfo->getParam('filename');
        if (preg_match('/\/|\.\./', $filename)) {
            throw new Exception('File not found');
        }
        $file = '/app/data/logs/'.$filename; // FIXME
        if (!is_readable($file)) {
            throw new Exception('File not found');
        }

        if ($this->requestInfo->isPost()) {
            header("Content-Type: application/text");
            header('Content-Disposition: inline; filename="'.$filename.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Pragma: no-cache');
            fpassthru($file);
            die();
        }

        $container = Html::div(array('id' => 'export-form'));
        $container->append(parent::getHtmlOutput());
        return $container;
    }

    protected function createForm($options = null)
    {
        $options['id'] = 'downloadForm';
        //$options['class'] = 'form-horizontal';
        //$options['data-autosubmit-inplace'] = true;

        $form = parent::createForm($options);
        $form->setAction($this->requestInfo->getPath() . '/' . $this->requestInfo->getParam('filename'));

        return $form;
    }

    protected function addFormElements(mixed $form)
    {
        if (! $form instanceof Form) {
            throw new SnippetException(sprintf("Incorrect form type %s, expected a \gems\Form form!"), get_class($form));
        }

        $this->addCsrf($this->csrfName, $this->csrfToken, $form);
    }

    /**
     * Hook that loads the form data from $_POST or the model
     *
     * Or from whatever other source you specify here.
     */
    protected function loadFormData(): array
    {
        $this->formData = $this->loadCsrfData() + $this->getDefaultFormValues() + $this->requestInfo->getRequestPostParams();
        return $this->formData;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        return sprintf($this->_('Download %s...'), $this->getTopic());
    }
}