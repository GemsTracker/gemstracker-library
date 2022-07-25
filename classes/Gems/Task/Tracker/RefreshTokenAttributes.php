<?php

/**
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Task\Tracker;

/**
 * Refresh the attributes of the token
 *
 * @package    Gems
 * @subpackage Task_Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5.2
 */
class RefreshTokenAttributes extends \MUtil\Task\TaskAbstract
{
    /**
     * @var \Gems\Loader
     */
    public $loader;

    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute($tokenId = null)
    {
        $batch   = $this->getBatch();
        $tracker = $this->loader->getTracker();
        $token   = $tracker->getToken($tokenId);

        $checked = $batch->addToCounter('ta-checkedTokens');
        if ($token->inSource()) {
            $survey = $token->getSurvey();
            if ($survey->copyTokenToSource($token, '')) {
                $batch->addToCounter('ta-changedTokens');
            }
        }

        $cTokens = $batch->getCounter('ta-changedTokens');

        $batch->setMessage('ta-check', sprintf(
                $this->plural('%d token out of %d tokens changed.', '%d tokens out of %d tokens changed.', $cTokens),
                $cTokens,
                $checked
                ));
    }
}