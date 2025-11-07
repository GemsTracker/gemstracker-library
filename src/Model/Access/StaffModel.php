<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Model\Accessq
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Model\Access;

use Gems\Legacy\CurrentUserRepository;
use Gems\Model\MetaModelLoader;
use Gems\User\User;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;
use Zalt\SnippetsActions\SnippetActionInterface;

/**
 * @package    Gems
 * @subpackage Model\Accessq
 * @since      Class available since version 1.0
 */
class StaffModel extends \Gems\Model\GemsJoinModel implements \Zalt\SnippetsActions\ApplyActionInterface
{
    protected User $user;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        CurrentUserRepository $currentUserRepository,
    )
    {
        parent::__construct('gems__staff', $metaModelLoader, $sqlRunner, $translate, 'gems__staff', true);

        $this->user = $currentUserRepository->getCurrentUser();

        $metaModelLoader->setChangeFields($this->metaModel,'gsf');

        $this->addColumn(
            "CONCAT(
                    COALESCE(CONCAT(gsf_last_name, ', '), '-, '),
                    COALESCE(CONCAT(gsf_first_name, ' '), ''),
                    COALESCE(gsf_surname_prefix, '')
                    )",
            'name'
        );
        $this->addColumn(
            "CASE WHEN gsf_email IS NULL OR gsf_email = '' THEN 0 ELSE 1 END",
            'can_mail'
        );

        $allowedGroups = $this->user?->getAllowedStaffGroups();
        if ($allowedGroups) {
            $expr = sprintf(
                "CASE WHEN gsf_id_primary_group IN (%s) THEN 1 ELSE 0 END",
                implode(", ", array_keys($allowedGroups))
                );
        } else {
            $expr = '0';
        }
        $this->addColumn($expr, 'accessible_role');

        $this->applySettings();
    }

    /**
     * @inheritDoc
     */
    public function applyAction(SnippetActionInterface $action): void
    {
        // TODO: Implement applyAction() method.
    }

    public function applySettings()
    {
        $this->metaModel->set('accessible_role', ['default' => 1]);
    }
}