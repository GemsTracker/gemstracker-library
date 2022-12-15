<?php

namespace Gems\Model;

use Gems\Api\Model\Transformer\BooleanTransformer;
use Gems\Api\Model\Transformer\IntTransformer;
use Gems\Model\Transform\TrackOrganizationTransformer;
use Gems\Model\Transform\TrackValidTransformer;

class SimpleTrackModel extends JoinModel
{
    public function __construct()
    {
        parent::__construct('tracks', 'gems__tracks', 'gtr');

        $this->set('gtr_id_track', [
            'apiName' => 'id',
        ]);
        $this->set('gtr_track_name', [
            'apiName' => 'name',
        ]);
        $this->set('gtr_active', [
            'apiName' => 'active',
        ]);
        $this->set('gtr_date_start', [
            'apiName' => 'start',
        ]);
        $this->set('gtr_date_until', [
            'apiName' => 'end',
        ]);
        $this->addColumn(new \Zend_Db_Expr('CASE
            WHEN (gtr_date_start < CURRENT_TIMESTAMP AND (gtr_date_until IS NULL OR gtr_date_until > CURRENT_TIMESTAMP)) 
            THEN 1 
            ELSE 0 
        END'), 'valid');

        $this->set('valid', [
            'apiName' => 'valid',
        ]);

        $this->set('organization', [
            'apiName' => 'organization',
        ]);

        $this->addTransformer(new BooleanTransformer(['gtr_active', 'valid']));
        $this->addTransformer(new TrackOrganizationTransformer());
        $this->addTransformer(new TrackValidTransformer());
    }
}