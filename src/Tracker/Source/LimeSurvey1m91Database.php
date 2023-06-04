<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Tracker\Source;

/**
 * Class description of LimeSurvey1m91Database
 *
 * Difference with 1.9 version:
 *   - private field was renamed to anonymized
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4.1
 */
class LimeSurvey1m91Database extends \Gems\Tracker\Source\LimeSurvey1m9Database
{
    /**
     * In 1.91 the field private = y was changed to anonymized = y
     *
     * @var string The LS version dependent field name for anonymized surveys
     */
    protected $_anonymizedField = 'anonymized';

    /**
     * Returns a list of field names that should be set in a newly inserted token.
     *
     * Added the usesleft value.
     *
     * @param \Gems\Tracker\Token $token
     * @return array Of fieldname => value type
     */
    protected function _fillAttributeMap(\Gems\Tracker\Token $token)
    {
        $values = parent::_fillAttributeMap($token);

        // Not really an attribute, but it is the best place to set this
        $values['usesleft'] = $token->isCompleted() ? 0 : 1;

        return $values;
    }

    /**
     * Check a token table for any changes needed by this version.
     *
     * @param array $tokenTable
     * @return array Fieldname => change field commands
     */
    protected function _checkTokenTable(array $tokenTable)
    {
        $missingFields = parent::_checkTokenTable($tokenTable);

        if (! isset($tokenTable['usesleft'])) {
            $missingFields['usesleft'] = "ADD `usesleft` INT( 11 ) NULL DEFAULT '1' AFTER `completed`";
        }

        return $missingFields;
    }
}