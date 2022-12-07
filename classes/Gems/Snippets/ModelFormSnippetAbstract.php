<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets;

use Gems\Html;
use Gems\MenuNew\MenuSnippetHelper;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Message\MessengerInterface;
use Zalt\Model\Bridge\FormBridgeAbstract;
use Zalt\Model\Bridge\FormBridgeInterface;
use Zalt\Model\Data\FullDataInterface;
use Zalt\Snippets\Zend\ZendModelFormSnippetAbstract;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 * Adds \Gems specific display details and helper functions:
 *
 * Items set are:
 * = Default route: 'show'
 * - Display class: 'formTable'
 * - \Gems\Form use: createForm()
 * - Table display: beforeDispay()
 *
 * Extra helpers are:
 * - Form title:   getTitle()
 * - Menu helpers: $this->menu, beforeDispay() & getMenuList()
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
abstract class ModelFormSnippetAbstract extends ZendModelFormSnippetAbstract
{
    use TopicCallableTrait;
    
    /**
     *
     * @var \Gems\AccessLog
     */
    // protected $accesslog;

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
     *
     * @var boolean
     */
    protected $menuShowChildren = false;

    /**
     *
     * @var boolean
     */
    protected $menuShowSiblings = false;

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = false;

    /**
     * @param \Zalt\SnippetsLoader\SnippetOptions                $snippetOptions
     * @param \Zalt\Base\RequestInfo                             $requestInfo
     * @param \Symfony\Contracts\Translation\TranslatorInterface $translate
     * @param \Zalt\Message\MessengerInterface                   $messenger
     * @param \Gems\MenuNew\MenuSnippetHelper                    $menuHelper
     */
    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        MessengerInterface $messenger,
        protected MenuSnippetHelper $menuHelper)
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $messenger);
        
        // $this->useCsrf = $project->useCsrfCheck();
    }
    
    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \Zalt\Model\Bridge\FormBridgeInterface $bridge
     * @param \Zalt\Model\Data\FullDataInterface $model
     */
    protected function addBridgeElements(FormBridgeInterface $bridge, FullDataInterface $model)
    {
        $metaModel = $model->getMetaModel();

        if (! $bridge->getForm() instanceof \Gems\TabForm) {
            parent::addBridgeElements($bridge, $model);
            return;
        }

        //Get all elements in the model if not already done
        $this->initItems($metaModel);

        // Add 'tooltip' to the allowed displayoptions
        $displayOptions = $bridge->getAllowedOptions(FormBridgeAbstract::DISPLAY_OPTIONS);
        if (!array_search('tooltip', $displayOptions)) {
            $displayOptions[] = 'tooltip';
            $bridge->setAllowedOptions(FormBridgeAbstract::DISPLAY_OPTIONS, $displayOptions);
        }

        $tab    = 0;
        $group  = 0;
        $oldTab = null;
        // \MUtil\EchoOut\EchoOut::track($metaModel->getItemsOrdered());
        foreach ($metaModel->getItemsOrdered() as $name) {
            // Get all options at once
            $modelOptions = $metaModel->get($name);
            $tabName      = $metaModel->get($name, 'tab');
            if ($tabName && ($tabName !== $oldTab)) {
                if (isset($modelOptions['elementClass']) && ('tab' == strtolower($modelOptions['elementClass']))) {
                    $bridge->addTab('tab' . $tab, $modelOptions + array('value' => $tabName));
                } else {
                    $bridge->addTab('tab' . $tab, 'value', $tabName);
                }
                $oldTab = $tabName;
                $tab++;
            }

            if ($metaModel->has($name, 'label') || $metaModel->has($name, 'elementClass')) {
                $bridge->add($name);

                if ($theName = $metaModel->get($name, 'startGroup')) {
                    //We start a new group here!
                    $groupElements   = array();
                    $groupElements[] = $name;
                    $groupName       = $theName;
                } elseif ($theName = $metaModel->get($name, 'endGroup')) {
                    //Ok, last element define the group
                    $groupElements[] = $name;
                    $bridge->addDisplayGroup('grp_' . $groupElements[0], $groupElements,
                            'description', $groupName,
                            'showLabels', ($theName == 'showLabels'),
                            'class', 'grp' . $group);
                    $group++;
                    unset($groupElements);
                    unset($groupName);
                } else {
                    //If we are in a group, add the elements to the group
                    if (isset($groupElements)) {
                        $groupElements[] = $name;
                    }
                }
            } else {
                $bridge->addHidden($name);
            }
            unset($this->_items[$name]);
        }
    }

    /**
     * Simple default function for making sure there is a $this->_saveButton.
     *
     * As the save button is not part of the model - but of the interface - it
     * does deserve it's own function.
     */
    protected function addSaveButton(string $saveButtonId, string $saveLabel, string $buttonClass)
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
     * /
    protected function afterSave($changed)
    {
        parent::afterSave($changed);

        if ($changed) {
            //$this->accesslog->logChange($this->request, null, $this->formData);
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     */
    public function beforeDisplay()
    {
        if ($this->_form instanceof \Gems\TabForm) {
            if ($links = $this->getMenuList()) {
                $linkContainer = \Zalt\Html\Html::create()->div(array('class' => 'element-container-labelless'));
                $linkContainer[] = $links;

                $element = $this->_form->createElement('html', 'formLinks');
                $element->setValue($linkContainer)
                        ->setOrder(999)
                        ->removeDecorator('HtmlTag')
                        ->removeDecorator('Label')
                        ->removeDecorator('DtDdWrapper');

                $this->_form->resetContext();
                $this->_form->addElement($element);

                if (is_null($this->_form->getDisplayGroup(\Gems\TabForm::GROUP_OTHER))) {
                    $this->_form->addDisplayGroup(array($element), \Gems\TabForm::GROUP_OTHER);
                } else {
                    $this->_form->getDisplayGroup(\Gems\TabForm::GROUP_OTHER)->addElement($element);
                }
            }
        } elseif($links = $this->getMenuList()) {
            $linkContainer = Html::div(['class' => 'element-container-labelless']);
            $linkContainer[] = $links;

            $element = $this->_form->createElement('html', 'formLinks');
            $element->setValue($linkContainer)
                    ->setOrder(999)
                    ->removeDecorator('HtmlTag')
                    ->removeDecorator('Label')
                    ->removeDecorator('DtDdWrapper');

            $this->_form->addElement($element);
        }
    }

    /**
     * Creates an empty form. Allows overruling in sub-classes.
     *
     * @param mixed $options
     * @return \Zend_Form
     */
    protected function createForm($options = null)
    {
        if ($this->useTabbedForm) {
            $this->_form = new \Gems\TabForm($options);
        } else {
            if (! isset($options['class'])) {
                $options['class'] = 'form-horizontal';
            }

            if (! isset($options['role'])) {
                $options['role'] = 'form';
            }
            $this->_form = new \Gems\Form($options);
        }
        return $this->_form;
    }

    /**
     * @return null|string
     */
    public function getCancelRoute()
    {
        return $this->menuHelper->getCurrentParentUrl();
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @return mixed Something that can be rendered
     */
    public function getHtmlOutput()
    {
        $htmlDiv = Html::div();

        $title = $this->getTitle();
        if ($title) {
            $htmlDiv->h3($title, ['class' => 'title']);
        }

        $form = parent::getHtmlOutput();
        $htmlDiv[] = $form;

        return $htmlDiv;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return string[]
     */
    protected function getMenuList(): array
    {
        $urls = [];
        // $urls[$this->_('Cancel')] = $this->getCancelRoute();

        $links = [];
        foreach($urls as $label => $url) {
            $links[] = Html::actionLink($url, $label);
        }

        /*if ($this->menuShowSiblings) {
            $links->addCurrentSiblings();
        }

        if ($this->menuShowChildren) {
            $links->addCurrentChildren();
        }*/

        return $links;
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

    /**
     * If menu item does not exist or is not allowed, redirect to index
     */
    protected function setAfterSaveRoute()
    {
        if (! $this->afterSaveRouteUrl) {
            $keys   = $this->getModel()->getMetaModel()->getKeys();
            $params = $this->requestInfo->getRequestMatchedParams();
            foreach ($keys as $key => $field) { 
                if (isset($this->formData[$field])) {
                    $params[$key] = $this->formData[$field];
                } else {
                    $params[$key] = null;
                }
            }
            
            $saveRoute = $this->menuHelper->getCurrentParentRoute();
            $this->afterSaveRouteUrl = $this->menuHelper->getRouteUrl($saveRoute, $params);
            $url = $this->menuHelper->getRouteUrl($saveRoute, $params);
            if ($url) {
                $this->afterSaveRouteUrl = $url;
            }
        }
        parent::setAfterSaveRoute();
    }
}
