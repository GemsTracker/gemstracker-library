<?php

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2021, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Task\Sites;

/**
 *
 * @package    Gems
 * @subpackage Task\Sites
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class AddToBaseUrl extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Loader
     */
    protected $loader;

    /**
     * @inheritDoc
     */
    public function execute($baseUrl = null, $orgId = null, $isoLang = null)
    {
        if (! $baseUrl) {
            return;
        }

        $batch = $this->getBatch();
        $model = $this->loader->getModels()->getSiteModel();
        $model->applySettings(true, 'edit');

        $current   = $model->loadFirst(['gsi_url' => $baseUrl]);
        $newValues = [];

        if ($current) {
            if ($orgId) {
                $newValues['gsi_id']                   = $current['gsi_id'];
                $newValues['gsi_select_organizations'] = 1;

                if (! in_array($orgId, $current['gsi_organizations'])) {
                    $newValues['gsi_organizations'] = array_merge($current['gsi_organizations'], [$orgId]);

                    // The less url's attached to this url, the higher the priority
                    $newValues['gsi_order'] = count($newValues['gsi_organizations']) * 10;
                }
            }
        } else {
            $newValues = [
                'gsi_url'                  => $baseUrl,
                'gsi_order'                => 10,
                'gsi_select_organizations' => $orgId ? 1 : 0,
                'gsi_organizations'        => '|' . $orgId . '|',
                ];

            if ($isoLang !== null) {
                $newValues['gsi_iso_lang'] = $isoLang;
            }

            $batch->addMessage(sprintf($this->_('Added url %s to sites.'), $baseUrl));
        }

        if ($newValues) {
            $model->save($newValues);

            if ($model->getChanged()) {
                $counter = $batch->addToCounter('c' . $baseUrl);
                $batch->setMessage($baseUrl, sprintf(
                    $this->plural('%d organization added to %s', '%d organizations added to %s', $counter),
                    $counter,
                    $baseUrl
                    ));
            }
        }
    }
}
