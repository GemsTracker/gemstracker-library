<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

class Test_Escort extends GemsEscort {
    
    public function _initLogger() {
        $this->bootstrap('project');    // Make sure the project object is available
        $logger = \Gems_Log::getLogger();

        $writer = new \Zend_Log_Writer_Null();
        $logger->addWriter($writer);

        \Zend_Registry::set('logger', $logger);
    }
    
    public function _initProject() {
        $projectArray = $this->includeFile(APPLICATION_PATH . '/configs/project.example.ini');

        if ($projectArray instanceof \Gems_Project_ProjectSettings) {
            $project = $projectArray;
        } else {
            $project = $this->createProjectClass('Project_ProjectSettings', $projectArray);
        }

        return $project;
    }
}


