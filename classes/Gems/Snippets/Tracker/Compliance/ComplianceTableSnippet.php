<?php

/**
 *
 * @package    Gems
 * @subpackage Tracker
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 */

namespace Gems\Snippets\Tracker\Compliance;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ComplianceTableSnippet extends \Gems\Snippets\ModelTableSnippetGeneric
{
    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditActions = [];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowActions = ['track' => 'show-track'];

    /**
     *
     * @var \Gems\Util
     */
    protected $util;

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     *
     * @param \MUtil\Model\Bridge\TableBridge $bridge
     * @param \MUtil\Model\ModelAbstract $model
     * @return void
     */
    protected function addBrowseTableColumns(\MUtil\Model\Bridge\TableBridge $bridge, \MUtil\Model\ModelAbstract $model)
    {
        $this->applyTextMarker();

        // Add link to patient to overview
        $menuItems = $this->findUrls('show', $bridge);
        if ($menuItems) {
            $menuItem = reset($menuItems);
            if ($menuItem instanceof \Gems\Menu\SubMenuItem) {
                $href = $menuItem->toHRefAttribute($bridge);

                if ($href) {
                    $aElem = new \MUtil\Html\AElement($href);
                    $aElem->setOnEmpty('');

                    // Make sure org is known
                    $model->get('gr2o_id_organization');

                    $model->set('gr2o_patient_nr', 'itemDisplay', $aElem);
                    $model->set('respondent_name', 'itemDisplay', $aElem);
                }
            }
        }

        $tUtil = $this->util->getTokenData();
        $table = $bridge->getTable();
        $table->appendAttrib('class', 'compliance');

        $thead  = $table->thead();
        $th_row = $thead->tr(array('class' => 'rounds'));
        $th     = $th_row->td();
        $span   = 1;
        $cRound = null;
        $cDesc  = null;
        $thead->tr();

        if ($showMenuItems = $this->getShowUrls($bridge)) {
            foreach($showMenuItems as $showMenuItem) {
                $bridge->addItemLink(\Gems\Html::actionLink($menuItem, $this->_('Show')));
            }
        }

        // Initialize alter
        $alternateClass = new \MUtil\Lazy\Alternate(array('odd', 'even'));

        foreach($model->getItemsOrdered() as $name) {
            $label = $model->get($name, 'label');
            if ($label) {
                $round = $model->get($name, 'round');
                if ($round == $cRound) {
                    $span++;
                    $class = null;
                } else {
                    // If the round has an icon, show the icon else just 'R' since
                    // complete round description messes up the display
                    $th->append($cDesc ?: substr($cRound, 0, ($span * 4) - 1));
                    $th->title = $cRound;
                    $th->colspan = $span;

                    $span    = 1;
                    $cRound  = $round;
                    if ($cIcon = $model->get($name, 'roundIcon')) {
                        $cDesc = \MUtil\Html\ImgElement::imgFile($cIcon, array(
                            'alt'   => $cRound,
                            'title' => $cRound
                        ));
                    } else {
                        $cDesc   = null;
                    }
                    $class   = 'newRound';
                    $thClass = $class . ' ' . $alternateClass; // Add alternate class only for th
                    $th      = $th_row->td(array('class' => $thClass));
                }

                if ($model->get($name, 'noSort')) {
                    $result = 'res_' . substr($name, 5);
                    $token  = 'tok_' . substr($name, 5);

                    $tds   = $bridge->addColumn(
                            \MUtil\Lazy::method($tUtil, 'getTokenStatusLinkForTokenId', $bridge->$token),
                            array($label, 'title' => $model->get($name, 'description'), 'class' => 'round')
                            );
                } else {
                        $tds = $bridge->addSortable($name, $label);
                }
                if ($class) {
                    $tds->appendAttrib('class', $class);
                }
            }
        }
        $th->append($cRound);
        $th->colspan = $span;
    }
}
