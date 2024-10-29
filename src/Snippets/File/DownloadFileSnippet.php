<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Snippets
 */

namespace Gems\Snippets\File;

use Gems\Audit\AuditLog;
use Gems\Exception;
use Gems\Form;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\FormSnippetAbstract;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Message\MessengerInterface;
use Zalt\Snippets\DataReaderGenericModelTrait;
use Zalt\SnippetsLoader\SnippetException;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @since      Class available since version 2.x
 */
class DownloadFileSnippet extends FormSnippetAbstract
{
    use DataReaderGenericModelTrait;

    protected string|null $directory;

    protected array $subjects = ['log file', 'log files'];

    /**
     * @param \Zalt\SnippetsLoader\SnippetOptions  $snippetOptions
     * @param \Zalt\Base\RequestInfo               $requestInfo
     * @param \Zalt\Base\TranslatorInterface       $translate
     * @param \Zalt\Message\MessengerInterface     $messenger
     * @param \Gems\Menu\MenuSnippetHelper         $menuHelper
     */
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        AuditLog $auditLog,
        MenuSnippetHelper $menuHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger, $auditLog, $menuHelper);

        $this->saveLabel = $this->_('Download');
    }

    public function getHtmlOutput()
    {
        $filename = $this->requestInfo->getParam('filename');
        if (preg_match('/\/|\.\./', $filename)) {
            throw new Exception('File not found');
        }
        $file = $this->directory.'/'.$filename;
        if (!is_readable($file)) {
            throw new Exception('File not found');
        }

        if ($this->requestInfo->isPost()) {
            header("Content-Type: application/octet-stream");
            header('Content-Disposition: attachment; filename="'.$filename.'"');
            header('Cache-Control: private, max-age=0, must-revalidate');
            header('Expires: 0');
            header('Pragma: no-cache');
            header('Content-Length: ' . filesize($file));
            readfile($file);
            die();
        }

        $container = Html::div(array('id' => 'export-form'));
        $container->append(parent::getHtmlOutput());
        return $container;
    }

    protected function createForm($options = null)
    {
        $options['id'] = 'downloadForm';

        $form = parent::createForm($options);
        $form->setAction($this->requestInfo->getPath() . '/' . $this->requestInfo->getParam('filename'));

        return $form;
    }

    protected function addFormElements(mixed $form)
    {
        if (! $form instanceof Form) {
            throw new SnippetException(sprintf("Incorrect form type %s, expected a \gems\Form form!", get_class($form)));
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