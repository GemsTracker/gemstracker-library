<?php

namespace Gems\Model;


class CommMethodsModel extends \Gems_Model_JoinModel
{
    /**
     * @var \Gems_Util
     */
    protected $util;

    public function __construct()
    {
        parent::__construct('commMethods', 'gems__comm_methods', 'gcm', true);
    }

    /**
     *
     * @param boolean $detailed True when the current action is not in $summarizedActions.
     */
    public function applySetting($detailed = true)
    {
        $commUtil = $this->util->getCommMethodsUtil();
        $translated = $this->util->getTranslated();

        $this->set('gcm_id_order',
            [
                'label' => $this->_('Order'),
                'description' => $this->_('The order in which the methods should be displayed'),
            ]
        );
        $this->set('gcm_name',
            [
                'label' => $this->_('Name'),
            ]
        );
        $this->set('gcm_description',
            [
                'label' => $this->_('Description'),
            ]
        );
        $this->set('gcm_type',
            [
                'label' => $this->_('Type'),
                'description' => $this->_('The type of method this is, e.g. mail, sms'),
                'multiOptions' => $commUtil->getAvailableMethodTypes(),
            ]
        );

        $this->set('gcm_method_identifier',
            [
                'label' => $this->_('Method identifier'),
                'description' => $this->_('An optional identifier for the chosen method type to pick a specific one'),
            ]
        );

        $this->set('gcm_active',
            [
                'label' => $this->_('Active'),
                'elementClass', 'Checkbox',
                'multiOptions', $translated->getYesNo()
            ]
        );

        \Gems_Model::setChangeFieldsByPrefix($this, 'gcm');
    }
}
