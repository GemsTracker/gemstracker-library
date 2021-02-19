<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

/**
 *
 * @package    Gems
 * @subpackage Default
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class Gems_Default_SiteMaintenanceAction extends \Gems_Controller_ModelSnippetActionAbstract
{
    /**
     * Tags for cache cleanup after changes, passed to snippets
     *
     * @var array
     */
    public $cacheTags = ['urlsites'];

    /**
     *
     * @var \GemsEscort
     */
    public $escort;

    /**
     *
     * @var \Gems_Loader
     */
    public $loader;

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
}