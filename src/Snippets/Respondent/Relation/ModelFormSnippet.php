<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Relation;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class ModelFormSnippet extends \Gems\Snippets\ModelFormSnippet {

    protected function setAfterSaveRoute() {

        $this->afterSaveRouteUrl = $this->menuHelper->getRelatedRouteUrl('index');

        return $this;
    }

}