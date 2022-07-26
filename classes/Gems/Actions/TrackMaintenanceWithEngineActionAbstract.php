<?php

/**
 *
 * @package    Gems
 * @subpackage TrackMaintenanceWithEngineAction
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage TrackMaintenanceWithEngineAction
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:46:19
 */
abstract class TrackMaintenanceWithEngineActionAbstract extends \Gems\Controller\ModelSnippetActionAbstract
{
    /**
     * Model level parameters used for all actions, overruled by any values set in any other
     * parameters array except the private $_defaultParamters values in this module.
     *
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected $defaultParameters = array(
        'trackEngine' => 'getTrackEngine',
        'trackId'     => '_getIdParam',
    );

    /**
     *
     * @var \Gems\Tracker\Engine\TrackEngineInterface
     */
    protected $trackEngine;

    /**
     * @var \Gems\Tracker
     */
    public $tracker;

    /**
     *
     * @return \Gems\Tracker\Engine\TrackEngineInterface
     * @throws \Gems\Exception
     */
    protected function getTrackEngine()
    {
        if ($this->trackEngine instanceof \Gems\Tracker\Engine\TrackEngineInterface) {
            return $this->trackEngine;
        }
        $trackId = $this->_getIdParam();

        if (! $trackId) {
            throw new \Gems\Exception($this->_('Missing track identifier.'));
        }

        $menuSource = $this->menu->getParameterSource();
        $this->trackEngine = $this->tracker->getTrackEngine($trackId);
        $this->trackEngine->applyToMenuSource($menuSource);
        $menuSource->setRequestId($trackId); // Tell the menu we're using track id as request id

        return $this->trackEngine;
   }

}
