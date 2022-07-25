<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 * @package    Gems
 * @subpackage Default
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class SiteMaintenanceAction extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * The parameters used for the autofilter action.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $autofilterParameters = [
        'extraSort'   => [
            'gsi_order' => SORT_ASC,
            'gsi_id' => SORT_ASC,
            ],
        ];
    
    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = ['urlsites'];

    /**
     *
     * @var \Gems\Escort
     */
    public $escort;

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected $indexStopSnippets = ['Generic\\CurrentSiblingsButtonRowSnippet', 'SiteMaintenance\\SiteSetupCheckSnippet', 'SiteMaintenance\\SiteMaintenanceInformation'];

    /**
     *
     * @var \Gems\Loader
     */
    public $loader;

    /**
     *
     * @var \Gems\Util
     */
    public $util;

    /**
     * @inheritDoc
     */
    protected function createModel($detailed, $action)
    {
        $model = $this->loader->getModels()->getSiteModel();
        $model->applySettings($detailed, $action);

        return $model;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return $string
     */
    public function getTopic($count = 1)
    {
        return $this->plural('site url', 'site url\'s', $count);
    }

    /**
     * Action for showing a browse page
     */
    public function indexAction()
    {
        $lock = $this->util->getSites()->getSiteLock();
        if ($lock->isLocked()) {
            $this->addMessage(sprintf($this->_('Automatic new site registration has been blocked since %s.'), $lock->getLockTime()));

            if ($menuItem = $this->menu->findController('site-maintenance', 'lock')) {
                $menuItem->set('label', $this->_('UNBLOCK new sites'));
            }
        } else {
            $this->addMessage($this->_('Automatic new site registration is unblocked!'));
            $this->addMessage($this->_('Block automatic site registration once setup is ready.'));
        }

        return parent::indexAction();
    }

    public function lockAction()
    {
        // Switch lock
        $this->util->getSites()->getSiteLock()->reverse();

        // Redirect
        $request = $this->getRequest();
        $this->_reroute($this->menu->getCurrentParent()->toRouteUrl());
    }
}