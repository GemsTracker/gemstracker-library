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

use Gems\Exception;
use Gems\Handlers\ModelSnippetLegacyHandlerAbstract;
use Gems\MenuNew\RouteHelper;
use Gems\Repository\RespondentRepository;
use Gems\Tracker\Respondent;
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
 * @since      Class available since version 1.7.1 5-mei-2015 13:15:49
 */
abstract class RespondentChildHandlerAbstract extends ModelSnippetLegacyHandlerAbstract
{
    /**
     * @var Respondent
     */
    protected ?Respondent $_respondent = null;

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
    protected array $defaultParameters = array(
        'multiTracks' => 'isMultiTracks',
        'respondent' => 'getRespondent',
    );

    /**
     * The parameters used for the import action
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $importParameters = ['respondent' => null];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'AutosearchInRespondentSnippet'];

    public function __construct(
        RouteHelper $routeHelper,
        SnippetResponderInterface $responder,
        TranslatorInterface $translate,
        protected RespondentRepository $respondentRepository
    ) {
        parent::__construct($routeHelper, $responder, $translate);
    }

    /**
     * Retrieve the error message when a respondent does not exist
     *
     * @return string Use %s to place respondentnumber
     */
    public function getMissingRespondentMessage(): string
    {
        return $this->_('Respondent %s is not participating at the moment.');
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        if (! $this->_respondent) {
            $patientNumber = $this->request->getAttribute(Model::REQUEST_ID1);
            $organizationId = $this->request->getAttribute(Model::REQUEST_ID2);

            $this->_respondent = $this->respondentRepository->getRespondent($patientNumber, $organizationId);

            if ((! $this->_respondent->exists) && $patientNumber && $organizationId) {
                throw new Exception(sprintf($this->getMissingRespondentMessage(), $patientNumber));
            }

            if ($this->_respondent->exists && (! array_key_exists($this->_respondent->getOrganizationId(), $this->currentUser->getAllowedOrganizations()))) {
                throw new Exception(
                    $this->_('Inaccessible or unknown organization'),
                    403, null,
                    sprintf($this->_('Access to this page is not allowed for current role: %s.'), $this->currentUser->getRole()));
            }
            //$this->_respondent->applyToMenuSource($this->menu->getParameterSource());
        }

        return $this->_respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return int
     */
    public function getRespondentId()
    {
        if ($this->request->getAttribute(Model::REQUEST_ID1) !== null) {
            return $this->getRespondent()->getId();
        }

        return null;
    }

    /**
     *
     * @return boolean
     */
    protected function isMultiTracks()
    {
        return true;
    }
}
