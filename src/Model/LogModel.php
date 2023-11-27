<?php

declare(strict_types=1);

/**
 *
 * @package    Gems
 * @subpackage Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Model;

use Gems\Model\Type\MaskedJsonType;
use Gems\User\Mask\MaskRepository;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 *
 *
 * @package    Gems
 * @subpackage Model
 * @copyright  Copyright (c) 2015 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.7.1 16-apr-2015 16:53:36
 */
class LogModel extends GemsMaskedModel
{
    /**
     * Create a model for the log
     */
    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
        MaskRepository $maskRepository,
    ) {
        parent::__construct('gems__log_activity', $metaModelLoader, $sqlRunner, $translate, $maskRepository);

        $metaModelLoader->setChangeFields($this->metaModel, 'gla');

        $this->addTable('gems__log_setup', ['gla_action' => 'gls_id_action'])
            ->addLeftTable('gems__respondents', ['gla_respondent_id' => 'grs_id_user'])
            ->addLeftTable('gems__staff', ['gla_by' => 'gsf_id_user']);

        $this->addColumns();
    }

    private function addColumns(): void
    {
        $this->addColumn(
            sprintf(
                "CASE WHEN gla_by IS NULL THEN '%s'
                    ELSE CONCAT(
                        COALESCE(gsf_last_name, '-'),
                        ', ',
                        COALESCE(CONCAT(gsf_first_name, ' '), ''),
                        COALESCE(gsf_surname_prefix, '')
                        )
                    END",
                $this->_('(no user)')
            ),
            'staff_name'
        );
        $this->addColumn(
            sprintf(
                "CASE WHEN gla_respondent_id IS NULL THEN '%s'
                    ELSE CONCAT(
                        COALESCE(grs_last_name, '-'),
                        ', ',
                        COALESCE(CONCAT(grs_first_name, ' '), ''),
                        COALESCE(grs_surname_prefix, '')
                        )
                    END",
                $this->_('(no respondent)')
            ),
            'respondent_name'
        );
    }

    /**
     * Set those settings needed for the browse display
     */
    public function applyBrowseSettings($detailed = false): void
    {
        $this->metaModel->resetOrder();

        //Not only active, we want to be able to read the log for inactive organizations too
        $organizations = $this->sqlRunner
            ->fetchRows('gems__organizations', ['gor_id_organization', 'gor_name'], null, null);

        $organizationPairs = [];
        foreach ($organizations as $organization) {
            $organizationPairs[$organization['gor_id_organization']] = $organization['gor_name'];
        }

        $this->metaModel->set('gla_created', [
            'label' => $this->_('Date'),
        ]);
        $this->metaModel->set('gls_name', [
            'label' => $this->_('Action'),
        ]);
        $this->metaModel->set('gla_organization', [
            'label' => $this->_('Organization'),
            'multiOptions' => $organizationPairs,
        ]);
        $this->metaModel->set('staff_name', [
            'label' => $this->_('Staff'),
        ]);
        $this->metaModel->set('gla_role', [
            'label' => $this->_('Role'),
        ]);
        $this->metaModel->set('respondent_name', [
            'label' => $this->_('Respondent'),
        ]);

        $this->metaModel->set('gla_message', [
            'label' => $this->_('Message'),
            'type' => new MaskedJsonType($this->maskRepository),
        ]);
//        $jdType = new JsonType();
//        $jdType->apply($this->metaModel, 'gla_message');
//
        if ($detailed) {
            $this->metaModel->set('gla_data', [
                'label' => $this->_('Data'),
                'type' => new MaskedJsonType($this->maskRepository),
            ]);
//            $mjdType = new MaskedJsonType($this->maskRepository);
//            $mjdType->apply($this->metaModel, 'gla_data');

            $this->metaModel->set('gla_method', [
                'label' => $this->_('Method'),
            ]);
            $this->metaModel->set('gla_remote_ip', [
                'label' => $this->_('IP address'),
            ]);
        }

        $this->applyMask();
    }

    /**
     * Set those settings needed for the detailed display
     */
    public function applyDetailSettings(): void
    {
        $this->applyBrowseSettings(true);
    }
}
