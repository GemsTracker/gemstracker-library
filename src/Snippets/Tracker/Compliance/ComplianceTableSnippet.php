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

use Gems\Html;
use Gems\Menu\MenuSnippetHelper;
use Gems\Model;
use Gems\Repository\TokenRepository;
use Gems\Snippets\ModelTableSnippet;
use Symfony\Contracts\Translation\TranslatorInterface;
use Zalt\Base\RequestInfo;
use Zalt\Html\AElement;
use Zalt\Html\ImgElement;
use Zalt\Late\Alternate;
use Zalt\Late\Late;
use Zalt\Model\Data\DataReaderInterface;
use Zalt\Snippets\ModelBridge\TableBridge;
use Zalt\SnippetsLoader\SnippetOptions;

/**
 *
 *
 * @package    Gems
 * @subpackage Tracker
 * @copyright  Copyright (c) 2012 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.5
 */
class ComplianceTableSnippet extends ModelTableSnippet
{
    /**
     * Menu actions to show in Edit box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuEditRoutes = [];

    /**
     * Menu actions to show in Show box.
     *
     * If controller is numeric $menuActionController is used, otherwise
     * the key specifies the controller.
     *
     * @var array (int/controller => action)
     */
    public array $menuShowRoutes = ['respondent.tracks.show-track'];

    public function __construct(
        SnippetOptions $snippetOptions, 
        RequestInfo $requestInfo, 
        MenuSnippetHelper $menuHelper, 
        TranslatorInterface $translate,
        protected TokenRepository $tokenRepository,
    )
    {
        parent::__construct($snippetOptions, $requestInfo, $menuHelper, $translate);
    }

    /**
     * Adds columns from the model to the bridge that creates the browse table.
     *
     * Overrule this function to add different columns to the browse table, without
     * having to recode the core table building code.
     */
    protected function addBrowseTableColumns(TableBridge $bridge, DataReaderInterface $dataModel)
    {
        $keys[\MUtil\Model::REQUEST_ID1] = 'gr2o_patient_nr';
        $keys[\MUtil\Model::REQUEST_ID2] = 'gr2o_id_organization';
        $keys[Model::RESPONDENT_TRACK]   = 'gr2t_id_respondent_track';

        // Add link to patient to overview
        $showResp = $this->menuHelper->getLateRouteUrl('respondent.show', $keys, $bridge);
        if ($showResp) {
            $aElem = new AElement($showResp['url']);
            $aElem->setOnEmpty('');

            // Make sure org is known
            $dataModel->get('gr2o_id_organization');

            $dataModel->set('gr2o_patient_nr', 'itemDisplay', $aElem);
            $dataModel->set('respondent_name', 'itemDisplay', $aElem);
        }

        $table = $bridge->getTable();
        $table->appendAttrib('class', 'compliance');

        $thead  = $table->thead();
        $th_row = $thead->tr(array('class' => 'rounds'));
        $th     = $th_row->td();
        $span   = 1;
        $cRound = '';
        $cDesc  = null;
        $thead->tr();
        
        if ($this->showMenu) {
            foreach ($this->getShowUrls($bridge, $keys) as $linkParts) {
                if (! isset($linkParts['label'])) {
                    $linkParts['label'] = $this->_('Show');
                }
                $bridge->addItemLink(Html::actionLink($linkParts['url'], $this->_('Show')));
            }
        }

        // Initialize alter
        $alternateClass = new Alternate(array('odd', 'even'));

        foreach($dataModel->getItemsOrdered() as $name) {
            $label = $dataModel->get($name, 'label');
            if ($label) {
                $round = $dataModel->get($name, 'round');
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
                    if ($cIcon = $dataModel->get($name, 'roundIcon')) {
                        $cDesc = ImgElement::imgFile($cIcon, array(
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

                if ($dataModel->get($name, 'noSort')) {
                    // $result = 'res_' . substr($name, 5);
                    $token  = 'tok_' . substr($name, 5);

                    $tds   = $bridge->addColumn(
                            Late::method($this->tokenRepository, 'getTokenStatusLinkForTokenId', $this->menuHelper, $bridge->$token),
                            array($label, 'title' => $dataModel->get($name, 'description'), 'class' => 'round')
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
