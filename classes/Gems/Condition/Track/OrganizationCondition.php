<?php

/**
 *
 *
 * @package    Gems
 * @subpackage Condition\Track
 * @author     mjong
 * @license    Not licensed, do not copy
 */

namespace Gems\Condition\Track;

use Gems\Condition\ConditionAbstract;
use Gems\Condition\TrackConditionInterface;

/**
 *
 * @package    Gems
 * @subpackage Condition\Track
 * @since      Class available since version 1.8.8
 */
class OrganizationCondition extends ConditionAbstract implements TrackConditionInterface
{
    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * @var \Gems\Util
     */
    protected $util;

    /**
     * @inheritDoc
     */
    public function getHelp()
    {
        return $this->_("Track condition will be valid when respondent is in one of the organizations");
    }

    /**
     * @inheritDoc
     */
    public function getModelFields($context, $new)
    {
        $empty  = $this->util->getTranslated()->getEmptyDropdownArray();
        $orgs   = $this->util->getDbLookup()->getOrganizationsWithRespondents();
        $output = [];

        for ($i = 1; $i < 5; $i++) {
            $output['gcon_condition_text' . $i] = [
                'label'        => sprintf($this->_('Organization %d'), $i),
                'elementClass' => 'Select',
                'multiOptions' => $empty + $orgs,
                ];
        }

        return $output;
    }

    /**
     * @inheritDoc
     */
    public function getName()
    {
        return $this->_('Respondent organization');
    }

    /**
     * @inheritDoc
     */
    public function getNotValidReason($value, $context)
    {
        // Never triggered
        return '';
    }

    /**
     * @inheritDoc
     */
    public function isValid($value, $context)
    {
        // Always usable in a track
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getTrackDisplay($trackId)
    {
        $orgs = [];

        for ($i = 1; $i < 5; $i++) {
            $field = 'gcon_condition_text' . $i;

            if (isset($this->_data[$field])) {
                $org = $this->loader->getOrganization($this->_data[$field]);

                if ($org && $org->exists()) {
                    $orgs[] = $org->getName();
                }
            }
        }

        switch (count($orgs)) {
            case 0:
                return $this->_('Track not in an organization');
            case 1:
                return sprintf($this->_('Organization is %s'), reset($orgs));
            default:
                return sprintf($this->_('Organization one of %s'), implode(', ', $orgs));
        }
    }

    /**
     * Is the condition for this round (token) valid or not
     *
     * This is the actual implementation of the condition
     *
     * @param \Gems\Tracker\RespondentTrack $respTrack
     * @param array $fieldData Optional field data to use instead of data currently stored at object
     * @return bool
     */
    public function isTrackValid(\Gems\Tracker\RespondentTrack $respTrack, array $fieldData = null)
    {
        $orgId = $respTrack->getOrganizationId();

        for ($i = 1; $i < 5; $i++) {
            $field = 'gcon_condition_text' . $i;

            if (isset($this->_data[$field]) && ($orgId == $this->_data[$field])) {
                return true;
            }
        }
        return false;
    }
}