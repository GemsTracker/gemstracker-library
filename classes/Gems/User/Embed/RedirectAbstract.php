<?php

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\User\Embed;

/**
 *
 * @package    Gems
 * @subpackage User\Embed
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.8 01-Apr-2020 16:03:26
 */
abstract class RedirectAbstract extends \MUtil\Translate\TranslateableAbstract implements RedirectInterface
{
    /**
     * @var \Zend_Controller_Request_Abstract
     */
    protected $request;

    /**
     *
     * @return mixed Something to display as label. Can be an \MUtil\Html\HtmlElement
     */
    // abstract public function getLabel();

    /**
     * @return array redirect route
     */
    // abstract public function getRedirectRoute(\Gems\User\User $embeddedUser, \Gems\User\User $deferredUser, $patientId, $organizations);

    public function setRequest(\Zend_Controller_Request_Abstract $request)
    {
        $this->request = $request;
    }
}