<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent\Consent
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent\Consent;

use Gems\Menu\MenuSnippetHelper;
use Gems\Model\Respondent\RespondentConsentLogModel;
use Gems\Snippets\ModelTableSnippetAbstract;
use Gems\Tracker\Respondent;
use MUtil\Model\TableModel;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Respondent\Consent
 * @copyright  Copyright (c) 2019, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.6 11-Oct-2019 12:26:51
 */
class RespondentConsentLogSnippet extends ModelTableSnippetAbstract
{
    /**
     * Shortfix to add class attribute
     *
     * @var string
     */
    protected $class = 'browser table';

    protected array $menuEditRoutes = [];

    protected array $menuShowRoutes = [];

    /**
     *
     * @var \Gems\Model\RespondentModel
     */
    protected $model;

    /**
     * Optional
     *
     * @var \Gems\Tracker\Respondent
     */
    protected $respondent;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuHelper,
        TranslatorInterface $translate,
        protected readonly RespondentConsentLogModel $respondentConsentLogModel,
    ) {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);

        if ($this->respondent instanceof \Gems\Tracker\Respondent) {
            $this->caption = sprintf(
                $this->_('Consent change log for respondent %s, %s at %s'),
                $this->respondent->getPatientNumber(),
                $this->respondent->getFullName(),
                $this->respondent->getOrganization()->getName()
            );

        }
        $this->onEmpty = $this->_('No consent changes found');
    }


    /**
     * Creates the model
     *
     * @return \MUtil\Model\ModelAbstract
     */
    protected function createModel(): DataReaderInterface
    {
        if ($this->respondent && $this->respondent->exists) {
            $this->respondentConsentLogModel->setFilter([
                'glrc_id_user' => $this->respondent->getId(),
                'glrc_id_organization' => $this->respondent->getOrganizationId(),
            ]);
        }
        return $this->respondentConsentLogModel;
    }

    /**
     * The place to check if the data set in the snippet is valid
     * to generate the snippet.
     *
     * When invalid data should result in an error, you can throw it
     * here but you can also perform the check in the
     * checkRegistryRequestsAnswers() function from the
     * {@see \MUtil\Registry\TargetInterface}.
     *
     * @return boolean
     */
    public function hasHtmlOutput(): bool
    {
        return parent::hasHtmlOutput() &&
                ($this->respondent instanceof Respondent) &&
                $this->respondent->exists;
    }
}
