<?php

namespace Gems\Model;

use Laminas\Db\Sql\Expression;
use Zalt\Base\TranslatorInterface;

class RespondentTrackFieldDataModel extends UnionModel
{
    public function __construct(
        MetaModelLoader $metaModelLoader,
        TranslatorInterface $translate,
    ) {
        parent::__construct($metaModelLoader, $translate, 'respondentTrackFieldDataModel');

        $trackFieldsModel = $metaModelLoader->createJoinModel('gems__respondent2track', 'trackFieldData', false);
        $trackFieldsModel->addTable('gems__track_fields',
            [
                'gr2t_id_track' => 'gtf_id_track'
            ],
            'gtf',
            false
        );

        $trackFieldsModel->addLeftTable(
            'gems__respondent2track2field',
            [
                'gr2t_id_respondent_track' => 'gr2t2f_id_respondent_track',
                'gtf_id_field' => 'gr2t2f_id_field',
            ],
            'gr2t2f',
            false
        );

        $trackFieldsModel->addColumn(new Expression('\'field\''), 'type');
        $trackFieldsModel->addColumn(new Expression('CONCAT(\'f__\', gtf_id_field)'), 'id');

        $this->addUnionModel($trackFieldsModel, null);

        //$trackAppointmentsModel = new JoinModel('trackAppointmentData', 'gems__respondent2track', 'gr2t', false);
        $trackAppointmentsModel = $metaModelLoader->createJoinModel('gems__respondent2track', 'trackAppointmentData', false);
        $trackAppointmentsModel->addTable('gems__track_appointments',
            [
                'gr2t_id_track' => 'gtap_id_track'
            ],
            'gtf',
            false
        );

        $trackAppointmentsModel->addLeftTable(
            'gems__respondent2track2appointment',
            [
                'gr2t_id_respondent_track' => 'gr2t2a_id_respondent_track',
                'gtap_id_app_field' => 'gr2t2a_id_app_field',
            ],
            'gr2t2f',
            false
        );

        $trackAppointmentsModel->addColumn(new Expression('\'appointment\''), 'type');
        $trackAppointmentsModel->addColumn(new Expression('CONCAT(\'a__\', gtap_id_app_field)'), 'id');
        $trackAppointmentsModel->addColumn(new Expression('\'appointment\''), 'gtf_field_type');

        $trackAppointmentdMapBase = $trackAppointmentsModel->getMetaModel()->getItemsOrdered();
        $trackAppointmentdMap = array_combine($trackAppointmentdMapBase, str_replace(['gr2t2a_', 'gtap'], ['gr2t2f_', 'gtf'], $trackAppointmentdMapBase));
        $trackAppointmentdMap['gr2t2a_id_app_field'] = 'gr2t2f_id_field';
        $trackAppointmentdMap['gr2t2a_id_appointment'] = 'gr2t2f_value';
        $trackAppointmentdMap[] = 'type';
        $trackAppointmentdMap[] = 'id';

        $this->addUnionModel($trackAppointmentsModel, $trackAppointmentdMap);
    }
}