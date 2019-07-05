<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.0
 */
class Gems_Default_ContactAction extends \Gems_Controller_Action
{
    /**
     * @var \Gems_Menu
     */
    public $menu;

    /**
     *
     * @var \Gems_Project_ProjectSettings
     */
    public $project;

    /**
     * A list of all participating organizations.
     *
     * @return \MUtil_Html_HtmlElement
     */
    protected function _getOrganizationsList()
    {
        $html = new \MUtil_Html_Sequence();
        $sql = '
            SELECT *
            FROM gems__organizations
            WHERE gor_active=1 AND gor_url IS NOT NULL AND gor_task IS NOT NULL
            ORDER BY gor_name';

        // $organizations = array();
        // $organizations = array(key($organizations) => reset($organizations));
        $organizations = $this->db->fetchAll($sql);
        $orgCount = count($organizations);


        switch ($orgCount) {
            case 0:
                return $html->pInfo(sprintf($this->_('%s is still under development.'), $this->project->getName()));

            case 1:
                $organization = reset($organizations);

                $p = $html->pInfo(sprintf($this->_('%s is run by: '), $this->project->getName()));
                $p->a($organization['gor_url'], $organization['gor_name']);
                $p->append('.');

                $html->pInfo()->sprintf(
                        $this->_('Please contact the %s if you have any questions regarding %s.'),
                        $organization['gor_name'],
                        $this->project->getName()
                        );

                return $html;

            default:

                $p = $html->pInfo(sprintf(
                        $this->_('%s is a collaboration of these organizations:'),
                        $this->project->getName()
                        ));

                $data = \MUtil_Lazy::repeat($organizations);
                $ul = $p->ul($data, array('class' => 'indent'));
                $li = $ul->li();
                $li->a($data->gor_url->call($this, '_'), $data->gor_name, array('rel' => 'external'));
                $li->append(' (');
                $li->append($data->gor_task->call(array($this, '_')));
                $li->append(')');

                $html->pInfo()->sprintf(
                        $this->_('You can contact any of these organizations if you have questions regarding %s.'),
                        $this->project->getName()
                        );

                return $html;
        }
    }

    /**
     * Shows an about page
     */
    public function aboutAction()
    {
        $this->initHtml();

        $this->html->h3()->sprintf($this->_('About %s'), $this->project->getName());
        $this->html->pInfo(\MUtil_Html_Raw::raw($this->project->getLongDescription($this->locale->getLanguage())));
        $this->html->append($this->_getOrganizationsList());
    }

    /**
     * Show screen telling people how to report bugs
     */
    public function bugsAction()
    {
        // Uses just view/script/bugs.html
    }

    /**
     * Show screen telling people about gems
     */
    public function gemsAction()
    {
        $this->initHtml();

        $this->html->h3()->sprintf($this->_('About %s'), $this->_('GemsTracker'));
        $this->html->pInfo($this->_(
                'GemsTracker (GEneric Medical Survey Tracker) is a software package for (complex) distribution of questionnaires and forms during clinical research and for quality registration in healthcare.'));
        $this->html->pInfo()->sprintf(
                $this->_('%s is a project built using GemsTracker as a foundation.'),
                $this->project->getName());
        $this->html->pInfo()->sprintf($this->_('GemsTracker is an open source project hosted on %s.'))
                ->a(
                        'https://github.com/GemsTracker/gemstracker-library',
                        'GitHub',
                        array('rel' => 'external', 'target' => 'sourceforge')
                        );
        $this->html->pInfo()->sprintf($this->_('More information about GemsTracker is available on the %s website.'))
                ->a(
                        'http://gemstracker.org/',
                        'GemsTracker.org',
                        array('rel' => 'external', 'target' => 'gemstracker')
                        );
    }

    /**
     * General contact page
     */
    public function indexAction()
    {
        $this->initHtml();

        $this->html->h3($this->_('Contact'));

        $this->html->h4(sprintf($this->_('The %s project'), $this->project->getName()));
        $this->html->append($this->_getOrganizationsList());

        $this->html->h4($this->_('Information on this application'));
        $this->html->pInfo($this->_('Links concerning this web application:'));

        $menuItem = $this->menu->getCurrent();
        $ul = $menuItem->toUl($this->getRequest());
        $ul->class = 'indent';
        $this->html[] = $ul;
    }

    /**
     * Shows a support page
     */
    public function supportAction()
    {
        $this->initHtml();

        $this->html->h3($this->_('Support'));
        $this->html->pInfo()->sprintf(
                $this->_('There is more than one way to get support for %s.'),
                $this->project->getName()
                );

        if ($url = $this->project->getDocumentationUrl()) {
            $this->html->h4($this->_('Documentation'));

            $this->html->pInfo()->sprintf($this->_('All available documentation is gathered at: %s'))
                    ->a($url, array('rel' => 'external', 'target' => 'documentation'));
        }

        if ($url = $this->project->getManualUrl()) {
            $this->html->h4($this->_('Manual'));

            $this->html->pInfo()->sprintf($this->_('The manual is available here: %s'))
                    ->a($url, array('rel' => 'external', 'target' => 'manual'));
        }

        if ($url = $this->project->getForumUrl()) {
            $this->html->h4($this->_('The forum'));

            $this->html->pInfo()->sprintf($this->_(
                    'You will find questions asked by other users and ask new questions at our forum site: %s'
                    ))->a($url, array('rel' => 'external', 'target' => 'forum'));
        }

        if ($url = $this->project->getSupportUrl()) {
            $this->html->h4($this->_('Support site'));

            $this->html->pInfo()->sprintf($this->_('Check our support site at %s.'))
                    ->a($url, array('rel' => 'external', 'target' => 'support'));
        }

        $this->html->h4($this->_('Or contact'));
        $this->html->append($this->_getOrganizationsList());
    }

}
