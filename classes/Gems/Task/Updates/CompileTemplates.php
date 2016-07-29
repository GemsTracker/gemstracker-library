<?php
/**
 *
 * @package    Gems
 * @subpackage Task
 * @author     Menno Dekker <menno.dekker@erasmusmc.nl>
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @version    $id$
 */

/**
 * Recompile all templates
 *
 * @package    Gems
 * @subpackage Task
 * @copyright  Copyright (c) 2014 Erasmus MC
 * @license    New BSD License
 * @since      Class available since version 1.6.4  05-jun-2014
 */
class Gems_Task_Updates_CompileTemplates extends \MUtil_Task_TaskAbstract
{
    /**
     * @var \Gems_Project_ProjectSettings
     */
    public $project;
    
    /**
     * Should handle execution of the task, taking as much (optional) parameters as needed
     *
     * The parameters should be optional and failing to provide them should be handled by
     * the task
     */
    public function execute()
    {
        $model = new \Gems_Model_TemplateModel('templates', $this->project);
        $templates = $model->load();
        foreach ($templates as $name => $data) {
            // Now load individual template
            $data = $model->load(array('name'=> $name));
            // And save
            $model->save(reset($data));
        }
    }
}
