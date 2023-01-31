<?php

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model\Type;

use MUtil\Html\HtmlElement;
use MUtil\Model\Type\JsonData;

/**
 *
 * @package    Gems
 * @subpackage Model\Type
 * @copyright  Copyright (c) 2016, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.2 Jan 2, 2017 2:45:45 PM
 */
class MaskedJsonData extends JsonData
{
    /**
     *
     * @var \Gems\User\User
     */
    protected $currentUser;

    /**
     *
     * @param int $maxTable Max number of rows to display in table display
     * @param string $separator Separator in table display
     * @param string $more There is more in table display
     */
    public function __construct(\Gems\User\User $user, $maxTable = 3, $separator = '<br />', $more = '...')
    {
        $this->currentUser = $user;

        parent::__construct($maxTable, $separator, $more);
    }

    /**
     * Displays the content
     *
     * @param mixed $value
     * @return string
     */
    public function formatDetailed(mixed $value): HtmlElement|string
    {
        //\MUtil\EchoOut\EchoOut::track($value);
        if (is_array($value)) {
            $group = $this->currentUser->getGroup();

            if ($group) {
                $value = $group->applyGroupToData($value);
            }
        }

        return parent::formatDetailed($value);
    }

    /**
     * Displays the content
     *
     * @param string $value
     * @return string
     */
    public function formatTable(mixed $value): HtmlElement|string
    {
        if (is_array($value)) {
            $group = $this->currentUser->getGroup();

            if ($group) {
                $value = $group->applyGroupToData($value);
            }
        }

        return parent::formatTable($value);
    }
}
