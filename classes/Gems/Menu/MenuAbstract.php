<?php


/**
 * Copyright (c) 2011, Erasmus MC
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
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
 * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 *
 * @package    Gems
 * @subpackage Menu
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @version    $Id$
 */

/**
 *
 * @package    Gems
 * @subpackage Menu
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
abstract class Gems_Menu_MenuAbstract
{
    public $escort;

    protected $_subItems;

    public function _($text, $locale = null)
    {
        return $this->escort->translate->_($text, $locale);
    }

    public function __construct(GemsEscort $escort)
    {
        $this->escort = $escort;
    }

    /**
     * Adds privileges that are used in this menu item to the array
     *
     * @param array $privileges
     */
    protected function _addUsedPrivileges(array &$privileges, $label)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                $_itemlabel = $label . ($item->get('label') ?: $item->get('privilege'));
                if ($_privilege = $item->get('privilege')) {

                    if (isset($privileges[$_privilege])) {
                        $privileges[$_privilege] .= "<br/>&nbsp; + " . $_itemlabel;
                    } else {
                        $privileges[$_privilege] = $_itemlabel;
                    }
                }
                $item->_addUsedPrivileges($privileges, $_itemlabel . '-&gt;');
            }
        }
    }

    protected function _toNavigationArray(array $parameterSources)
    {
        if ($this->_subItems) {
            $lastParams = null;
            $i = 0;
            $pages = array();
            foreach ($this->_subItems as $item) {
                if (! $item->get('button_only')) {
                    $page = $item->_toNavigationArray($parameterSources);

                    if (($this instanceof Gems_Menu_SubMenuItem) &&
                        (! $this->notSet('controller', 'action')) &&
                        isset($page['params'])) {
                        $params = $page['params'];
                        // TODO: ugly! Make beautiful!
                        unset($params['reset']);

                        if (count($params)) {
                            $class = '';
                        } else {
                            $class = 'noParameters ';
                        }

                        if ((null !== $lastParams) && ($lastParams !== $params)) {
                            // $pages[$i++] = array('type' => 'uri');
                            /* $l = $i - 1;
                            if (isset($pages[$l]['class'])) {
                                $pages[$l]['class'] .= ' breakAfter';
                            } else {
                                $pages[$l]['class'] =  'breakAfter';
                            } // */
                            $class .= 'breakBefore';
                        } else {
                            $class = trim($class);
                        }

                        if ($class) {
                            if (isset($page['class'])) {
                                $page['class'] .= ' ' . $class;
                            } else {
                                $page['class'] =  $class;
                            }
                        }
                        $lastParams = $params;
                    }
                    $pages[$i] = $page;
                    $i++;
                }
            }

            return $pages;
        }

        return array();
    }

    /**
     *
     * @param <type> $args_array
     * @return Gems_Menu_SubMenuItem
     */
    protected function add($args_array)
    {
        // Process parameters.
        $args = MUtil_Ra::args(func_get_args(), 0,
            array('visible' => true,    // All menu items are initally visible unless stated otherwise
                'allowed' => true,      // Same as with visible, need this for t_oNavigationArray()
                ));

        if (! isset($args['label'])) {
            $args['visible'] = false;
        }

        if (! isset($args['order'])) {
            $args['order'] = 10 * (count($this->_subItems) + 1);
        }

        $page = new Gems_Menu_SubMenuItem($this->escort, $this, $args);

        $this->_subItems[] = $page;

        return $page;
    }

    public function addBrowsePage($label, $privilege, $controller, array $other = array())
    {
        $page = $this->addPage($label, $privilege, $controller, 'index', $other);
        $page->addAutofilterAction();
        $page->addCreateAction();
        $page->addExcelAction();
        $page->addShowAction();
        $page->addEditAction();
        $page->addDeleteAction();

        return $page;
    }

    public function addContainer($label, $privilege = null, array $other = array())
    {
        $other['label'] = $label;

        if ($privilege) {
            $other['privilege'] = $privilege;
        }

        return $this->add($other);
    }

    public function addButtonOnly($label, $privilege, $controller, $action = 'index', array $other = array())
    {
        $other['button_only'] = true;

        return $this->addPage($label, $privilege, $controller, $action, $other);
    }

    public function addMailSetupPage($label)
    {
        $setup = $this->addContainer($label);

        // MAIL ACTIVITY CONTROLLER
        //$setup->addBrowsePage();
        $page = $setup->addPage($this->_('Activity'), 'pr.mail.log', 'mail-log');
        $page->addAutofilterAction();
        $page->addExcelAction();
        $page->addShowAction();

        // MAIL Server CONTROLLER
        $page = $setup->addBrowsePage($this->_('Servers'), 'pr.mail.server', 'mail-server');
        // $page->addAction($this->_('Test'), 'pr.mail.server.test', 'test')->addParameters(MUtil_Model::REQUEST_ID);

        // MAIL CONTROLLER
        $setup->addBrowsePage($this->_('Templates'), 'pr.mail', 'mail');

        return $setup;
    }

    /**
     * Add a page to the menu
     *
     * @param string $label         The label to display for the menu item
     * @param string $privilege     The privilege for the item
     * @param string $controller    What controller to use
     * @param string $action        The name of the action
     * @param array  $other         Array of extra options for this item
     *
     * @return Gems_Menu_SubMenuItem
     */
    public function addPage($label, $privilege, $controller, $action = 'index', array $other = array())
    {
        $other['label'] = $label;
        $other['controller'] = $controller;
        $other['action'] = $action;

        if ($privilege) {
            $other['privilege'] = $privilege;
        }

        return $this->add($other);
    }

    public function addPlanPage($label)
    {
        $infoPage = $this->addContainer($label);

        $plans[] = $infoPage->addPage($this->_('By period'), 'pr.plan.overview', 'overview-plan', 'index');
        $plans[] = $infoPage->addPage($this->_('By token'), 'pr.plan.token', 'token-plan', 'index');
        $plans[] = $infoPage->addPage($this->_('By respondent'), 'pr.plan.respondent', 'respondent-plan', 'index');

        foreach ($plans as $plan) {
            $plan->addAutofilterAction();
            $plan->addAction($this->_('Bulk mail'), 'pr.token.bulkmail', 'email', array('routeReset' => false));
            $plan->addExcelAction();
        }

        return $infoPage;
    }

    public function addProjectPage($label)
    {
        if ($this->escort instanceof Gems_Project_Tracks_SingleTrackInterface) {
            if ($trackId = $this->escort->getTrackId()) {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks', 'show')
                    ->addHiddenParameter(MUtil_Model::REQUEST_ID, $trackId);
                $trackSurveys = $infoPage;
            } else {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks');
                $trackSurveys = $infoPage->addShowAction('pr.project');
            }
            $trackSurveys->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                    ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::SURVEY_ID, 'gsu_id_survey');

            $infoPage->addAutofilterAction();

            // MUtil_Echo::track($infoPage->_toNavigationArray(array($this->escort->request)));
        } else {
            if ($this->escort instanceof Gems_Project_Tracks_StandAloneSurveysInterface) {
                $infoPage = $this->addContainer($label, 'pr.project');
                $tracksPage = $infoPage->addPage($this->_('Tracks'), 'pr.project', 'project-tracks');
                $tracksPage->addAutofilterAction();

                $trackSurveys = $tracksPage->addShowAction('pr.project');
                $trackSurveys->addAction($this->_('Preview'), 'pr.project.questions', 'questions')
                        ->addNamedParameters(MUtil_Model::REQUEST_ID, 'gro_id_track', Gems_Model::SURVEY_ID, 'gsu_id_survey');

                $surveysPage = $infoPage->addPage($this->_('Surveys'), 'pr.project', 'project-surveys');
                $surveysPage->addAutofilterAction();
                $surveysPage->addShowAction('pr.project');
            } else {
                $infoPage = $this->addPage($label, 'pr.project', 'project-tracks');
                $infoPage->addAutofilterAction();
                $infoPage->addShowAction('pr.project');
            }
        }

        return $infoPage;
    }

    public function addStaffPage($label, array $other = array())
    {
        $page = $this->addPage($label, 'pr.staff', 'staff', 'index', $other);
        $page->addAutofilterAction();
        $page->addCreateAction();
        $page->addShowAction();
        if ($this->escort->hasPrivilege('pr.staff.edit.all')) {
            $page->addEditAction();
            $page->addDeleteAction();
        } else {
            $page->addEditAction()->setParameterFilter('gsf_id_organization', $this->escort->getCurrentOrganization());
            $page->addDeleteAction()->setParameterFilter('gsf_id_organization', $this->escort->getCurrentOrganization());
        }

        return $page;
    }

    public function applyAcl(Zend_Acl $acl, $userRole)
    {
        if ($this->_subItems) {
            $anyVisible = false;

            foreach ($this->_subItems as $item) {

                if ($item->get('visible', true)) {
                    $allowed = $item->get('allowed', true);

                    if ($allowed && ($privilege = $item->get('privilege'))) {
                        $allowed = $acl->isAllowed($userRole, null, $privilege);
                    }

                    if ($allowed) {
                        $item->applyAcl($acl, $userRole);
                        $anyVisible = true;
                    } else {
                        // As an item can be invisible but allowed,
                        // but not disallowed but visible we need to
                        // set both.
                        $item->set('allowed', false);
                        $item->set('visible', false);
                        $item->setForChildren('allowed', false);
                        $item->setForChildren('visible', false);
                    }
                }
            }

            // Do not show a 'container' menu item (that depends for controller
            // on it's children) when no sub item is allowed.
            if ((! $anyVisible) && $this->notSet('controller', 'action')) {
                $this->set('allowed', false);
                $this->set('visible', false);
            }
        }

        return $this;
    }

    /**
     *
     * @param <type> $options
     * @param <type> $findDeep
     * @return Gems_Menu_SubMenuItem|null
     */
    protected function findItem($options, $findDeep = true)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($result = $item->findItem($options, $findDeep)) {
                    return $result;
                }
            }
        }

        return null;
    }

    protected function findItemPath($options)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($path = $item->findItemPath($options)) {
                    return $path;
                }
            }
        }

        return array();
    }

    protected function findItems($options, array &$results)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                $item->findItems($options, $results);
            }
        }
    }

    /**
     *
     * @return array of type Gems_Menu_SubMenuItem
     */
    public function getChildren()
    {
        if ($this->_subItems) {
            return $this->_subItems;
        } else {
            return array();
        }
    }

    public function hasChildren()
    {
        return (boolean) $this->_subItems;
    }

    abstract public function isTopLevel();

    abstract public function isVisible();


    protected function setBranchVisible(array $activeBranch)
    {
        $current = array_pop($activeBranch);

        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                if ($item->isVisible()) {
                    if ($item === $current) {
                        $item->setBranchVisible($activeBranch);
                    } else {
                        $item->setForChildren('visible', false);
                    }
                }
            }
        }

        return $this;
    }

    protected function setForChildren($key, $value)
    {
        if ($this->_subItems) {
            foreach ($this->_subItems as $item) {
                $item->set($key, $value);
                if ($item->_subItems) {
                    $item->setForChildren($key, $value);
                }
            }
        }
        return $this;
    }
 }
