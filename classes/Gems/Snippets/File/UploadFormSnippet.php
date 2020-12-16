<?php

/**
 *
 * @package    Gems
 * @subpackage Snippets\File
 * @author     Matijs de Jong <mjong@magnafacta.nl>
 * @copyright  Copyright (c) 2020, Erasmus MC and MagnaFacta B.V.
 * @license    New BSD License
 */

namespace Gems\Snippets\File;

/**
 *
 * @package    Gems
 * @subpackage Snippets\File
 * @license    New BSD License
 * @since      Class available since version 1.9.1
 */
class UploadFormSnippet extends \Gems\Snippets\FormSnippetAbstract
{
    /**
     * @var \Zend_Form_Element_File
     */
    protected $_fileElement;
    
    /**
     * @var string
     */
    protected $currentDir;

    /**
     * @var array Optional array of allowed extensions
     */
    protected $extensions;

    /**
     * @inheritDoc
     */
    protected function addFormElements(\Zend_Form $form)
    {
        $this->_fileElement = $form->createElement('File', 'file', ['label' => $this->_('Select a file to upload')]);
        $this->_fileElement->setDestination($this->currentDir)
            ->setRequired(true);
        
        if ($this->extensions) {
            $this->_fileElement->addValidator('Extension', false, $this->extensions);
            // Now set a custom validation message telling what extensions are allowed
            $validator = $this->_fileElement->getValidator('Extension');
            $validator->setMessage('Only %extension% files are accepted.', \Zend_Validate_File_Extension::FALSE_EXTENSION);
        }

//        'accept', 'application/pdf',
	
        $form->addElement($this->_fileElement);
    }
    
    /**
     * Hook containing the actual save code.
     *
     * @return int The number of "row level" items changed
     */
    protected function saveData()
    {
        //\MUtil_Echo::track($this->_fileElement->getFileName());
        if ($this->_fileElement->isValid(null) && $this->_fileElement->getFileName()) {
            if (! $this->_fileElement->receive()) {
                throw new \Zend_File_Transfer_Exception(sprintf(
                                                            $this->_("Error retrieving file '%s'."),
                                                            basename($this->_fileElement->getFileName())
                                                        ));
            }

            return 1;
        }
        
        return 0;
    }
    
    /**
     * Set what to do when the form is 'finished'.
     *
     * #param array $params Url items to set for this route
     * @return MUtil_Snippets_ModelFormSnippetAbstract (continuation pattern)
     */
    protected function setAfterSaveRoute(array $params = array())
    {
        $fileName = $this->_fileElement->getFileName();
        if ($fileName) {
            $filePath = \MUtil_String::stripStringLeft($fileName, $this->currentDir);
            return parent::setAfterSaveRoute([\MUtil_Model::REQUEST_ID => ltrim(str_replace(['\\', '/', '.'], ['|', '|', '%2E'], $filePath), '|')]);
        }   
    }
}