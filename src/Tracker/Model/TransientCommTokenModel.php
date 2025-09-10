<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Model;

use Gems\Model\MetaModelLoader;
use Gems\Repository\OrganizationRepository;
use Gems\User\Mask\MaskRepository;
use Gems\Util\Translated;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @since      Class available since version 2.x
 */
class TransientCommTokenModel extends TokenModel
{
    /**
     * @var bool Temporary switch to enable / disable use of TokenModel
     */
    public static $useTokenModel = false;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        OrganizationRepository $organizationRepository,
        Translated $translatedUtil,
    )
    {
        parent::__construct(
            $metaModelLoader,
            $sqlRunner,
            $translate,
            $maskRepository,
            $organizationRepository,
            $translatedUtil
        );

        $this->addTable('gems__transient_comm_tokens', ['gto_id_token' => 'gtct_id_token']);
    }
}
