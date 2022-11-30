<?php

/**
 *
 * @package    Gems
 * @subpackage TrackMaintenanceWithEngineAction
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\TrackBuilder;

use Gems\Exception;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\Tracker;
use Gems\Tracker\Engine\TrackEngineInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage TrackMaintenanceWithEngineAction
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.2 9-sep-2015 18:46:19
 */
abstract class TrackMaintenanceWithEngineHandlerAbstract extends ModelSnippetLegacyHandlerAbstract
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
    protected array $defaultParameters = [
        'trackEngine' => 'getTrackEngine',
        'trackId'     => 'getTrackId',
    ];

    /**
     *
     * @var TrackEngineInterface
     */
    protected ?TrackEngineInterface $trackEngine = null;

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected Tracker $tracker,
    ) {
        parent::__construct($responder, $translate);
    }

    /**
     *
     * @return TrackEngineInterface
     * @throws Exception
     */
    protected function getTrackEngine(): TrackEngineInterface
    {
        if ($this->trackEngine instanceof TrackEngineInterface) {
            return $this->trackEngine;
        }
        $trackId = $this->getTrackId();

        if (! $trackId) {
            throw new Exception($this->_('Missing track identifier.'));
        }

        $this->trackEngine = $this->tracker->getTrackEngine($trackId);

        return $this->trackEngine;
   }

   protected function getTrackId(): int
   {
       return (int)$this->request->getAttribute('trackId');
   }

}
