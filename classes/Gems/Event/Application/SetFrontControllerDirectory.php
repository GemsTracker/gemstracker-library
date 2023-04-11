<?php


namespace Gems\Event\Application;


use Symfony\Contracts\EventDispatcher\Event;

class SetFrontControllerDirectory extends Event
{
    const NAME = 'gems.controllerdirectory.set';

    /**
     * @var \Zend_Controller_Front
     */
    protected $front;

    protected $found = false;

    /**
     * @var string
     */
    protected $controllerFileName;

    /**
     * @var string
     */
    protected $moduleName;

    public function __construct(\Zend_Controller_Front $front, $controllerFileName, $moduleName)
    {
        $this->front = $front;
        $this->controllerFileName = $controllerFileName;
        $this->moduleName = $moduleName;
    }

    public function setControllerDirIfControllerExists($controllerDir)
    {
        $controllerLocation = $controllerDir;
        $fileLocation = $controllerLocation . DIRECTORY_SEPARATOR . $this->controllerFileName;
        if (file_exists($fileLocation)) {
            $this->front->setControllerDirectory($controllerLocation, $this->moduleName);
            $this->found = true;
            $this->stopPropagation();
        }
    }
}
