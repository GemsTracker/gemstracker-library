<?php

namespace Gems\Model;


use Gems\Util\Translated;

class CommMessengersModel extends JoinModel
{

    /**
     * @var Translated
     */
    protected $translatedUtil;

    /**
     * @var \Gems\Util
     */
    protected $util;

    public function __construct()
    {
        parent::__construct('commMessengers', 'gems__comm_messengers', 'gcm', true);
    }

    /**
     *
     * @param bool $detailed True when the current action is not in $summarizedActions.
     */
    public function applySetting($detailed = true)
    {
        $commUtil = $this->util->getCommMessengersUtil();

        $this->set('gcm_id_order',
            [
                'label' => $this->_('Order'),
                'description' => $this->_('The order in which the messengers should be displayed'),
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
                'description' => $this->_('The type of messenger this is, e.g. mail, sms'),
                'multiOptions' => $commUtil->getAvailableMessengerTypes(),
            ]
        );

        $this->set('gcm_messenger_identifier',
            [
                'label' => $this->_('Messenger identifier'),
                'description' => $this->_('An optional identifier for the chosen messenger type to pick a specific one'),
            ]
        );

        $this->set('gcm_active',
            [
                'label' => $this->_('Active'),
                'elementClass' => 'Checkbox',
                'multiOptions' => $this->translatedUtil->getYesNo()
            ]
        );

        \Gems\Model::setChangeFieldsByPrefix($this, 'gcm');
    }
}