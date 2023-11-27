<?php

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Selector;

use Gems\Menu\RouteHelper;
use Gems\Repository\TokenRepository;
use Gems\Util\Localized;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;

/**
 *
 * @package    Gems
 * @subpackage Selector
 * @copyright  Copyright (c) 2011 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.2
 */
class TokenDateSelector extends DateSelectorAbstract
{
    /**
     * The name of the database table to use as the main table.
     *
     * @var string
     */
    protected string $dataTableName = 'gems__tokens';

    /**
     * The name of the field where the date is calculated from
     *
     * @var string
     */
    protected string $dateFrom = 'gto_valid_from';

    /**
     *
     * @var array Every tokenData status displayed in table
     */
    protected array $statiUsed = ['O', 'P', 'I', 'M', 'A'];

    public function __construct(
        TranslatorInterface $translate,
        Localized $localized,
        \Zend_Db_Adapter_Abstract $db,
        RouteHelper $routeHelper,
        Translated $translatedUtil,
        protected TokenRepository $tokenRepository
    ) {
        parent::__construct($translate, $localized, $db, $routeHelper, $translatedUtil);
    }

    /**
     *
     * @param string $name
     * @return \Gems\Selector\SelectorField
     */
    public function addSubField(string $name): SelectorField
    {
        $field = $this->addField($name);
        $field->setClass('smallTime');
        $field->setLabelClass('indentLeft smallTime');

        return $field;
    }

    /**
     * Tells the models which fields to expect.
     */
    protected function loadFields()
    {
        $this->addField('tokens')
                ->setLabel($this->translate->_('Activated surveys'))
                ->setToCount("gto_id_token");

        foreach ($this->statiUsed as $key) {
            $this->addField('stat_' . $key)
                    ->setLabel([$this->tokenRepository->getStatusIcon($key), ' ', $this->tokenRepository->getStatusDescription($key)])
                    ->setToSumWhen($this->tokenRepository->getStatusExpressionFor($key));
        }
    }

    /**
     * Stub function to allow extension of standard one table select.
     *
     * @param \Zend_Db_Select $select
     */
    protected function processSelect(\Zend_Db_Select $select)
    {
        $select->columns([]);
        $select->join('gems__surveys', 'gto_id_survey = gsu_id_survey', []);
        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', []);
//        $select->columns(['gto_id_token']);
//        $select->join('gems__surveys', 'gto_id_survey = gsu_id_survey', ['gsu_id_primary_group', 'gsu_active']);
//        $select->join('gems__reception_codes', 'gto_reception_code = grc_id_reception_code', ['grc_success']);
    } // */
}
