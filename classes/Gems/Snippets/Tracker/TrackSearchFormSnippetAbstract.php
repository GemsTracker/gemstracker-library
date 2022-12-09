<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker;

use Gems\Db\ResultFetcher;
use Gems\Legacy\CurrentUserRepository;
use Gems\Repository\TrackDataRepository;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.5
 */
class TrackSearchFormSnippetAbstract extends \Gems\Snippets\AutosearchFormSnippet
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @var boolean
     */
    protected $singleTrackId = false;

    /**
     *
     * @var boolean
     */
    protected $trackFieldId  = false;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        TranslatorInterface $translate,
        ResultFetcher $resultFetcher,
        CurrentUserRepository $currentUserRepository,
        protected TrackDataRepository $trackData,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $translate, $resultFetcher);
        
        $this->currentUser = $currentUserRepository->getCurrentUser();
    }

    /**
     * Add filler select to the elements array
     *
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addFillerSelect(array &$elements, $data, $elementId = 'fillerfilter')
    {
        $elements[] = null;
        if (isset($data[$this->trackFieldId]) && !empty($data[$this->trackFieldId])) {
            $trackId = (int) $data[$this->trackFieldId];
        } else {
            $trackId = -1;
        }

        $options = $this->trackData->getRespondersForTrack($trackId);

        $elements[$elementId] = $this->_createSelectElement($elementId, $options, $this->_('(all fillers)'));
    }

    /**
     * Add organization select to the elements array
     *
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addOrgSelect(array &$elements, $data, $elementId = 'gto_id_organzation')
    {
        $orgs = $this->currentUser->getRespondentOrganizations();

        if (count($orgs) > 1) {
            if ($this->orgIsMultiCheckbox) {
                $elements[$elementId] = $this->_createMultiCheckBoxElements($elementId, $orgs, ' ');
            } else {
                $elements[$elementId] = $this->_createSelectElement($elementId, $orgs, $this->_('(all organizations)'));
            }
        }
    }

    /**
     * Add period select to the elements array
     *
     * @param array $elements
     * @param array $data
     */
    protected function addPeriodSelect(array &$elements, $data)
    {
        $dates = array(
            'gr2t_start_date' => $this->_('Track start'),
            'gr2t_end_date'   => $this->_('Track end'),
            'gto_valid_from'  => $this->_('Valid from'),
            'gto_valid_until' => $this->_('Valid until'),
        );
        // $dates = 'gto_valid_from';
        $this->_addPeriodSelectors($elements, $dates, 'gto_valid_from');
    }

    /**
     * Add track select to the elements array
     *
     * @param array $elements
     * @param array $data
     * @param string $elementId
     */
    protected function addTrackSelect(array &$elements, $data, $elementId = 'gto_id_track')
    {
        // Store for use in addFillerSelect
        $this->trackFieldId = $elementId;

        $orgs   = $this->currentUser->getRespondentOrganizations();
        $tracks = $this->trackData->getTracksForOrgs($orgs);

        if (count($tracks) > 1) {
            $elements[$elementId] = $this->_createSelectElement($elementId, $tracks, $this->_('(select a track)'));
            $elements[$elementId]->setAttrib('onchange', 'this.form.submit();');
        }
    }

}
