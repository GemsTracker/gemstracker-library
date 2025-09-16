<?php

declare(strict_types=1);

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 */

namespace Gems\Tracker\Model;

use Gems\Model\GemsJoinModel;
use Gems\Model\MetaModelLoader;
use Zalt\Base\TranslatorInterface;
use Zalt\Model\Sql\SqlRunnerInterface;

/**
 * @package    Gems
 * @subpackage Tracker\Model
 * @since      Class available since version 2.x
 */
class TransientCommTokenModel extends GemsJoinModel
{
    private bool $hasGroups = true;
    private bool $hasOrganization = false;
    private bool $hasRelation = false;
    private bool $hasRespondent = false;

    public function __construct(
        MetaModelLoader $metaModelLoader,
        SqlRunnerInterface $sqlRunner,
        TranslatorInterface $translate,
    ) {
        parent::__construct('gems__transient_comm_tokens', $metaModelLoader, $sqlRunner, $translate, 'commJobTransientTokenModel', false);

        $this->addTable('gems__tokens', ['gto_id_token' => 'gtct_id_token']);
        $this->addTable(    'gems__surveys',              ['gto_id_survey' => 'gsu_id_survey']);
        $this->addTable(    'gems__groups',               ['gsu_id_primary_group' => 'ggp_id_group']);
    }

    public function addGroups(): void
    {
        if (!$this->hasGroups) {
            $this->addTable(    'gems__surveys',              ['gto_id_survey' => 'gsu_id_survey']);
            $this->addTable(    'gems__groups',               ['gsu_id_primary_group' => 'ggp_id_group']);
        }
    }

    public function addOrganization(): void
    {
        if (!$this->hasOrganization) {
            $this->addTable(    'gems__organizations',        ['gto_id_organization' => 'gor_id_organization']);
        }
    }

    public function addRespondent(): void
    {
        if (!$this->hasRespondent) {
            $this->addTable('gems__respondent2org', ['gr2o_id_user' => 'gto_id_respondent', 'gr2o_id_organization' => 'gto_id_organization']);
            $this->hasRespondent = true;
        }
    }

    public function addRelation(): void
    {
        if (!$this->hasRelation) {
            $this->addLeftTable('gems__track_fields',         ['gto_id_relationfield' => 'gtf_id_field', 'gtf_field_type = "relation"']);       // Add relation fields
            $this->addLeftTable('gems__respondent_relations', ['gto_id_relation' => 'grr_id', 'gto_id_respondent' => 'grr_id_respondent']); // Add relation
            $this->hasRelation = true;
        }
    }
}
