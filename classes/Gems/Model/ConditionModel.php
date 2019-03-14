<?php

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

/**
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2018 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.8.4
 */
class ConditionModel extends \Gems_Model_JoinModel
{
    /**
     *
     * @var \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     *
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     *
     * @var \Gems_Util
     */
    protected $util;

    /**
     *
     * @param string $name
     */
    public function __construct($name = 'conditions')
    {
        parent::__construct($name, 'gems__conditions', 'gcon');
    }

    /**
     * Set those settings needed for the browse display
     *
     * @param boolean $addCount Add a count in rounds column
     * @return \Gems\Model\ConditionModel
     */
    public function applyBrowseSettings($addCount = true)
    {
        $conditions = $this->loader->getConditions();

        $yesNo = $this->util->getTranslated()->getYesNo();


        $types = $conditions->getConditionTypes();
        reset($types);
        $default = key($types);
        $this->set('gcon_type', 'label', $this->_('Type'),
                'description', $this->_('Determines where the condition can be applied.'),
                'multiOptions', $types,
                'default', $default
                );

        $this->set('gcon_class', 'label', $this->_('Condition'),
                'multiOptions', []
                );

        $this->set('gcon_name', 'label', $this->_('Name'));
        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addColumn("CASE WHEN gcon_active = 1 THEN '' ELSE 'deleted' END", 'row_class');

        if ($addCount) {
            $this->addColumn(
                    "(SELECT COUNT(gro_id_round) FROM gems__rounds WHERE gcon_id = gro_condition)",
                    'usage'
                    );
            $this->set('usage', 'label', $this->_('Rounds'),
                    'description', $this->_('The number of rounds using this condition.'),
                    'elementClass', 'Exhibitor'
                    );
        }
        $this->addDependency('Condition\\TypeDependency');

        return $this;
    }

    /**
     * Set those settings needed for the detailed display
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyDetailSettings()
    {
        $this->applyBrowseSettings(false);

        $yesNo = $this->util->getTranslated()->getYesNo();

        $this->resetOrder();

        $this->set('gcon_type');
        $this->set('gcon_class');
        $this->set('gcon_name', 'description', $this->_('A name for this condition, will be used to select it when applying the condition.'));

        $this->set('condition_help', 'label', $this->_('Help'), 'elementClass', 'Exhibitor');

        // Set the order
        $this->set('gcon_condition_text1');
        $this->set('gcon_condition_text2');
        $this->set('gcon_condition_text3');
        $this->set('gcon_condition_text4');

        $this->set('gcon_active', 'label', $this->_('Active'),
                'multiOptions', $yesNo
                );

        $this->addDependency('Condition\\ClassDependency');

        return $this;
    }

    /**
     * Set those values needed for editing
     *
     * @return \Gems\Model\ConditionModel
     */
    public function applyEditSettings($create = false)
    {
        $this->applyDetailSettings();

        // gcon_id is not needed for some validators
        $this->set('gcon_id',            'elementClass', 'Hidden');

        $this->set('gcon_active',        'elementClass', 'Checkbox');

        return $this;
    }

}
