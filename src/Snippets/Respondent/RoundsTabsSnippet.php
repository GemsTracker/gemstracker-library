<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Respondent;

use Gems\Db\ResultFetcher;
use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Snippets\TabSnippetAbstract;
use Gems\Tracker\Respondent;
use Gems\Util;
use Zalt\Base\RequestInfo;
use Zalt\Base\TranslateableTrait;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Model\MetaModelInterface;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Snippets
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.1
 */
class RoundsTabsSnippet extends TabSnippetAbstract
{
    use TranslateableTrait;

    /**
     * The tab values
     *
     * @var array key => label
     */
    protected $_tabs = [];

    /**
     * Default href parameter values
     *
     * Clicking a tab always resets the page counter
     *
     * @var array
     */
    protected array $href = ['page' => null];

    /**
     * The RESPONDENT model, not the token model
     *
     * @var DataReaderInterface
     */
    protected $model;

    /**
     * Optional array of labels that should never be the default tab
     *
     * @var array
     */
    protected $neverDefaults = [];

    /**
     * Required, can be derived from request or respondent
     *
     * @var int|null
     */
    protected ?int $organizationId = null;

    /**
     * Required
     *
     * @var null|\Gems\Tracker\Respondent
     */
    protected ?Respondent $respondent = null;


    /**
     * Required, can be derived from request or respondent
     *
     * @var int
     */
    protected ?int $respondentId = null;

    public function __construct(
        SnippetOptions $snippetOptions,
        RequestInfo $requestInfo,
        MenuSnippetHelper $menuSnippetHelper,
        TranslatorInterface $translate,
        protected readonly ResultFetcher $resultFetcher,
        protected readonly Util $util,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuSnippetHelper);

        $this->translate = $translate;

        if (! $this->organizationId) {
            if ($this->respondent) {
                $this->organizationId = $this->respondent->getOrganizationId();
            }
            if (! $this->organizationId) {
                $this->organizationId = $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID2);
            }
        }

        if (isset($this->organizationId) && (! isset($this->respondentId))) {
            if ($this->respondent) {
                $this->respondentId = $this->respondent->getId();
            }
            if (! $this->respondentId) {
                $this->respondentId = $this->util->getDbLookup()->getRespondentId(
                    $this->requestInfo->getParam(MetaModelInterface::REQUEST_ID1),
                    $this->organizationId
                );
            }
        }

    }

    /**
     * Return optionally the single parameter key which should left out for the default value,
     * but is added for all other tabs.
     *
     * @return mixed
     */
    protected function getParameterKey()
    {
        // gro_round_description
        return 'round';
    }

    /**
     * Function used to fill the tab bar
     *
     * @return array tabId => label
     */
    protected function getTabs(): array
    {
        return $this->_tabs;
    }

    /**
     * @inheritdoc
     */
    public function hasHtmlOutput(): bool
    {
        $sql = "SELECT COALESCE(gto_round_description, '') AS label,
                        SUM(
                            CASE
                            WHEN gto_completion_time IS NOT NULL
                            THEN 1
                            ELSE 0
                            END
                        ) AS completed,
                        SUM(
                            CASE
                            WHEN gto_completion_time IS NULL AND
                                gto_valid_from < CURRENT_TIMESTAMP AND
                                (gto_valid_until > CURRENT_TIMESTAMP OR gto_valid_until IS NULL)
                            THEN 1
                            ELSE 0
                            END
                        ) AS waiting,
                        COUNT(*) AS any
                    FROM gems__tokens INNER JOIN
                        gems__surveys ON gto_id_survey = gsu_id_survey INNER JOIN
                        gems__rounds ON gto_id_round = gro_id_round INNER JOIN
                        gems__respondent2track ON gto_id_respondent_track = gr2t_id_respondent_track INNER JOIN
                        gems__reception_codes AS rcto ON gto_reception_code = rcto.grc_id_reception_code INNER JOIN
                        gems__reception_codes AS rctr ON gr2t_reception_code = rctr.grc_id_reception_code
                    WHERE gto_id_respondent = ? AND
                        gro_active = 1 AND
                        gsu_active = 1 AND
                        rcto.grc_success = 1 AND
                        rctr.grc_success = 1
                    GROUP BY COALESCE(gto_round_description, ''), gto_round_description
                    ORDER BY MIN(COALESCE(gto_round_order, 100000)), gto_round_description";

        $tabLabels = $this->resultFetcher->fetchAll($sql, [$this->respondentId]);

        if ($tabLabels) {
            $default = null;
            $filters = array();
            $noOpen  = true;
            $tabs    = array();

            foreach ($tabLabels as $row) {
                $name = '_' . \MUtil\Form::normalizeName($row['label']);
                $label = $row['label'] ? $row['label'] : $this->_('empty');
                if ($row['waiting']) {
                    $label = sprintf($this->_('%s (%d open)'), $label, $row['waiting']);
                } else {
                    $label = $label;
                }
                if (! $row['label']) {
                    $label = Html::create('em', $label);
                }

                $filters[$name] = $row['label'];
                $tabs[$name]    = $label;

                if (in_array($row['label'], $this->neverDefaults)) {
                    // Skip default setting
                    continue;
                }

                if ($noOpen && ($row['completed'] > 0)) {
                    $default  = $name;
                }
                if ($row['waiting'] > 0) {
                    $default = $name;
                    $noOpen  = false;
                }
            }
            if (null === $default) {
                reset($filters);
                $default = key($filters);
            }

            // Set the model
            $reqFilter = $this->requestInfo->getParam($this->getParameterKey());
            if (! isset($filters[$reqFilter])) {
                $reqFilter = $default;
            }

            if ('' === $filters[$reqFilter]) {
                $this->model->getMetaModel()->setMeta('tab_filter', array("(gto_round_description IS NULL OR gto_round_description = '')"));
            } else {
                $this->model->getMetaModel()->setMeta('tab_filter', array('gto_round_description' => $filters[$reqFilter]));
            }

            // dump($tabs, $reqFilter, $default, $tabLabels);

            $this->defaultTab = $default;
            $this->_tabs      = $tabs;
        }

        return $this->_tabs && parent::hasHtmlOutput();
    }
}
