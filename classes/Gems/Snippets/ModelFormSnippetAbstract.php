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
abstract class ModelFormSnippetAbstract extends \MUtil\Snippets\ModelFormSnippetAbstract
{
    /**
     *
     * @var \Gems\AccessLog
     */
    protected $accesslog;

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
     * When set getTopic uses this function instead of parent class.
     *
     * @var callable
     */
    protected $topicCallable;

    /**
     * Required
     *
     * @var \Gems\Menu
     */
    protected $menu;

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
     *
     * @var \Gems\Project\ProjectSettings
     */
    protected $project;

    /**
     * The name of the action to forward to after form completion
     *
     * @var string
     */
    protected $routeAction = 'show';

    /**
     * When true a tabbed form is used.
     *
     * @var boolean
     */
    protected $useTabbedForm = false;

    /**
     * Adds elements from the model to the bridge that creates the form.
     *
     * Overrule this function to add different elements to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\FormBridgeInterface $bridge
     * @param \MUtil\Model\ModelAbstract $model
     */
    protected function addFormElements(\MUtil\Model\Bridge\FormBridgeInterface $bridge, \MUtil\Model\ModelAbstract $model)
    {
        if (! $bridge->getForm() instanceof \Gems\TabForm) {
            parent::addFormElements($bridge, $model);
            return;
        }

        //Get all elements in the model if not already done
        $this->initItems();

        // Add 'tooltip' to the allowed displayoptions
        $displayOptions = $bridge->getAllowedOptions(\MUtil\Model\Bridge\FormBridge::DISPLAY_OPTIONS);
        if (!array_search('tooltip', $displayOptions)) {
            $displayOptions[] = 'tooltip';
            $bridge->setAllowedOptions(\MUtil\Model\Bridge\FormBridge::DISPLAY_OPTIONS, $displayOptions);
        }

        $tab    = 0;
        $group  = 0;
        $oldTab = null;
        // \MUtil\EchoOut\EchoOut::track($model->getItemsOrdered());
        foreach ($model->getItemsOrdered() as $name) {
            // Get all options at once
            $modelOptions = $model->get($name);
            $tabName      = $model->get($name, 'tab');
            if ($tabName && ($tabName !== $oldTab)) {
                if (isset($modelOptions['elementClass']) && ('tab' == strtolower($modelOptions['elementClass']))) {
                    $bridge->addTab('tab' . $tab, $modelOptions + array('value' => $tabName));
                } else {
                    $bridge->addTab('tab' . $tab, 'value', $tabName);
                }
                $oldTab = $tabName;
                $tab++;
            }

            if ($model->has($name, 'label') || $model->has($name, 'elementClass')) {
                $bridge->add($name);

                if ($theName = $model->get($name, 'startGroup')) {
                    //We start a new group here!
                    $groupElements   = array();
                    $groupElements[] = $name;
                    $groupName       = $theName;
                } elseif ($theName = $model->get($name, 'endGroup')) {
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
    protected function addSaveButton()
    {
        if ($this->_form instanceof \Gems\TabForm) {
            $this->_form->resetContext();
        }
        parent::addSaveButton();
    }

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        if ($this->project instanceof \Gems\Project\ProjectSettings) {
            $this->useCsrf = $this->project->useCsrfCheck();
        }
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
            //$this->accesslog->logChange($this->request, null, $this->formData);
        }
    }

    /**
     * Perform some actions on the form, right before it is displayed but already populated
     *
     * Here we add the table display to the form.
     *
     * @return \Zend_Form
     */
    public function beforeDisplay()
    {
        if ($this->_form instanceof \Gems\TabForm) {
            if ($links = $this->getMenuList()) {
                $linkContainer = \MUtil\Html::create()->div(array('class' => 'element-container-labelless'));
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
            $linkContainer = \MUtil\Html::create()->div(array('class' => 'element-container-labelless'));
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
     * Should be called after answering the request to allow the Target
     * to check if all required registry values have been set correctly.
     *
     * @return boolean False if required are missing.
     */
    public function checkRegistryRequestsAnswers()
    {
        return $this->menu && parent::checkRegistryRequestsAnswers();
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
            return new \Gems\TabForm($options);
        }
        if (!isset($options['class'])) {
            $options['class'] = 'form-horizontal';
        }

        if (!isset($options['role'])) {
            $options['role'] = 'form';
        }
        return new \Gems\Form($options);
    }

    /**
     * Create the snippets content
     *
     * This is a stub function either override getHtmlOutput() or override render()
     *
     * @param \Zend_View_Abstract $view Just in case it is needed here
     * @return \MUtil\Html\HtmlInterface Something that can be rendered
     */
    public function getHtmlOutput(\Zend_View_Abstract $view)
    {
        $htmlDiv = \MUtil\Html::div();

        $title = $this->getTitle();
        if ($title) {
            $htmlDiv->h3($title, array('class' => 'title'));
        }

        $form = parent::getHtmlOutput($view);

        $htmlDiv[] = $form;

        return $htmlDiv;
    }

    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
        $links = $this->menu->getMenuList();

        $links->addParameterSources($this->menu->getParameterSource());
        $links->addCurrentParent($this->_('Cancel'));

        if ($this->menuShowSiblings) {
            $links->addCurrentSiblings();
        }

        if ($this->menuShowChildren) {
            $links->addCurrentChildren();
        }

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
     * Helper function to allow generalized statements about the items in the model to used specific item names.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        if (is_callable($this->topicCallable)) {
            return call_user_func($this->topicCallable, $count);
        } else {
            return parent::getTopic($count);
        }
    }

    /**
     * If menu item does not exist or is not allowed, redirect to index
     *
     * @return \Gems\Snippets\ModelFormSnippetAbstract
     */
    protected function setAfterSaveRoute()
    {
        parent::setAfterSaveRoute();

        if (is_array($this->afterSaveRouteUrl)) {
            // Make sure controller is set
            if (!array_key_exists('controller', $this->afterSaveRouteUrl)) {
                $this->afterSaveRouteUrl['controller'] = $this->requestInfo->getCurrentController();
            }

            // Search array for menu item
            $find['controller'] = $this->afterSaveRouteUrl['controller'];
            $find['action'] = $this->afterSaveRouteUrl['action'];

            // If not allowed, redirect to index
            if (null == $this->menu->find($find)) {
                $this->afterSaveRouteUrl['action'] = 'index';
                $this->resetRoute = true;
            }
        }
        // \MUtil\EchoOut\EchoOut::track($this->routeAction, $this->resetRoute);

        return $this;
    }
}
