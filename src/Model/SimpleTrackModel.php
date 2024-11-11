<?php

namespace Gems\Model;

use Gems\Api\Model\Transformer\BooleanTransformer;
use Gems\Model\Transform\TrackOrganizationTransformer;
use Gems\Model\Transform\TrackValidTransformer;
use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

class SimpleTrackModel extends GemsJoinModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
    ) {
        parent::__construct('gems__tracks', $metaModelLoader, $sqlRunner, $translate, 'tracks');

        $this->metaModel->set('gtr_id_track', [
            'apiName' => 'id',
        ]);
        $this->metaModel->set('gtr_track_name', [
            'apiName' => 'name',
        ]);
        $this->metaModel->set('gtr_active', [
            'apiName' => 'active',
        ]);
        $this->metaModel->set('gtr_date_start', [
            'apiName' => 'start',
        ]);
        $this->metaModel->set('gtr_date_until', [
            'apiName' => 'end',
        ]);
        $this->addColumn(new Expression('CASE
            WHEN (gtr_date_start < CURRENT_TIMESTAMP AND (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP)) 
            THEN 1 
            ELSE 0 
        END'), 'valid');

        $this->metaModel->set('valid', [
            'apiName' => 'valid',
        ]);

        $this->metaModel->set('organization', [
            'apiName' => 'organization',
        ]);

        $this->metaModel->addTransformer(new BooleanTransformer(['gtr_active', 'valid']));
        $this->metaModel->addTransformer(new TrackOrganizationTransformer());
        $this->metaModel->addTransformer(new TrackValidTransformer());
    }
}