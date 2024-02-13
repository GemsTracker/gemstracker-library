<?php

declare(strict_types=1);


/**
 * @package    Gems
 * @subpackage Handlers\Respondent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Handlers\Respondent;

use Gems\Exception;
use Gems\Model;
use Gems\Repository\RespondentRepository;
use Gems\SnippetsLoader\GemsSnippetResponder;
use Gems\Tracker\Respondent;
use Gems\User\User;
use Zalt\Base\TranslateableTrait;

/**
 * @package    Gems
 * @subpackage Handlers\Respondent
 * @since      Class available since version 1.0
 */
trait GetRespondentTrait
{
    use TranslateableTrait;

    /**
     * @var Respondent
     */
    protected ?Respondent $_respondent = null;

    protected User $currentUser;

    protected RespondentRepository $respondentRepository;

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
            $patientNumber  = $this->request->getAttribute(Model::REQUEST_ID1);
            $organizationId = intval($this->request->getAttribute(Model::REQUEST_ID2, $this->currentUser->getCurrentOrganizationId()));
            $this->currentUserRepository->assertAccessToOrganizationId($organizationId);

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

            if ($this->responder instanceof GemsSnippetResponder) {
                $this->_respondent->setMenu($this->responder->getMenuSnippetHelper(), $this->translate);
            }
        }

        return $this->_respondent;
    }

    /**
     * Retrieve the respondent id
     * (So we don't need to repeat that for every snippet.)
     *
     * @return ?int
     */
    public function getRespondentId(): ?int
    {
        if ($this->request->getAttribute(Model::REQUEST_ID1) !== null) {
            return $this->getRespondent()->getId();
        }

        return null;
    }}