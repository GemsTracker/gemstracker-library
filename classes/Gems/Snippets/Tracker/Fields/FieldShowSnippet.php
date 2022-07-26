<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Fields;

use Gems\Tracker\Field\FieldInterface;

/**
 *
 * @package    Gems
 * @subpackage Snippets\Tracker\Fields
 * @license    New BSD License
 * @since      Class available since version 1.8.8
 */
class FieldShowSnippet extends \Gems\Snippets\ModelItemTableSnippetGeneric
{
    /**
     * @return \Zend_Db_Adapter_Abstract
     */
    protected $db;

    /**
     * @var \Gems\Loader
     */
    protected $loader;

    /**
     * Called after the check that all required registry values
     * have been set correctly has run.
     *
     * @return void
     */
    public function afterRegistry()
    {
        parent::afterRegistry();

        $this->menuList = $this->getMenuList();
    }


    /**
     * overrule to add your own buttons.
     *
     * @return \Gems\Menu\MenuList
     */
    protected function getMenuList()
    {
//        $fid  = $this->request->getParam(\Gems\Model::FIELD_ID);
//        $prev = $this->db->fetchOne(
//            "SELECT gcj_id_job FROM gems__comm_jobs 
//                WHERE gcj_id_order < (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
//                ORDER BY gcj_id_order DESC",
//            $fid) ?: null;
//        $next = $this->db->fetchOne(
//            "SELECT gcj_id_job FROM gems__comm_jobs 
//                WHERE gcj_id_order > (SELECT gcj_id_order FROM gems__comm_jobs WHERE gcj_id_job = ?) 
//                ORDER BY gcj_id_order ASC",
//            $fid) ?: null;
//
//        $this->loader
        $fid  = $this->request->getParam(\Gems\Model::FIELD_ID);
        $sub  = $this->request->getParam('sub');
        $tid  = $this->request->getParam(\MUtil\Model::REQUEST_ID);

        $trackEngine = $this->loader->getTracker()->getTrackEngine($tid);
        $fieldDef    = $trackEngine->getFieldsDefinition();
        
        $prev = false;
        $next = null;
        foreach ($fieldDef->getFieldNames() as $key => $label) {
            if (isset($field)) {
                $previous = $field;
            }
            $field = $fieldDef->getField($key);
            if ($field instanceof FieldInterface) {
                if (($fid == $field->getFieldId()) && ($sub == $field->getFieldSub())) {
                    if (isset($previous)) {
                        $prev = [
                            'gtf_id_track' => $tid,
                            'gtf_id_field' => $previous->getFieldId(),
                            'sub' => $previous->getFieldSub(),
                        ];
                    } else {
                        $prev = null;
                    }
                } elseif (false !== $prev) {
                    $next = [
                        'gtf_id_track' => $tid,
                        'gtf_id_field' => $field->getFieldId(),
                        'sub' => $field->getFieldSub(),
                    ];
                    break;
                }
            }
        }
        
        // \MUtil\EchoOut\EchoOut::track($tid, $fid, $prev, $next);
        
        $links = $this->menu->getMenuList();
        $links->addParameterSources($this->request, $this->menu->getParameterSource());

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil\Html::raw($this->_('&lt; Previous')), $prev));
        $links->addCurrentParent($this->_('Cancel'));
        $links->addCurrentChildren();
        $links->addCurrentSiblings();

        $links->append($this->menu->getCurrent()->toActionLink(true, \MUtil\Html::raw($this->_('Next &gt;')), $next));

        return $links;
    }
}