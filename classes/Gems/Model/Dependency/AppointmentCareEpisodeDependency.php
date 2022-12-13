<?php

/**
 *
 * @package    Gems
 * @subpackage Model\Dependency
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Model\Dependency;

use Gems\Agenda\Agenda;
use Gems\Util\Translated;
use MUtil\Model\Dependency\DependencyAbstract;

/**
 *
 * @package    Gems
 * @subpackage Model\Dependency
 * @copyright  Copyright (c) 2018, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 * @since      Class available since version 1.8.3 May 17, 2018 12:02:38 PM
 */
class AppointmentCareEpisodeDependency extends DependencyAbstract
{
    /**
     * Array of setting => setting of setting changed by this dependency
     *
     * The settings array for those effecteds that don't have an effects array
     *
     * @var array
     */
    protected $_defaultEffects = array('multiOptions');

    /**
     * Array of name => name of items dependency depends on.
     *
     * Can be overriden in sub class, when set to only field names this class will
     * change the array to the correct structure.
     *
     * @var array Of name => name
     */
    protected $_dependentOn = array('gap_id_user', 'gap_id_organization', 'gap_admission_time');

    /**
     * Array of name => array(setting => setting) of fields with settings changed by this dependency
     *
     * Can be overriden in sub class, when set to only field names this class will use _defaultEffects
     * to change the array to the correct structure.
     *
     * @var array of name => array(setting => setting)
     */
    protected $_effecteds = array('gap_id_episode');

    public function __construct(protected Agenda $agenda, protected Translated $translatedUtil)
    {
        parent::__construct();
    }

    /**
     * Returns the changes that must be made in an array consisting of
     *
     * <code>
     * array(
     *  field1 => array(setting1 => $value1, setting2 => $value2, ...),
     *  field2 => array(setting3 => $value3, setting4 => $value4, ...),
     * </code>
     *
     * By using [] array notation in the setting name you can append to existing
     * values.
     *
     * Use the setting 'value' to change a value in the original data.
     *
     * When a 'model' setting is set, the workings cascade.
     *
     * @param array $context The current data this object is dependent on
     * @param boolean $new True when the item is a new record not yet saved
     * @return array name => array(setting => value)
     */
    public function getChanges(array $context, bool $new = false): array
    {
        $options = $this->translatedUtil->getEmptyDropdownArray();

        if (isset($context['gap_id_user'], $context['gap_id_organization'])) {
            if (isset($context['gap_admission_time'])) {
                if ($context['gap_admission_time'] instanceof \DateTimeInterface) {
                    $admission = $context['gap_admission_time']->format('Y-m-d');
                } else {
                    $admission = $context['gap_admission_time'];
                }
                $where['gec_startdate <= ?'] = $admission;
                $where['gec_enddate IS NULL OR gec_enddate > ?'] = $admission;
            } else {
                $where = null;
            }

            $options = $options + $this->agenda->getEpisodesAsOptions(
                    $this->agenda->getEpisodesForRespId($context['gap_id_user'], $context['gap_id_organization'], $where)
                    );
        }
        return ['gap_id_episode' => ['multiOptions' => $options]];
    }
}
