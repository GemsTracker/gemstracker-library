<?php

/**
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Handlers\Respondent;

use Gems\Handlers\Overview\TokenSearchHandlerAbstract;
use Gems\Repository\OrganizationRepository;
use Gems\Repository\RespondentRepository;
use Gems\Tracker;
use MUtil\Model;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\SnippetsLoader\SnippetResponderInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1
 */
class TokenHandler extends TokenSearchHandlerAbstract
{
    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Token\\RespondentPlanTokenSnippet'];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Token\\TokenSearchSnippet'];

    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = ['Tracker\\TokenStatusLegenda'];

    public function __construct(
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        Tracker $tracker,
        \Zend_Db_Adapter_Abstract $db,
        protected RespondentRepository $respondentRepository,
        protected OrganizationRepository $organizationRepository,
    ) {
        parent::__construct($responder, $translate, $tracker, $db);
    }

    /**
     * Helper function to get the title for the index action.
     *
     * @return string
     */
    public function getIndexTitle(): string
    {
        return sprintf($this->_('Surveys assigned to respondent %s'), $this->request->getAttribute(Model::REQUEST_ID1));
    }

    /**
     * Get the respondent object
     *
     * @return \Gems\Tracker\Respondent
     */
    public function getRespondent()
    {
        static $respondent;

        if (! $respondent) {
            $patientNumber  = $this->request->getAttribute(Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(Model::REQUEST_ID2);

            $respondent = $this->respondentRepository->getRespondent($patientNumber, $organizationId);
            
            if ((! $respondent->exists) && $patientNumber && $organizationId) {
                throw new \Gems\Exception(sprintf($this->_('Unknown respondent %s.'), $patientNumber));
            }
        }

        return $respondent;
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

    /**
     * Get the data to use for searching: the values passed in the request + any defaults
     * used in the search form (or any other search request mechanism).
     *
     * It does not return the actual filter used in the query.
     *
     * @see getSearchFilter()
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array
     */
    public function getSearchData(bool $useRequest = true): array
    {
        $data = parent::getSearchData($useRequest);

        // Survey action data
        $data['gto_id_respondent']   = $this->getRespondentId();

        $orgsFor = $this->organizationRepository->getAllowedOrganizationsFor($this->request->getAttribute(Model::REQUEST_ID2));
        if (is_array($orgsFor)) {
            $data['gto_id_organization'] = $orgsFor;
        } elseif (true !== $orgsFor) {
            $data['gto_id_organization'] = $this->request->getAttribute(Model::REQUEST_ID2);
        }

        return $data;
    }

   /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('survey', 'surveys', $count);
    }
}
