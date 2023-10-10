<?php

/**
 * Short description of file
 *
 * @package    Gems
 * @subpackage Default
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Actions;

use Gems\Handlers\Respondent\RespondentChildHandlerAbstract;
use Gems\Model\CommLogModel;
use Gems\Tracker\Respondent;

/**
 * Controller for looking at mail activity
 *
 * @package    Gems
 * @subpackage Default
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.4
 */
class RespondentMailLogHandler extends RespondentChildHandlerAbstract
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
    protected array $autofilterParameters = [
        'browse'        => true,
        'keyboard'      => true,
        'onEmpty'       => 'getOnEmptyText',
        'sortParamAsc'  => 'asrt',
        'sortParamDesc' => 'dsrt',
        'extraSort'     => ['grco_created' => SORT_DESC]
    ];

    /**
     * The snippets used for the autofilter action.
     *
     * @var mixed String or array of snippets name
     */
    protected array $autofilterSnippets = ['Mail\\Log\\MailLogBrowseSnippet'];

    /**
     * The default search data to use.
     *
     * @var array()
     */
    protected array $defaultSearchData = [\Gems\Snippets\AutosearchFormSnippet::PERIOD_DATE_USED => 'grco_created'];

    /**
     * The parameters used for the index action minus those in autofilter.
     *
     * When the value is a function name of that object, then that functions is executed
     * with the array key as single parameter and the return value is set as the used value
     * - unless the key is an integer in which case the code is executed but the return value
     * is not stored.
     *
     * @var array Mixed key => value array for snippet initialization
     */
    protected array $indexParameters = [
        'contentTitle' => 'getContentTitle',
    ];

    /**
     * The snippets used for the index action, before those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStartSnippets = ['Generic\\ContentTitleSnippet', 'Mail\\Log\\RespondentMailLogSearchSnippet'];


    /**
     * The snippets used for the index action, after those in autofilter
     *
     * @var mixed String or array of snippets name
     */
    protected array $indexStopSnippets = ['Generic\\CurrentButtonRowSnippet'];

    /**
     * Creates a model for getModel(). Called only for each new $action.
     *
     * The parameters allow you to easily adapt the model to the current action. The $detailed
     * parameter was added, because the most common use of action is a split between detailed
     * and summarized actions.
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     * @param string $action The current action.
     * @return \MUtil\Model\ModelAbstract
     */
    public function createModel(bool $detailed, string $action): CommLogModel
    {
        $model = $this->loader->getModels()->getCommLogModel($detailed);

        if (! $detailed) {
            $model->addFilter(array(
                'gr2o_patient_nr' => $this->_getParam(\MUtil\Model::REQUEST_ID1),
                'gr2o_id_organization' => $this->_getParam(\MUtil\Model::REQUEST_ID2),
                ));
        }

        return $model;
;
    }

    /**
     * Helper function to get the informed title for the index action.
     *
     * @return $string
     */
    public function getContentTitle()
    {
        $respondent = $this->getRespondent();
        if ($respondent) {
            return sprintf(
                    $this->_('Communication activity log for respondent %s: %s'),
                    $respondent->getPatientNumber(),
                    $respondent->getName()
                    );
        }
        return $this->getIndexTitle();
    }

    /**
     * Get the respondent object
     *
     * @return Respondent
     */
    public function getRespondent(): Respondent
    {
        if (! $this->_respondent instanceof Respondent) {
            if ($this->_getParam(\MUtil\Model::REQUEST_ID1) && $this->_getParam(\MUtil\Model::REQUEST_ID2)) {
                $this->_respondent = parent::getRespondent();

            } else {
                $id = $this->_getParam(\MUtil\Model::REQUEST_ID);

                if ($id) {
                    $model = $this->getModel();
                    $row = $model->loadFirst(array('grco_id_action' => $id));

                    if ($row) {
                        $this->_respondent = $this->loader->getRespondent(
                                $row['gr2o_patient_nr'],
                                $row['gr2o_id_organization']
                                );

                        if (! $this->_respondent->exists) {
                            throw new \Gems\Exception($this->_('Unknown respondent.'));
                        }

                        $this->_respondent->applyToMenuSource($this->menu->getParameterSource());
                    }
                }
            }
        }

        return $this->_respondent;
    }

    /**
     * Helper function to allow generalized statements about the items in the model.
     *
     * @param int $count
     * @return string
     */
    public function getTopic(int $count = 1): string
    {
        return $this->plural('communication activity', 'communication activities', $count);
    }

    /**
     * Get the filter to use with the model for searching
     *
     * @param boolean $useRequest Use the request as source (when false, the session is used)
     * @return array or false
     */
    public function getSearchFilter(bool $useRequest = true): array
    {
        $filter = parent::getSearchFilter($useRequest);

        $where = \Gems\Snippets\AutosearchFormSnippet::getPeriodFilter($filter, $this->db);
        if ($where) {
            $filter[] = $where;
        }

        return $filter;
    }

    /**
     * Resend a log item
     */
    public function resendAction(): void
    {
        $this->addSnippets('Gems\\Snippets\\Communication\\ResendCommLogItemSnippet');
    }
}
