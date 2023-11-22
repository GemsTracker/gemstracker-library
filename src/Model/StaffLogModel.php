<?php

namespace Gems\Model;

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
    ) {
        parent::__construct($metaModelLoader, $sqlRunner, $translate, $maskRepository);

        $this->getMetaModel()->setKeys([
            'id' => 'gsf_id_user',
            'logId' => 'gla_id'
        ]);
    }
}
