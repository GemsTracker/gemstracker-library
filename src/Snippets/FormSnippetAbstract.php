<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Audit\AuditLog;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\Generic\ButtonRowTrait;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslatorInterface;
use Zalt\Html\Raw;
use Zalt\Message\MessengerInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 14-okt-2015 11:26:15
 */
abstract class FormSnippetAbstract extends ZendFormSnippetAbstract
{
    use AuditLogDataCleanupTrait;
    use ButtonRowTrait;

    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'formTable';

    /**
     * An optional title for the form. replacing the current generic form title.
     *
     * @var string Optional
     */
    protected $formTitle;

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
        protected readonly AuditLog $auditLog,
        protected readonly MenuSnippetHelper $menuHelper,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);

        $this->saveLabel = $this->_('Save');
        // $this->useCsrf = $project->useCsrfCheck();
    }

    /**
     * @inheritdoc
     */
    // abstract protected function addFormElements(mixed $form);

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton(string $saveButtonId, ?string $saveLabel, string $buttonClass)
    {
        if ("OK" == $this->saveLabel) {
            $this->saveLabel = $this->_('Save');
        }

        if ($this->_form instanceof \Gems\TabForm) {
            $this->_form->resetContext();
        }
        parent::addSaveButton($saveButtonId, $saveLabel, $buttonClass);
    }

    /**
     * Hook that allows actions when data was saved
     *
     * When not rerouted, the form will be populated afterwards
     *
     * @param int $changed The number of changed rows (0 or 1 usually, but can be more)
     */
    protected function afterSave($changed)
    {
        parent::afterSave($changed);

        if ($changed) {
            $this->logChanges($changed);
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     */
    public function beforeDisplay()
    {
        $menuList = $this->getButtons();

        if (count($menuList)) {
            $container = Html::create('div', array('class' => 'fromButtons', 'renderClosingTag' => true));
            foreach ($menuList as $buttonInfo) {
                if (isset($buttonInfo['label'])) {
                    if (isset($buttonInfo['disabled']) && $buttonInfo['disabled'] === true) {
                        $container->append(Html::actionDisabled(Raw::raw($buttonInfo['label'])));
                    } elseif (isset($buttonInfo['url'])) {
                        $container->append(Html::actionLink($buttonInfo['url'], Raw::raw($buttonInfo['label'])));
                    }
                }
            }

            $buttons = $this->_form->createElement('Html', 'buttons');
            if ($buttons instanceof \MUtil\Bootstrap\Form\Element\Html) {
                $buttons->setValue($container);
                $this->_form->addElement($buttons);
            }
        }

        parent::beforeDisplay();
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
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $htmlDiv = Html::div();

        $htmlDiv->h3($this->getTitle(), array('class' => 'title'));

        $form = parent::getHtmlOutput();

        $htmlDiv[] = $form;

        return $htmlDiv;
    }

    /**
     * Retrieve the header title to display
     *
     * @return string
     */
    protected function getTitle()
    {
        if ($this->formTitle) {
            return $this->formTitle;
        } elseif ($this->createData) {
            return sprintf($this->_('New %s...'), $this->getTopic());
        } else {
            return sprintf($this->_('Edit %s'), $this->getTopic());
        }
    }

    protected function logChanges(int $changes)
    {
        $oldData = $this->loadCsrfData() + $this->getDefaultFormValues() + $this->requestInfo->getRequestMatchedParams();
        $this->auditLog->registerChanges($this->cleanupLogData($this->formData), $oldData);
    }
}
