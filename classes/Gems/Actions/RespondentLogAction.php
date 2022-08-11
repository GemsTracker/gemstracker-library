<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 17:36:20
 */
class RespondentLogAction extends \Gems\Actions\LogAction
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
    protected $autofilterParameters = ['extraFilter' => 'getRespondentFilter'];

    /**
     * Get the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->request->getAttribute(\MUtil\Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(\MUtil\Model::REQUEST_ID2);

            $respondent = $this->loader->getRespondent($patientNumber, $organizationId);

            if ((! $respondent->exists) && $patientNumber && $organizationId) {
                throw new \Gems\Exception(sprintf($this->_('Unknown respondent %s.'), $patientNumber));
            }

            $respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $respondent;
    }

    /**
     * Get filter for current respondent
     *
     * @return array
     */
    public function getRespondentFilter()
    {
        return array('gla_respondent_id' => $this->getRespondentId());
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        return $this->getRespondent()->getId();
    }
}
