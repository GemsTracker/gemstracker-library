<?php

namespace Gems\Model;

use Gems\Audit\AuditLog;
use Gems\Repository\StaffRepository;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class StaffLogModel extends LogModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
        AuditLog $auditLog,
        StaffRepository $staffRepository,
    ) {
        parent::__construct($metaModelLoader, $sqlRunner, $translate, $maskRepository, $auditLog, $staffRepository);

        $this->getMetaModel()->setKeys([
            'id' => 'gla_by',
            'logId' => 'gla_id'
        ]);
    }
}
